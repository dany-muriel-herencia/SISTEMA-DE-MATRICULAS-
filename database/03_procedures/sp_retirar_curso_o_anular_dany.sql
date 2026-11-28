USE bd_matricula_universitaria;

-- =============================================================================
-- PROCEDIMIENTO 04: sp_retirar_curso_o_anular (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-05 (M05 / M06: TX-06)
-- Unifica Retiro de Curso + Anulación Total de Matrícula + Promoción de Espera:
--   * Si p_id_seccion > 0       -> Retira esa sección, resta créditos, libera cupo
--                                  y promueve al 1ro de lista_espera_seccion.
--   * Si p_id_seccion IS NULL/0 -> Anula toda la matrícula (exige p_id_admin),
--                                  cancela sus esperas propias y libera cada sección.
-- =============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_retirar_curso_o_anular$$
CREATE PROCEDURE sp_retirar_curso_o_anular( 
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    IN  p_id_admin     INT UNSIGNED,
    IN  p_motivo       VARCHAR(200),
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_ret_anu: BEGIN
    DECLARE v_id_est           INT UNSIGNED;
    DECLARE v_id_periodo       SMALLINT UNSIGNED;
    DECLARE v_estado_mat       VARCHAR(20);
    DECLARE v_bypass_cruce     TINYINT(1) DEFAULT 0;
    DECLARE v_id_usuario_est   INT UNSIGNED;
    DECLARE v_ini              DATETIME;
    DECLARE v_fin              DATETIME;
    DECLARE v_estado_per       VARCHAR(20);
    DECLARE v_sec_actual       INT UNSIGNED;
    DECLARE v_creditos_sec     TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_promovidos       INT DEFAULT 0;

    -- Variables para promoción de lista de espera
    DECLARE v_seguir_esp       TINYINT DEFAULT 1;
    DECLARE v_id_espera        INT UNSIGNED;
    DECLARE v_mat_esp          INT UNSIGNED;
    DECLARE v_est_esp          INT UNSIGNED;
    DECLARE v_per_esp          SMALLINT UNSIGNED;
    DECLARE v_bypass_esp       TINYINT(1) DEFAULT 0;
    DECLARE v_curso_sec        SMALLINT UNSIGNED;
    DECLARE v_cred_sec         TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_vac_disp         INT DEFAULT 0;
    DECLARE v_cred_perm_esp    TINYINT UNSIGNED DEFAULT 22;
    DECLARE v_cred_act_esp     SMALLINT UNSIGNED DEFAULT 0;
    DECLARE v_id_det_prev      INT UNSIGNED;
    DECLARE v_motivo_fallo     VARCHAR(80);
    DECLARE v_errno            INT;
    DECLARE v_msg              TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    CREATE TEMPORARY TABLE IF NOT EXISTS tmp_secciones_liberar (
        id_sec INT UNSIGNED PRIMARY KEY
    ) ENGINE = MEMORY;
    DELETE FROM tmp_secciones_liberar;

    START TRANSACTION;

    -- 1) Bloqueo canónico: primero MATRÍCULAS
    SELECT m.id_estudiante, m.id_periodo, m.estado, m.bypass_cruces, e.id_usuario
    INTO   v_id_est, v_id_periodo, v_estado_mat, v_bypass_cruce, v_id_usuario_est
    FROM   matriculas m
    JOIN   estudiantes e ON e.id_estudiante = m.id_estudiante
    WHERE  m.id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_id_est IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'La matrícula indicada no existe.';
        LEAVE proc_ret_anu;
    END IF;

    IF v_estado_mat = 'ANULADA' THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_ANULADA';
        SET p_mensaje     = 'La matrícula ya se encuentra anulada.';
        LEAVE proc_ret_anu;
    END IF;

    IF COALESCE(p_id_seccion, 0) > 0 THEN
        -- MODO A: RETIRO DE UNA SECCIÓN INDIVIDUAL
        SELECT inicio_matricula, fin_rectificacion, estado
        INTO   v_ini, v_fin, v_estado_per
        FROM   periodos_academicos
        WHERE  id_periodo = v_id_periodo;

        IF p_id_admin IS NULL AND (v_estado_per <> 'ACTIVO' OR NOW() < v_ini OR NOW() > v_fin) THEN
            ROLLBACK;
            SET p_codigo_resp = 'FUERA_DE_PERIODO';
            SET p_mensaje     = 'El retiro de cursos no está habilitado en este momento.';
            LEAVE proc_ret_anu;
        END IF;

        INSERT INTO tmp_secciones_liberar (id_sec) VALUES (p_id_seccion);
    ELSE
        -- MODO B: ANULACIÓN COMPLETA DE MATRÍCULA (requiere administrador)
        IF p_id_admin IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'ADMIN_REQUERIDO';
            SET p_mensaje     = 'Solo un administrador puede anular una matrícula completa.';
            LEAVE proc_ret_anu;
        END IF;

        -- Cancelar primero sus propias esperas para evitar auto-promoción al liberar vacantes
        UPDATE lista_espera_seccion
        SET estado = 'CANCELADO', fecha_atencion = NOW()
        WHERE id_matricula = p_id_matricula AND estado = 'EN_ESPERA';

        INSERT INTO tmp_secciones_liberar (id_sec)
        SELECT id_seccion
        FROM detalle_matricula
        WHERE id_matricula = p_id_matricula AND estado = 'INSCRITO';
    END IF;

    -- 2) Procesar cada sección a liberar en orden ascendente
    WHILE EXISTS (SELECT 1 FROM tmp_secciones_liberar) DO
        SELECT MIN(id_sec) INTO v_sec_actual FROM tmp_secciones_liberar;

        SET v_creditos_sec = NULL;
        SELECT c.creditos, s.id_curso
        INTO   v_creditos_sec, v_curso_sec
        FROM   secciones s
        JOIN   cursos c ON c.id_curso = s.id_curso
        WHERE  s.id_seccion = v_sec_actual
        FOR UPDATE;

        IF v_creditos_sec IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'SECCION_NO_EXISTE';
            SET p_mensaje     = CONCAT('La sección ', v_sec_actual, ' no existe.');
            LEAVE proc_ret_anu;
        END IF;

        UPDATE detalle_matricula
        SET estado = 'RETIRADO'
        WHERE id_matricula = p_id_matricula
          AND id_seccion   = v_sec_actual
          AND estado       = 'INSCRITO';

        IF ROW_COUNT() = 0 THEN
            ROLLBACK;
            SET p_codigo_resp = 'NO_INSCRITO';
            SET p_mensaje     = CONCAT('El alumno no está inscrito en la sección ', v_sec_actual, '.');
            LEAVE proc_ret_anu;
        END IF;

        UPDATE matriculas
        SET total_creditos = GREATEST(CAST(total_creditos AS SIGNED) - CAST(v_creditos_sec AS SIGNED), 0)
        WHERE id_matricula = p_id_matricula;

        UPDATE secciones
        SET vacantes_ocupadas = GREATEST(CAST(vacantes_ocupadas AS SIGNED) - 1, 0)
        WHERE id_seccion = v_sec_actual;

        -- Promoción automática FIFO de lista_espera_seccion para esta sección
        SET v_seguir_esp = 1;
        WHILE v_seguir_esp = 1 DO
            SELECT (CAST(vacantes_total AS SIGNED) + CAST(vacantes_extra AS SIGNED)) - CAST(vacantes_ocupadas AS SIGNED)
            INTO   v_vac_disp
            FROM   secciones WHERE id_seccion = v_sec_actual;

            IF COALESCE(v_vac_disp, 0) <= 0 THEN
                SET v_seguir_esp = 0;
            ELSE
                SET v_id_espera = NULL;
                SELECT id_espera, id_matricula INTO v_id_espera, v_mat_esp
                FROM lista_espera_seccion
                WHERE id_seccion = v_sec_actual AND estado = 'EN_ESPERA'
                ORDER BY posicion ASC
                LIMIT 1
                FOR UPDATE;

                IF v_id_espera IS NULL THEN
                    SET v_seguir_esp = 0;
                ELSE
                    SELECT id_estudiante, id_periodo, bypass_cruces
                    INTO   v_est_esp, v_per_esp, v_bypass_esp
                    FROM   matriculas WHERE id_matricula = v_mat_esp FOR UPDATE;

                    SET v_motivo_fallo = NULL;
                    IF fn_validar_prerrequisitos(v_est_esp, v_curso_sec) = 0 THEN
                        SET v_motivo_fallo = 'FALTAN_PRERREQUISITOS';
                    ELSEIF v_bypass_esp = 0 AND fn_detectar_cruce_horario(v_mat_esp, v_sec_actual) = 1 THEN
                        SET v_motivo_fallo = 'CRUCE_HORARIO';
                    ELSE
                        SET v_cred_perm_esp = fn_creditos_permitidos(v_est_esp, v_per_esp);
                        SET v_cred_act_esp  = fn_calcular_creditos_matricula(v_mat_esp);
                        IF (v_cred_act_esp + v_creditos_sec) > v_cred_perm_esp THEN
                            SET v_motivo_fallo = 'LIMITE_CREDITOS';
                        END IF;
                    END IF;

                    IF v_motivo_fallo IS NULL THEN
                        SET v_id_det_prev = NULL;
                        SELECT id_detalle INTO v_id_det_prev
                        FROM detalle_matricula
                        WHERE id_matricula = v_mat_esp AND id_seccion = v_sec_actual AND estado = 'RETIRADO'
                        LIMIT 1;

                        IF v_id_det_prev IS NOT NULL THEN
                            UPDATE detalle_matricula SET estado = 'INSCRITO' WHERE id_detalle = v_id_det_prev;
                        ELSE
                            INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, origen)
                            VALUES (v_mat_esp, v_sec_actual, 'INSCRITO', 'PRIMERA_VEZ');
                        END IF;

                        UPDATE secciones  SET vacantes_ocupadas = vacantes_ocupadas + 1 WHERE id_seccion = v_sec_actual;
                        UPDATE matriculas SET total_creditos = total_creditos + v_creditos_sec WHERE id_matricula = v_mat_esp;
                        UPDATE lista_espera_seccion SET estado = 'ASIGNADO', fecha_atencion = NOW() WHERE id_espera = v_id_espera;
                        SET v_promovidos = v_promovidos + 1;
                    ELSE
                        UPDATE lista_espera_seccion SET estado = 'EXPIRADO', fecha_atencion = NOW() WHERE id_espera = v_id_espera;
                    END IF;
                END IF;
            END IF;
        END WHILE;

        DELETE FROM tmp_secciones_liberar WHERE id_sec = v_sec_actual;
    END WHILE;

    IF COALESCE(p_id_seccion, 0) = 0 THEN
        UPDATE matriculas SET estado = 'ANULADA', id_admin = p_id_admin WHERE id_matricula = p_id_matricula;
        INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
        VALUES (p_id_admin, 'MATRICULA', 'ANULAR_MATRICULA', 'matriculas', p_id_matricula,
                CONCAT('Motivo: ', COALESCE(p_motivo, 'No indicado'), ' | Promovidos lista espera: ', v_promovidos));
        SET p_mensaje = CONCAT('Matrícula anulada exitosamente. Promovidos en lista de espera: ', v_promovidos);
    ELSE
        INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
        VALUES (COALESCE(p_id_admin, v_id_usuario_est), 'MATRICULA', 'RETIRO_CURSO', 'detalle_matricula', p_id_matricula,
                CONCAT('Retiro sección ', p_id_seccion, ' | Promovidos lista espera: ', v_promovidos));
        SET p_mensaje = CONCAT('Curso retirado exitosamente. Promovidos en lista de espera: ', v_promovidos);
    END IF;

    COMMIT;
    SET p_codigo_resp = 'EXITO';
END proc_ret_anu$$

DELIMITER ;

-- Prueba rápida:
-- CALL sp_retirar_curso_o_anular(101, 95, 1, 'Retiro voluntario de prueba', @cod, @msg);
-- SELECT @cod, @msg;
