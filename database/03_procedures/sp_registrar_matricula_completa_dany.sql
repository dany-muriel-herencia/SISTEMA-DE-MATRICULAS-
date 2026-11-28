USE bd_matricula_universitaria;

-- =============================================================================
-- PROCEDIMIENTO 03: sp_registrar_matricula_completa (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-05 (M05 / M06: TX-01 Maestra) — CU-23, CU-24, CU-27, CU-44, CU-45, CU-46
-- Unifica Matrícula Alumno + Matrícula Manual Excepcional del Admin:
--   * Alumno (p_id_admin IS NULL): exige turno EN_TURNO en cola, 0 deuda vencida y apto médico.
--   * Admin  (p_id_admin IS NOT NULL): omite cola y, con p_numero_rd, puede habilitar
--     sobrevacante (p_autorizar_sobrepaso=1), cruce horario (p_autorizar_cruce=1), deuda o médico.
--   * Orden canónico: matriculas -> secciones en orden ascendente (MIN(id_sec)) para cero deadlocks.
-- =============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_registrar_matricula_completa$$
CREATE PROCEDURE sp_registrar_matricula_completa( 
    IN  p_id_estudiante       INT UNSIGNED,
    IN  p_id_periodo          SMALLINT UNSIGNED,
    IN  p_id_admin            INT UNSIGNED,
    IN  p_numero_rd           VARCHAR(80),
    IN  p_autorizar_sobrepaso TINYINT(1),
    IN  p_autorizar_cruce     TINYINT(1),
    IN  p_lista_secciones     VARCHAR(255),
    OUT p_id_matricula        INT UNSIGNED,
    OUT p_codigo_resp         VARCHAR(25),
    OUT p_mensaje             VARCHAR(255)
)
proc_mat: BEGIN
    DECLARE v_rd               VARCHAR(80);
    DECLARE v_codigo_periodo   VARCHAR(10);
    DECLARE v_periodo_ok       INT DEFAULT 0;
    DECLARE v_estado_acad      VARCHAR(20);
    DECLARE v_id_usuario_est   INT UNSIGNED;
    DECLARE v_estado_cola      VARCHAR(20);
    DECLARE v_deuda_vencida    INT DEFAULT 0;
    DECLARE v_apto_medico      TINYINT(1) DEFAULT 0;
    DECLARE v_bypass_deuda     TINYINT(1) DEFAULT 0;
    DECLARE v_bypass_medico    TINYINT(1) DEFAULT 0;
    DECLARE v_bypass_cruce     TINYINT(1) DEFAULT 0;
    DECLARE v_cred_permitidos  TINYINT UNSIGNED DEFAULT 22;
    DECLARE v_cred_adicionales TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_constancia       VARCHAR(25);
    DECLARE v_lista_rem        VARCHAR(255);
    DECLARE v_pos_coma         INT;
    DECLARE v_sec_str          VARCHAR(20);
    DECLARE v_id_seccion       INT UNSIGNED;
    DECLARE v_id_curso         SMALLINT UNSIGNED;
    DECLARE v_id_per_sec       SMALLINT UNSIGNED;
    DECLARE v_cred_curso       TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_vac_total        SMALLINT UNSIGNED;
    DECLARE v_vac_ocup         SMALLINT UNSIGNED;
    DECLARE v_vac_extra        SMALLINT UNSIGNED;
    DECLARE v_cred_actuales    SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_veces_desapr     INT DEFAULT 0;
    DECLARE v_origen_calc      VARCHAR(20);
    DECLARE v_id_det_prev      INT UNSIGNED;
    DECLARE v_cursos_ins       INT DEFAULT 0;
    DECLARE v_total_cred       SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_errno            INT;
    DECLARE v_msg              TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_id_matricula = NULL;
        SET p_codigo_resp  = 'ERROR_SQL';
        SET p_mensaje      = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_matricula = NULL;
    SET v_rd = NULLIF(TRIM(p_numero_rd), '');

    IF p_lista_secciones IS NULL OR CHAR_LENGTH(TRIM(p_lista_secciones)) = 0 THEN
        SET p_codigo_resp = 'LISTA_VACIA';
        SET p_mensaje     = 'Debe indicar al menos una sección para matricularse.';
        LEAVE proc_mat;
    END IF;

    CREATE TEMPORARY TABLE IF NOT EXISTS tmp_secciones_matricula (
        id_sec INT UNSIGNED PRIMARY KEY
    ) ENGINE = MEMORY;
    DELETE FROM tmp_secciones_matricula;

    SET v_lista_rem = TRIM(p_lista_secciones);
    WHILE CHAR_LENGTH(v_lista_rem) > 0 DO
        SET v_pos_coma = INSTR(v_lista_rem, ',');
        IF v_pos_coma > 0 THEN
            SET v_sec_str   = TRIM(SUBSTRING(v_lista_rem, 1, v_pos_coma - 1));
            SET v_lista_rem = TRIM(SUBSTRING(v_lista_rem, v_pos_coma + 1));
        ELSE
            SET v_sec_str   = TRIM(v_lista_rem);
            SET v_lista_rem = '';
        END IF;
        IF CHAR_LENGTH(v_sec_str) > 0 AND CAST(v_sec_str AS UNSIGNED) > 0 THEN
            INSERT IGNORE INTO tmp_secciones_matricula (id_sec) VALUES (CAST(v_sec_str AS UNSIGNED));
        END IF;
    END WHILE;

    IF NOT EXISTS (SELECT 1 FROM tmp_secciones_matricula) THEN
        SET p_codigo_resp = 'LISTA_VACIA';
        SET p_mensaje     = 'Debe indicar al menos una sección válida.';
        LEAVE proc_mat;
    END IF;

    START TRANSACTION;

    -- 1) Control de turno en cola (solo cuando el estudiante actúa sin administrador)
    SELECT estado_cola INTO v_estado_cola
    FROM cola_matricula
    WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo
    FOR UPDATE;

    IF p_id_admin IS NULL THEN
        IF v_estado_cola IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'NO_EN_COLA';
            SET p_mensaje     = 'El estudiante no ingresó a la cola de matrícula.';
            LEAVE proc_mat;
        ELSEIF v_estado_cola <> 'EN_TURNO' THEN
            ROLLBACK;
            SET p_codigo_resp = 'SIN_TURNO';
            SET p_mensaje     = CONCAT('Aún no es su turno. Estado en cola: ', v_estado_cola, '.');
            LEAVE proc_mat;
        END IF;
    END IF;

    -- 2) Período, alumno hábil y no duplicidad
    SELECT COUNT(*), COALESCE(MAX(codigo_periodo), '')
    INTO v_periodo_ok, v_codigo_periodo
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo
      AND estado = 'ACTIVO'
      AND NOW() BETWEEN inicio_matricula AND fin_matricula;

    IF v_periodo_ok = 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'PERIODO_INACTIVO';
        SET p_mensaje     = 'El período no está activo o está fuera de fechas de matrícula.';
        LEAVE proc_mat;
    END IF;

    SELECT id_usuario, estado_academico
    INTO v_id_usuario_est, v_estado_acad
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_estado_acad IS NULL OR v_estado_acad IN ('EGRESADO', 'RETIRADO') THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTUDIANTE_NO_HABIL';
        SET p_mensaje     = 'El estudiante no existe o no está hábil para matricularse.';
        LEAVE proc_mat;
    END IF;

    IF EXISTS (SELECT 1 FROM matriculas WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo) THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_DUPLICADA';
        SET p_mensaje     = 'El estudiante ya cuenta con matrícula en este período.';
        LEAVE proc_mat;
    END IF;

    -- 3) Deuda vencida y apto médico (con bypass si Admin + RD)
    SELECT COUNT(*) INTO v_deuda_vencida
    FROM deudas_estudiante
    WHERE id_estudiante = p_id_estudiante
      AND estado IN ('PENDIENTE', 'FRACCIONADO')
      AND fecha_vencimiento <= CURDATE();

    SELECT COALESCE(MAX(apto_medico), 0) INTO v_apto_medico
    FROM ficha_medica
    WHERE id_estudiante = p_id_estudiante;

    IF v_deuda_vencida > 0 OR v_apto_medico = 0 THEN
        IF p_id_admin IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = IF(v_deuda_vencida > 0, 'DEUDA_PENDIENTE', 'SIN_APTO_MEDICO');
            SET p_mensaje     = IF(v_deuda_vencida > 0,
                                   'El estudiante registra deudas vencidas pendientes de pago.',
                                   'El estudiante no cuenta con ficha médica aprobada.');
            LEAVE proc_mat;
        END IF;
        IF v_rd IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'REQUIERE_RESOLUCION';
            SET p_mensaje     = 'Debe indicar número de resolución para matricular con deuda o sin apto médico.';
            LEAVE proc_mat;
        END IF;
        IF v_deuda_vencida > 0 THEN SET v_bypass_deuda = 1; END IF;
        IF v_apto_medico = 0   THEN SET v_bypass_medico = 1; END IF;
    END IF;

    IF COALESCE(p_autorizar_cruce, 0) = 1 OR COALESCE(p_autorizar_sobrepaso, 0) = 1 THEN
        IF p_id_admin IS NULL OR v_rd IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'REQUIERE_RESOLUCION';
            SET p_mensaje     = 'Sobrecupo o cruce autorizado requieren administrador y número de resolución.';
            LEAVE proc_mat;
        END IF;
        SET v_bypass_cruce = COALESCE(p_autorizar_cruce, 0);
    END IF;

    SET v_cred_permitidos  = fn_creditos_permitidos(p_id_estudiante, p_id_periodo);
    SET v_cred_adicionales = IF(v_cred_permitidos > 22, v_cred_permitidos - 22, 0);
    SET v_constancia       = CONCAT('MAT-', v_codigo_periodo, '-', LPAD(p_id_estudiante, 5, '0'));

    -- 4) Crear cabecera de matrícula y bloquearla
    INSERT INTO matriculas (
        id_estudiante, id_periodo, tipo_matricula, estado, total_creditos, creditos_adicionales,
        id_admin, bypass_cruces, bypass_deuda, bypass_medico, resolucion_excep, fecha_matricula, nro_constancia
    ) VALUES (
        p_id_estudiante, p_id_periodo, IF(p_id_admin IS NOT NULL AND v_rd IS NOT NULL, 'ESPECIAL', 'REGULAR'),
        'PENDIENTE', 0, v_cred_adicionales,
        p_id_admin, v_bypass_cruce, v_bypass_deuda, v_bypass_medico, v_rd, NOW(), v_constancia
    );

    SET p_id_matricula = LAST_INSERT_ID();
    SELECT id_matricula INTO p_id_matricula FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_bypass_deuda = 1 THEN
        INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
        VALUES (p_id_matricula, 'DEUDA_PENDIENTE', v_rd, 'Matrícula autorizada con deuda pendiente', p_id_admin);
    END IF;
    IF v_bypass_medico = 1 THEN
        INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
        VALUES (p_id_matricula, 'REQUISITO_MEDICO', v_rd, 'Matrícula autorizada sin apto médico', p_id_admin);
    END IF;
    IF v_bypass_cruce = 1 THEN
        INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
        VALUES (p_id_matricula, 'CRUCE_HORARIO', v_rd, 'Matrícula manual con cruce horario autorizado', p_id_admin);
    END IF;

    -- 5) Inscribir cada sección en orden ascendente (previene deadlocks)
    WHILE EXISTS (SELECT 1 FROM tmp_secciones_matricula) DO
        SELECT MIN(id_sec) INTO v_id_seccion FROM tmp_secciones_matricula;

        SET v_id_curso = NULL;
        SELECT s.id_curso, s.id_periodo, s.vacantes_total, s.vacantes_ocupadas, s.vacantes_extra, c.creditos
        INTO   v_id_curso, v_id_per_sec, v_vac_total, v_vac_ocup, v_vac_extra, v_cred_curso
        FROM   secciones s
        JOIN   cursos c ON c.id_curso = s.id_curso
        WHERE  s.id_seccion = v_id_seccion
        FOR UPDATE;

        IF v_id_curso IS NULL OR v_id_per_sec <> p_id_periodo THEN
            ROLLBACK;
            SET p_id_matricula = NULL;
            SET p_codigo_resp  = 'SECCION_INVALIDA';
            SET p_mensaje      = CONCAT('La sección ', v_id_seccion, ' no existe o pertenece a otro período.');
            LEAVE proc_mat;
        END IF;

        IF ((CAST(v_vac_total AS SIGNED) + CAST(v_vac_extra AS SIGNED)) - CAST(v_vac_ocup AS SIGNED)) <= 0 THEN
            IF COALESCE(p_autorizar_sobrepaso, 0) = 1 AND p_id_admin IS NOT NULL AND v_rd IS NOT NULL THEN
                UPDATE secciones SET vacantes_extra = vacantes_extra + 1 WHERE id_seccion = v_id_seccion;
            ELSE
                ROLLBACK;
                SET p_id_matricula = NULL;
                SET p_codigo_resp  = 'SIN_VACANTES';
                SET p_mensaje      = CONCAT('No quedan vacantes en la sección ', v_id_seccion, '.');
                LEAVE proc_mat;
            END IF;
        END IF;

        IF EXISTS (
            SELECT 1 FROM detalle_matricula dm
            JOIN secciones sec ON sec.id_seccion = dm.id_seccion
            WHERE dm.id_matricula = p_id_matricula AND sec.id_curso = v_id_curso AND dm.estado = 'INSCRITO'
        ) THEN
            ROLLBACK;
            SET p_id_matricula = NULL;
            SET p_codigo_resp  = 'CURSO_DUPLICADO';
            SET p_mensaje      = CONCAT('Curso duplicado en secciones seleccionadas (sección ', v_id_seccion, ').');
            LEAVE proc_mat;
        END IF;

        IF fn_validar_prerrequisitos(p_id_estudiante, v_id_curso) = 0 THEN
            ROLLBACK;
            SET p_id_matricula = NULL;
            SET p_codigo_resp  = 'FALTAN_PRERREQUISITOS';
            SET p_mensaje      = CONCAT('No cumple plan o prerrequisitos para la sección ', v_id_seccion, '.');
            LEAVE proc_mat;
        END IF;

        IF v_bypass_cruce = 0 AND fn_detectar_cruce_horario(p_id_matricula, v_id_seccion) = 1 THEN
            ROLLBACK;
            SET p_id_matricula = NULL;
            SET p_codigo_resp  = 'CRUCE_HORARIO';
            SET p_mensaje      = CONCAT('Cruce de horario detectado en sección ', v_id_seccion, '.');
            LEAVE proc_mat;
        END IF;

        SET v_cred_actuales = fn_calcular_creditos_matricula(p_id_matricula);
        IF (v_cred_actuales + v_cred_curso) > v_cred_permitidos THEN
            ROLLBACK;
            SET p_id_matricula = NULL;
            SET p_codigo_resp  = 'LIMITE_CREDITOS';
            SET p_mensaje      = CONCAT('Supera el límite de ', v_cred_permitidos, ' créditos permitidos.');
            LEAVE proc_mat;
        END IF;

        -- Conteo de desaprobaciones previas (excluyendo matrículas ANULADAs)
        SELECT COUNT(*) INTO v_veces_desapr
        FROM detalle_matricula dm
        JOIN secciones sec ON sec.id_seccion = dm.id_seccion
        JOIN matriculas mat ON mat.id_matricula = dm.id_matricula
        WHERE mat.id_estudiante = p_id_estudiante
          AND sec.id_curso = v_id_curso
          AND mat.estado != 'ANULADA'
          AND dm.estado = 'INSCRITO'
          AND dm.nota_final IS NOT NULL
          AND dm.nota_final < 10.50;

        SET v_origen_calc = CASE
            WHEN v_veces_desapr = 0 THEN 'PRIMERA_VEZ'
            WHEN v_veces_desapr = 1 THEN 'SEGUNDA_VEZ'
            ELSE 'TERCERA_VEZ'
        END;

        SET v_id_det_prev = NULL;
        SELECT id_detalle INTO v_id_det_prev
        FROM detalle_matricula
        WHERE id_matricula = p_id_matricula AND id_seccion = v_id_seccion AND estado = 'RETIRADO'
        LIMIT 1;

        IF v_id_det_prev IS NOT NULL THEN
            UPDATE detalle_matricula SET estado = 'INSCRITO', origen = v_origen_calc WHERE id_detalle = v_id_det_prev;
        ELSE
            INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, origen)
            VALUES (p_id_matricula, v_id_seccion, 'INSCRITO', v_origen_calc);
        END IF;

        UPDATE secciones SET vacantes_ocupadas = vacantes_ocupadas + 1 WHERE id_seccion = v_id_seccion;
        UPDATE matriculas SET total_creditos = total_creditos + v_cred_curso WHERE id_matricula = p_id_matricula;

        SET v_cursos_ins = v_cursos_ins + 1;
        DELETE FROM tmp_secciones_matricula WHERE id_sec = v_id_seccion;
    END WHILE;

    -- 6) Confirmar matrícula, cerrar turno en cola y auditar
    SET v_total_cred = fn_calcular_creditos_matricula(p_id_matricula);
    UPDATE matriculas SET estado = 'CONFIRMADA', total_creditos = v_total_cred WHERE id_matricula = p_id_matricula;

    UPDATE cola_matricula
    SET estado_cola = 'FINALIZADO', hora_fin = NOW()
    WHERE id_estudiante = p_id_estudiante
      AND id_periodo    = p_id_periodo
      AND estado_cola   IN ('EN_TURNO', 'EN_ESPERA');

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (
        COALESCE(p_id_admin, v_id_usuario_est), 'MATRICULA', 'REGISTRO_COMPLETO', 'matriculas', p_id_matricula,
        CONCAT('Constancia: ', v_constancia, ' | Cursos: ', v_cursos_ins, ' | Créditos: ', v_total_cred)
    );

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Matrícula confirmada exitosamente. Constancia: ', v_constancia);
END proc_mat$$

DELIMITER ;

-- Prueba rápida (Seed v4.0 - Est 1 como Admin en Sec 1 y Sec 5):
-- CALL sp_registrar_matricula_completa(1, 2, 1, NULL, 0, 0, '1,5', @id_mat, @cod, @msg);
-- SELECT @id_mat, @cod, @msg;
