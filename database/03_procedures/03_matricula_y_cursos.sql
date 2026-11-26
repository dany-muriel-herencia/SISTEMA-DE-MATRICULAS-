USE bd_matricula_universitaria;

DELIMITER $$

-- =============================================================================
-- PARTE 6: MATRÍCULA (TX-01) Y OPERACIONES SOBRE CURSOS
-- =============================================================================

-- -----------------------------------------------------------------------------
-- sp_registrar_matricula_completa  (TX-01) — PUNTO DE ENTRADA ÚNICO
-- Sin p_id_admin el estudiante debe estar EN_TURNO en la cola; con administrador
-- puede matricularse sin pasar por la cola. Todo o nada: matrícula, cursos,
-- confirmación, cierre del turno y auditoría. Secciones procesadas en orden
-- ascendente (previene deadlocks entre matrículas simultáneas).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_registrar_matricula_completa$$
CREATE PROCEDURE sp_registrar_matricula_completa(
    IN  p_id_estudiante   INT UNSIGNED,
    IN  p_id_periodo      SMALLINT UNSIGNED,
    IN  p_id_admin        INT UNSIGNED,
    IN  p_numero_rd       VARCHAR(80),
    IN  p_lista_secciones VARCHAR(255),
    OUT p_id_matricula    INT UNSIGNED,
    OUT p_codigo_resp     VARCHAR(25),
    OUT p_mensaje         VARCHAR(255)
)
proc_matricula: BEGIN
    DECLARE v_seccion_str  VARCHAR(20);
    DECLARE v_seccion_id   INT UNSIGNED;
    DECLARE v_pos_coma     INT;
    DECLARE v_lista_rem    VARCHAR(255);
    DECLARE v_constancia   VARCHAR(25);
    DECLARE v_resp_aux     VARCHAR(25);
    DECLARE v_msg_aux      VARCHAR(255);
    DECLARE v_estado_cola  VARCHAR(20);
    DECLARE v_errno        INT;
    DECLARE v_msg          TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_id_matricula = NULL;
        SET p_codigo_resp  = 'ERROR_SQL';
        SET p_mensaje      = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_matricula = NULL;

    IF p_lista_secciones IS NULL OR CHAR_LENGTH(TRIM(p_lista_secciones)) = 0 THEN
        SET p_codigo_resp = 'LISTA_VACIA';
        SET p_mensaje     = 'Debe indicar al menos una sección para matricularse.';
        LEAVE proc_matricula;
    END IF;

    -- Tabla temporal (DDL de tablas TEMPORARY no confirma transacciones)
    CREATE TEMPORARY TABLE IF NOT EXISTS tmp_secciones_matricula (
        id_sec INT UNSIGNED PRIMARY KEY
    ) ENGINE = MEMORY;

    DELETE FROM tmp_secciones_matricula;

    SET v_lista_rem = TRIM(p_lista_secciones);

    WHILE CHAR_LENGTH(v_lista_rem) > 0 DO
        SET v_pos_coma = INSTR(v_lista_rem, ',');
        IF v_pos_coma > 0 THEN
            SET v_seccion_str = TRIM(SUBSTRING(v_lista_rem, 1, v_pos_coma - 1));
            SET v_lista_rem   = TRIM(SUBSTRING(v_lista_rem, v_pos_coma + 1));
        ELSE
            SET v_seccion_str = TRIM(v_lista_rem);
            SET v_lista_rem   = '';
        END IF;

        IF CHAR_LENGTH(v_seccion_str) > 0 THEN
            INSERT IGNORE INTO tmp_secciones_matricula (id_sec) VALUES (CAST(v_seccion_str AS UNSIGNED));
        END IF;
    END WHILE;

    IF NOT EXISTS (SELECT 1 FROM tmp_secciones_matricula) THEN
        SET p_codigo_resp = 'LISTA_VACIA';
        SET p_mensaje     = 'Debe indicar al menos una sección válida para matricularse.';
        LEAVE proc_matricula;
    END IF;

    START TRANSACTION;

    -- 0. Control de turno (solo cuando NO actúa un administrador)
    SELECT estado_cola INTO v_estado_cola
    FROM cola_matricula
    WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo
    FOR UPDATE;

    IF p_id_admin IS NULL THEN
        IF v_estado_cola IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'NO_EN_COLA';
            SET p_mensaje     = 'El estudiante no ingresó a la cola de matrícula.';
            LEAVE proc_matricula;
        ELSEIF v_estado_cola <> 'EN_TURNO' THEN
            ROLLBACK;
            SET p_codigo_resp = 'SIN_TURNO';
            SET p_mensaje     = CONCAT('Aún no es su turno. Estado en cola: ', v_estado_cola, '.');
            LEAVE proc_matricula;
        END IF;
    END IF;

    -- 1. Aperturar matrícula PENDIENTE
    CALL sp_crear_matricula_pendiente(
        p_id_estudiante, p_id_periodo, p_id_admin, p_numero_rd,
        p_id_matricula, v_constancia, v_resp_aux, v_msg_aux
    );

    IF v_resp_aux <> 'EXITO' THEN
        ROLLBACK;
        SET p_id_matricula = NULL;
        SET p_codigo_resp  = v_resp_aux;
        SET p_mensaje      = v_msg_aux;
        LEAVE proc_matricula;
    END IF;

    -- 2. Inscribir cada sección en orden ascendente
    WHILE EXISTS (SELECT 1 FROM tmp_secciones_matricula) DO
        SELECT MIN(id_sec) INTO v_seccion_id FROM tmp_secciones_matricula;

        CALL sp_i_inscribir_curso(p_id_matricula, v_seccion_id, 'MATRICULA', v_resp_aux, v_msg_aux);

        IF v_resp_aux <> 'EXITO' THEN
            ROLLBACK;
            SET p_id_matricula = NULL;
            SET p_codigo_resp  = v_resp_aux;
            SET p_mensaje      = CONCAT('Error en sección ', v_seccion_id, ': ', v_msg_aux);
            LEAVE proc_matricula;
        END IF;

        DELETE FROM tmp_secciones_matricula WHERE id_sec = v_seccion_id;
    END WHILE;

    -- 3. Confirmar (PENDIENTE -> CONFIRMADA)
    CALL sp_confirmar_matricula(p_id_matricula, p_id_admin, v_resp_aux, v_msg_aux);

    IF v_resp_aux <> 'EXITO' THEN
        ROLLBACK;
        SET p_id_matricula = NULL;
        SET p_codigo_resp  = v_resp_aux;
        SET p_mensaje      = CONCAT('Error al confirmar matrícula: ', v_msg_aux);
        LEAVE proc_matricula;
    END IF;

    -- 4. Cerrar el turno en la cola (si existe)
    UPDATE cola_matricula
    SET estado_cola = 'FINALIZADO', hora_fin = NOW()
    WHERE id_estudiante = p_id_estudiante
      AND id_periodo    = p_id_periodo
      AND estado_cola IN ('EN_TURNO', 'EN_ESPERA');

    -- 5. Auditoría
    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (
        COALESCE(p_id_admin, (SELECT id_usuario FROM estudiantes WHERE id_estudiante = p_id_estudiante)),
        'MATRICULA', 'REGISTRO_COMPLETO', 'matriculas', p_id_matricula,
        CONCAT('Matrícula integral procesada. Constancia: ', v_constancia, ' | Secciones: ', p_lista_secciones)
    );

    COMMIT;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Matrícula completa procesada exitosamente. Constancia: ', v_constancia);
END proc_matricula$$

-- -----------------------------------------------------------------------------
-- sp_inscribir_curso_matricula (público, transaccional)
-- Agrega un curso a una matrícula ya existente (dentro de la ventana de matrícula).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_inscribir_curso_matricula$$
CREATE PROCEDURE sp_inscribir_curso_matricula(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE v_errno INT;
    DECLARE v_msg   TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    START TRANSACTION;
    CALL sp_i_inscribir_curso(p_id_matricula, p_id_seccion, 'MATRICULA', p_codigo_resp, p_mensaje);
    IF p_codigo_resp = 'EXITO' THEN
        COMMIT;
    ELSE
        ROLLBACK;
    END IF;
END$$

-- -----------------------------------------------------------------------------
-- sp_retirar_curso (público, transaccional)
-- Solo entre el inicio de matrícula y el fin de rectificación del período.
-- Libera la vacante y promueve la lista de espera en la misma transacción.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_retirar_curso$$
CREATE PROCEDURE sp_retirar_curso(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_retiro: BEGIN
    DECLARE v_ini    DATETIME;
    DECLARE v_fin    DATETIME;
    DECLARE v_estado VARCHAR(20);
    DECLARE v_errno  INT;
    DECLARE v_msg    TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SELECT p.inicio_matricula, p.fin_rectificacion, p.estado
    INTO   v_ini, v_fin, v_estado
    FROM   matriculas m
    JOIN   periodos_academicos p ON p.id_periodo = m.id_periodo
    WHERE  m.id_matricula = p_id_matricula;

    IF v_ini IS NULL THEN
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'La matrícula indicada no existe.';
        LEAVE proc_retiro;
    END IF;

    IF v_estado <> 'ACTIVO' OR NOW() < v_ini OR NOW() > v_fin THEN
        SET p_codigo_resp = 'FUERA_DE_PERIODO';
        SET p_mensaje     = 'El retiro de cursos no está habilitado en este momento.';
        LEAVE proc_retiro;
    END IF;

    START TRANSACTION;
    CALL sp_i_retirar_curso(p_id_matricula, p_id_seccion, p_codigo_resp, p_mensaje);
    IF p_codigo_resp = 'EXITO' THEN
        COMMIT;
    ELSE
        ROLLBACK;
    END IF;
END proc_retiro$$

-- -----------------------------------------------------------------------------
-- sp_anular_matricula (TX-06, administrador)
-- Retira todos los cursos (liberando vacantes y promoviendo las esperas),
-- cancela sus propias esperas y marca la matrícula como ANULADA.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_anular_matricula$$
CREATE PROCEDURE sp_anular_matricula(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_admin     INT UNSIGNED,
    IN  p_motivo       VARCHAR(200),
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_anular: BEGIN
    DECLARE v_estado     VARCHAR(20);
    DECLARE v_id_seccion INT UNSIGNED;
    DECLARE v_hay        TINYINT DEFAULT 1;
    DECLARE v_resp       VARCHAR(25);
    DECLARE v_msg_aux    VARCHAR(255);
    DECLARE v_errno      INT;
    DECLARE v_msg        TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_id_admin IS NULL THEN
        SET p_codigo_resp = 'ADMIN_REQUERIDO';
        SET p_mensaje     = 'Solo un administrador puede anular una matrícula.';
        LEAVE proc_anular;
    END IF;

    START TRANSACTION;

    SELECT estado INTO v_estado
    FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_estado IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'No se encontró la matrícula indicada.';
        LEAVE proc_anular;
    END IF;

    IF v_estado = 'ANULADA' THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_ANULADA';
        SET p_mensaje     = 'La matrícula ya se encuentra anulada.';
        LEAVE proc_anular;
    END IF;

    -- Primero se cancelan sus propias esperas para que no se auto-promueva
    UPDATE lista_espera_seccion
    SET estado = 'CANCELADO', fecha_atencion = NOW()
    WHERE id_matricula = p_id_matricula AND estado = 'EN_ESPERA';

    WHILE v_hay = 1 DO
        SET v_id_seccion = NULL;

        SELECT id_seccion INTO v_id_seccion
        FROM detalle_matricula
        WHERE id_matricula = p_id_matricula AND estado = 'INSCRITO'
        LIMIT 1;

        IF v_id_seccion IS NULL THEN
            SET v_hay = 0;
        ELSE
            CALL sp_i_retirar_curso(p_id_matricula, v_id_seccion, v_resp, v_msg_aux);
            IF v_resp <> 'EXITO' THEN
                ROLLBACK;
                SET p_codigo_resp = v_resp;
                SET p_mensaje     = v_msg_aux;
                LEAVE proc_anular;
            END IF;
        END IF;
    END WHILE;

    UPDATE matriculas SET estado = 'ANULADA', id_admin = p_id_admin
    WHERE id_matricula = p_id_matricula;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'MATRICULA', 'ANULAR_MATRICULA', 'matriculas', p_id_matricula,
            CONCAT('Motivo: ', COALESCE(p_motivo, 'No indicado')));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Matrícula anulada; vacantes liberadas y listas de espera procesadas.';
END proc_anular$$

-- -----------------------------------------------------------------------------
-- sp_aplicar_excepcion (TX-06, administrador)
-- Registra una excepción con número de resolución y aplica su efecto.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_aplicar_excepcion$$
CREATE PROCEDURE sp_aplicar_excepcion(
    IN  p_id_matricula   INT UNSIGNED,
    IN  p_id_admin       INT UNSIGNED,
    IN  p_tipo_excepcion VARCHAR(20),
    IN  p_numero_rd      VARCHAR(80),
    IN  p_descripcion    TEXT,
    IN  p_creditos_extra TINYINT UNSIGNED,
    OUT p_codigo_resp    VARCHAR(25),
    OUT p_mensaje        VARCHAR(255)
)
proc_exc: BEGIN
    DECLARE v_estado VARCHAR(20);
    DECLARE v_errno  INT;
    DECLARE v_msg    TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_id_admin IS NULL OR p_numero_rd IS NULL OR CHAR_LENGTH(TRIM(p_numero_rd)) = 0 THEN
        SET p_codigo_resp = 'REQUIERE_RESOLUCION';
        SET p_mensaje     = 'Debe indicar el administrador y el número de resolución.';
        LEAVE proc_exc;
    END IF;

    IF p_tipo_excepcion NOT IN ('CREDITOS_EXTRAS','CRUCE_HORARIO','CURSO_DIRIGIDO','REQUISITO_MEDICO','DEUDA_PENDIENTE') THEN
        SET p_codigo_resp = 'TIPO_INVALIDO';
        SET p_mensaje     = 'Tipo de excepción no válido.';
        LEAVE proc_exc;
    END IF;

    IF p_tipo_excepcion = 'CREDITOS_EXTRAS' AND (p_creditos_extra IS NULL OR p_creditos_extra NOT BETWEEN 1 AND 8) THEN
        SET p_codigo_resp = 'CREDITOS_INVALIDOS';
        SET p_mensaje     = 'Para CREDITOS_EXTRAS indique entre 1 y 8 créditos.';
        LEAVE proc_exc;
    END IF;

    START TRANSACTION;

    SELECT estado INTO v_estado FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_estado IS NULL OR v_estado = 'ANULADA' THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_VALIDA';
        SET p_mensaje     = 'La matrícula no existe o está anulada.';
        LEAVE proc_exc;
    END IF;

    INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
    VALUES (p_id_matricula, p_tipo_excepcion, p_numero_rd, COALESCE(p_descripcion, 'Sin descripción'), p_id_admin);

    IF p_tipo_excepcion = 'CREDITOS_EXTRAS' THEN
        UPDATE matriculas
        SET creditos_adicionales = creditos_adicionales + p_creditos_extra,
            resolucion_excep = p_numero_rd, id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'CRUCE_HORARIO' THEN
        UPDATE matriculas SET bypass_cruces = 1, resolucion_excep = p_numero_rd, id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'REQUISITO_MEDICO' THEN
        UPDATE matriculas SET bypass_medico = 1, resolucion_excep = p_numero_rd, id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'DEUDA_PENDIENTE' THEN
        UPDATE matriculas SET bypass_deuda = 1, resolucion_excep = p_numero_rd, id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    END IF;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'MATRICULA', 'APLICAR_EXCEPCION', 'matriculas', p_id_matricula,
            CONCAT(p_tipo_excepcion, ' | RD: ', p_numero_rd));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Excepción registrada y aplicada a la matrícula.';
END proc_exc$$

DELIMITER ;
