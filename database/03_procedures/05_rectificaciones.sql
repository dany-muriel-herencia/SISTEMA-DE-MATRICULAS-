USE bd_matricula_universitaria;

DELIMITER $$

-- =============================================================================
-- PARTE 8: COLA 3 — SOLICITUDES DE RECTIFICACIÓN (TX-02)
-- Flujo: el estudiante SOLICITA (queda en cola FIFO: v_rectificaciones_pendientes)
--        -> el administrador APRUEBA (ejecuta el movimiento) o RECHAZA.
-- Reemplaza al antiguo sp_rectificar_matricula (que aprobaba todo al instante).
-- La ventana de rectificación se valida al SOLICITAR.
-- =============================================================================

DROP PROCEDURE IF EXISTS sp_rectificar_matricula$$   -- obsoleto en v4

DROP PROCEDURE IF EXISTS sp_solicitar_rectificacion$$
CREATE PROCEDURE sp_solicitar_rectificacion(
    IN  p_id_matricula       INT UNSIGNED,
    IN  p_tipo_movimiento    VARCHAR(20),
    IN  p_id_seccion_origen  INT UNSIGNED,
    IN  p_id_seccion_destino INT UNSIGNED,
    OUT p_id_rectificacion   INT UNSIGNED,
    OUT p_codigo_resp        VARCHAR(25),
    OUT p_mensaje            VARCHAR(255)
)
proc_sol: BEGIN
    DECLARE v_id_periodo  SMALLINT UNSIGNED;
    DECLARE v_estado_mat  VARCHAR(20);
    DECLARE v_estado_per  VARCHAR(20);
    DECLARE v_ini         DATETIME;
    DECLARE v_fin         DATETIME;
    DECLARE v_curso_orig  SMALLINT UNSIGNED;
    DECLARE v_curso_dest  SMALLINT UNSIGNED;
    DECLARE v_errno       INT;
    DECLARE v_msg         TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_id_rectificacion = NULL;
        SET p_codigo_resp      = 'ERROR_SQL';
        SET p_mensaje          = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_rectificacion = NULL;

    IF p_tipo_movimiento NOT IN ('RETIRO', 'AGREGAR', 'CAMBIO_SECCION') THEN
        SET p_codigo_resp = 'MOVIMIENTO_INVALIDO';
        SET p_mensaje     = 'Tipo de movimiento no válido. Debe ser RETIRO, AGREGAR o CAMBIO_SECCION.';
        LEAVE proc_sol;
    END IF;

    IF (p_tipo_movimiento IN ('RETIRO', 'CAMBIO_SECCION') AND p_id_seccion_origen IS NULL)
       OR (p_tipo_movimiento IN ('AGREGAR', 'CAMBIO_SECCION') AND p_id_seccion_destino IS NULL) THEN
        SET p_codigo_resp = 'PARAMETRO_FALTANTE';
        SET p_mensaje     = 'Falta la sección de origen o de destino para el movimiento indicado.';
        LEAVE proc_sol;
    END IF;

    IF p_tipo_movimiento = 'CAMBIO_SECCION' AND p_id_seccion_origen = p_id_seccion_destino THEN
        SET p_codigo_resp = 'SECCIONES_IGUALES';
        SET p_mensaje     = 'La sección de origen y destino no pueden ser la misma.';
        LEAVE proc_sol;
    END IF;

    START TRANSACTION;

    SELECT id_periodo, estado INTO v_id_periodo, v_estado_mat
    FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_id_periodo IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_INEXISTENTE';
        SET p_mensaje     = 'No se encontró la matrícula para solicitar la rectificación.';
        LEAVE proc_sol;
    END IF;

    IF v_estado_mat NOT IN ('PENDIENTE', 'CONFIRMADA') THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula está en estado "', v_estado_mat, '" y no admite rectificaciones.');
        LEAVE proc_sol;
    END IF;

    SELECT inicio_rectificacion, fin_rectificacion, estado INTO v_ini, v_fin, v_estado_per
    FROM periodos_academicos WHERE id_periodo = v_id_periodo;

    IF v_estado_per <> 'ACTIVO' OR NOW() < v_ini OR NOW() > v_fin THEN
        ROLLBACK;
        SET p_codigo_resp = 'FUERA_DE_FECHA';
        SET p_mensaje     = 'El proceso de rectificación no se encuentra activo para este período académico.';
        LEAVE proc_sol;
    END IF;

    IF p_tipo_movimiento IN ('RETIRO', 'CAMBIO_SECCION') AND NOT EXISTS (
        SELECT 1 FROM detalle_matricula
        WHERE id_matricula = p_id_matricula AND id_seccion = p_id_seccion_origen AND estado = 'INSCRITO'
    ) THEN
        ROLLBACK;
        SET p_codigo_resp = 'NO_INSCRITO';
        SET p_mensaje     = 'El estudiante no está inscrito en la sección de origen.';
        LEAVE proc_sol;
    END IF;

    IF p_tipo_movimiento IN ('AGREGAR', 'CAMBIO_SECCION') AND NOT EXISTS (
        SELECT 1 FROM secciones WHERE id_seccion = p_id_seccion_destino AND id_periodo = v_id_periodo
    ) THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_NO_VALIDA';
        SET p_mensaje     = 'La sección de destino no existe o es de otro período.';
        LEAVE proc_sol;
    END IF;

    IF p_tipo_movimiento = 'CAMBIO_SECCION' THEN
        SELECT id_curso INTO v_curso_orig FROM secciones WHERE id_seccion = p_id_seccion_origen;
        SELECT id_curso INTO v_curso_dest FROM secciones WHERE id_seccion = p_id_seccion_destino;
        IF v_curso_orig <> v_curso_dest THEN
            ROLLBACK;
            SET p_codigo_resp = 'CURSOS_DISTINTOS';
            SET p_mensaje     = 'El cambio de sección debe ser dentro del mismo curso.';
            LEAVE proc_sol;
        END IF;
    END IF;

    -- Acta provisional única; luego se reemplaza por el formato definitivo basado en el id
    INSERT INTO rectificaciones (id_matricula, id_admin, nro_acta, estado, observaciones)
    VALUES (p_id_matricula, NULL, SUBSTRING(REPLACE(UUID(), '-', ''), 1, 32), 'SOLICITADO',
            CONCAT('Solicitud de ', p_tipo_movimiento));

    SET p_id_rectificacion = LAST_INSERT_ID();

    UPDATE rectificaciones
    SET nro_acta = CONCAT('ACTA-REC-', LPAD(p_id_rectificacion, 7, '0'))
    WHERE id_rectificacion = p_id_rectificacion;

    INSERT INTO detalle_rectificacion (id_rectificacion, id_seccion, tipo_movimiento, seccion_destino)
    VALUES (p_id_rectificacion, COALESCE(p_id_seccion_origen, p_id_seccion_destino),
            p_tipo_movimiento, p_id_seccion_destino);

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (NULL, 'RECTIFICACION', 'SOLICITAR', 'rectificaciones', p_id_rectificacion,
            CONCAT('Matrícula ', p_id_matricula, ' | ', p_tipo_movimiento));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Solicitud de rectificación registrada y en espera de atención.';
END proc_sol$$

DROP PROCEDURE IF EXISTS sp_aprobar_rectificacion$$
CREATE PROCEDURE sp_aprobar_rectificacion(
    IN  p_id_rectificacion INT UNSIGNED,
    IN  p_id_admin         INT UNSIGNED,
    OUT p_codigo_resp      VARCHAR(25),
    OUT p_mensaje          VARCHAR(255)
)
proc_aprob: BEGIN
    DECLARE v_id_matricula INT UNSIGNED;
    DECLARE v_estado       VARCHAR(20);
    DECLARE v_acta         VARCHAR(35);
    DECLARE v_tipo         VARCHAR(20);
    DECLARE v_origen       INT UNSIGNED;
    DECLARE v_destino      INT UNSIGNED;
    DECLARE v_dummy        INT UNSIGNED;
    DECLARE v_resp         VARCHAR(25) DEFAULT 'EXITO';
    DECLARE v_msg_aux      VARCHAR(255) DEFAULT '';
    DECLARE v_errno        INT;
    DECLARE v_msg          TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_id_admin IS NULL THEN
        SET p_codigo_resp = 'ADMIN_REQUERIDO';
        SET p_mensaje     = 'Solo un administrador puede aprobar rectificaciones.';
        LEAVE proc_aprob;
    END IF;

    START TRANSACTION;

    SELECT id_matricula, estado, nro_acta INTO v_id_matricula, v_estado, v_acta
    FROM rectificaciones WHERE id_rectificacion = p_id_rectificacion FOR UPDATE;

    IF v_id_matricula IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'NO_ENCONTRADA';
        SET p_mensaje     = 'La solicitud de rectificación no existe.';
        LEAVE proc_aprob;
    END IF;

    IF v_estado <> 'SOLICITADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_RESUELTA';
        SET p_mensaje     = CONCAT('La solicitud ya fue atendida (estado ', v_estado, ').');
        LEAVE proc_aprob;
    END IF;

    SELECT tipo_movimiento, id_seccion, seccion_destino INTO v_tipo, v_origen, v_destino
    FROM detalle_rectificacion WHERE id_rectificacion = p_id_rectificacion LIMIT 1;

    -- Ejecución en modo INTERNO: la ventana ya se validó al solicitar
    IF v_tipo = 'RETIRO' THEN
        CALL sp_i_retirar_curso(v_id_matricula, v_origen, v_resp, v_msg_aux);

    ELSEIF v_tipo = 'AGREGAR' THEN
        CALL sp_i_inscribir_curso(v_id_matricula, v_destino, 'INTERNO', v_resp, v_msg_aux);

    ELSE -- CAMBIO_SECCION
        -- Pre-bloqueo de ambas secciones en orden ascendente (previene deadlocks cruzados)
        SELECT id_seccion INTO v_dummy FROM secciones WHERE id_seccion = LEAST(v_origen, v_destino) FOR UPDATE;
        SELECT id_seccion INTO v_dummy FROM secciones WHERE id_seccion = GREATEST(v_origen, v_destino) FOR UPDATE;

        CALL sp_i_retirar_curso(v_id_matricula, v_origen, v_resp, v_msg_aux);
        IF v_resp = 'EXITO' THEN
            CALL sp_i_inscribir_curso(v_id_matricula, v_destino, 'INTERNO', v_resp, v_msg_aux);
        END IF;
    END IF;

    IF v_resp <> 'EXITO' THEN
        ROLLBACK;  -- deshace también el retiro de origen de un cambio de sección
        SET p_codigo_resp = v_resp;
        SET p_mensaje     = CONCAT('No se pudo aprobar (nada fue modificado): ', v_msg_aux);
        LEAVE proc_aprob;
    END IF;

    UPDATE rectificaciones
    SET estado = 'APROBADO', id_admin = p_id_admin, fecha_confirmacion = NOW()
    WHERE id_rectificacion = p_id_rectificacion;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'RECTIFICACION', 'APROBAR', 'rectificaciones', p_id_rectificacion,
            CONCAT(v_acta, ' | ', v_tipo));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Rectificación aprobada y ejecutada. Acta: ', v_acta);
END proc_aprob$$

DROP PROCEDURE IF EXISTS sp_rechazar_rectificacion$$
CREATE PROCEDURE sp_rechazar_rectificacion(
    IN  p_id_rectificacion INT UNSIGNED,
    IN  p_id_admin         INT UNSIGNED,
    IN  p_observaciones    VARCHAR(500),
    OUT p_codigo_resp      VARCHAR(25),
    OUT p_mensaje          VARCHAR(255)
)
proc_rech: BEGIN
    DECLARE v_id_matricula INT UNSIGNED;
    DECLARE v_estado       VARCHAR(20);
    DECLARE v_acta         VARCHAR(35);
    DECLARE v_errno        INT;
    DECLARE v_msg          TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_id_admin IS NULL THEN
        SET p_codigo_resp = 'ADMIN_REQUERIDO';
        SET p_mensaje     = 'Solo un administrador puede rechazar rectificaciones.';
        LEAVE proc_rech;
    END IF;

    START TRANSACTION;

    SELECT id_matricula, estado, nro_acta INTO v_id_matricula, v_estado, v_acta
    FROM rectificaciones WHERE id_rectificacion = p_id_rectificacion FOR UPDATE;

    IF v_id_matricula IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'NO_ENCONTRADA';
        SET p_mensaje     = 'La solicitud de rectificación no existe.';
        LEAVE proc_rech;
    END IF;

    IF v_estado <> 'SOLICITADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_RESUELTA';
        SET p_mensaje     = CONCAT('La solicitud ya fue atendida (estado ', v_estado, ').');
        LEAVE proc_rech;
    END IF;

    UPDATE rectificaciones
    SET estado = 'RECHAZADO', id_admin = p_id_admin, fecha_confirmacion = NOW(),
        observaciones = COALESCE(p_observaciones, 'Rechazada por la administración')
    WHERE id_rectificacion = p_id_rectificacion;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'RECTIFICACION', 'RECHAZAR', 'rectificaciones', p_id_rectificacion, v_acta);

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Solicitud rechazada. Acta: ', v_acta);
END proc_rech$$

DELIMITER ;
