USE bd_matricula_universitaria;

DELIMITER $$

-- =============================================================================
-- PARTE 5: COLA 1 — TURNOS DE MATRÍCULA (TX-04)
-- Atención: primero los priorizados (promedio anterior >= fn_promedio_merito()),
-- luego por orden de llegada. La fila del período actúa como mutex de la cola.
-- =============================================================================

DROP PROCEDURE IF EXISTS sp_ingresar_cola_matricula$$
CREATE PROCEDURE sp_ingresar_cola_matricula(
    IN  p_id_estudiante INT UNSIGNED,
    IN  p_id_periodo    SMALLINT UNSIGNED,
    OUT p_posicion      SMALLINT UNSIGNED,
    OUT p_codigo_resp   VARCHAR(25),
    OUT p_mensaje       VARCHAR(255)
)
proc_cola_in: BEGIN
    DECLARE v_lock        SMALLINT UNSIGNED;
    DECLARE v_periodo_ok  INT DEFAULT 0;
    DECLARE v_id_usuario  INT UNSIGNED;
    DECLARE v_estado_acad VARCHAR(20);
    DECLARE v_prom_ant    DECIMAL(4,2);
    DECLARE v_id_cola     INT UNSIGNED;
    DECLARE v_estado_cola VARCHAR(20);
    DECLARE v_pos_actual  SMALLINT UNSIGNED;
    DECLARE v_prioridad   TINYINT(1) DEFAULT 0;
    DECLARE v_nueva_pos   SMALLINT UNSIGNED;
    DECLARE v_errno       INT;
    DECLARE v_msg         TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_posicion    = NULL;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_posicion = NULL;
    START TRANSACTION;

    SELECT id_periodo INTO v_lock
    FROM periodos_academicos WHERE id_periodo = p_id_periodo FOR UPDATE;

    IF v_lock IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'PERIODO_NO_EXISTE';
        SET p_mensaje     = 'El período académico indicado no existe.';
        LEAVE proc_cola_in;
    END IF;

    SELECT COUNT(*) INTO v_periodo_ok
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo
      AND estado = 'ACTIVO'
      AND NOW() BETWEEN inicio_matricula AND fin_matricula;

    IF v_periodo_ok = 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'PERIODO_INACTIVO';
        SET p_mensaje     = 'El período no está activo o está fuera de la fecha de matrícula.';
        LEAVE proc_cola_in;
    END IF;

    SELECT id_usuario, estado_academico, promedio_semestre_anterior
    INTO   v_id_usuario, v_estado_acad, v_prom_ant
    FROM   estudiantes
    WHERE  id_estudiante = p_id_estudiante;

    IF v_estado_acad IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTUDIANTE_NO_EXISTE';
        SET p_mensaje     = 'El estudiante no existe en el sistema.';
        LEAVE proc_cola_in;
    END IF;

    IF v_estado_acad IN ('EGRESADO', 'RETIRADO') THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTUDIANTE_NO_HABIL';
        SET p_mensaje     = CONCAT('El estudiante tiene estado "', v_estado_acad, '" y no puede ingresar a la cola.');
        LEAVE proc_cola_in;
    END IF;

    IF EXISTS (SELECT 1 FROM matriculas WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo) THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_MATRICULADO';
        SET p_mensaje     = 'El estudiante ya tiene matrícula en este período.';
        LEAVE proc_cola_in;
    END IF;

    SELECT id_cola, estado_cola, posicion
    INTO   v_id_cola, v_estado_cola, v_pos_actual
    FROM   cola_matricula
    WHERE  id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo;

    IF v_estado_cola IN ('EN_ESPERA', 'EN_TURNO') THEN
        ROLLBACK;
        SET p_posicion    = v_pos_actual;
        SET p_codigo_resp = IF(v_estado_cola = 'EN_TURNO', 'EN_TURNO', 'YA_EN_COLA');
        SET p_mensaje     = CONCAT('El estudiante ya está en la cola (estado ', v_estado_cola, ', turno N° ', v_pos_actual, ').');
        LEAVE proc_cola_in;
    END IF;

    IF v_estado_cola = 'FINALIZADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'TURNO_FINALIZADO';
        SET p_mensaje     = 'El proceso de matrícula de este estudiante ya concluyó en este período.';
        LEAVE proc_cola_in;
    END IF;

    IF v_prom_ant IS NOT NULL AND v_prom_ant >= fn_promedio_merito() THEN
        SET v_prioridad = 1;
    END IF;

    SELECT COALESCE(MAX(posicion), 0) + 1 INTO v_nueva_pos
    FROM cola_matricula WHERE id_periodo = p_id_periodo;

    IF v_id_cola IS NULL THEN
        INSERT INTO cola_matricula (id_estudiante, id_periodo, posicion, estado_cola, priorizado)
        VALUES (p_id_estudiante, p_id_periodo, v_nueva_pos, 'EN_ESPERA', v_prioridad);
        SET v_id_cola = LAST_INSERT_ID();
    ELSE
        -- Reingreso tras abandono, con nueva posición
        UPDATE cola_matricula
        SET posicion = v_nueva_pos, estado_cola = 'EN_ESPERA', priorizado = v_prioridad,
            hora_ingreso = NOW(), hora_turno = NULL, hora_fin = NULL
        WHERE id_cola = v_id_cola;
    END IF;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (v_id_usuario, 'COLA', 'INGRESO_COLA', 'cola_matricula', v_id_cola,
            CONCAT('Posición ', v_nueva_pos, ' | Priorizado: ', v_prioridad));

    COMMIT;
    SET p_posicion    = v_nueva_pos;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Ingreso exitoso a la cola con turno N° ', v_nueva_pos,
                               IF(v_prioridad = 1, ' (con prioridad por rendimiento).', '.'));
END proc_cola_in$$

DROP PROCEDURE IF EXISTS sp_atender_siguiente_cola$$
CREATE PROCEDURE sp_atender_siguiente_cola(
    IN  p_id_periodo     SMALLINT UNSIGNED,
    IN  p_max_en_turno   TINYINT UNSIGNED,
    OUT p_id_cola        INT UNSIGNED,
    OUT p_id_estudiante  INT UNSIGNED,
    OUT p_codigo_resp    VARCHAR(25),
    OUT p_mensaje        VARCHAR(255)
)
proc_atender: BEGIN
    DECLARE v_lock          SMALLINT UNSIGNED;
    DECLARE v_max           TINYINT UNSIGNED;
    DECLARE v_en_turno      INT DEFAULT 0;
    DECLARE v_id_cola       INT UNSIGNED;
    DECLARE v_id_estudiante INT UNSIGNED;
    DECLARE v_posicion      SMALLINT UNSIGNED;
    DECLARE v_errno         INT;
    DECLARE v_msg           TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_id_cola       = NULL;
        SET p_id_estudiante = NULL;
        SET p_codigo_resp   = 'ERROR_SQL';
        SET p_mensaje       = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_cola       = NULL;
    SET p_id_estudiante = NULL;
    SET v_max = COALESCE(NULLIF(p_max_en_turno, 0), 1);

    START TRANSACTION;

    SELECT id_periodo INTO v_lock
    FROM periodos_academicos WHERE id_periodo = p_id_periodo FOR UPDATE;

    IF v_lock IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'PERIODO_NO_EXISTE';
        SET p_mensaje     = 'El período académico indicado no existe.';
        LEAVE proc_atender;
    END IF;

    SELECT COUNT(*) INTO v_en_turno
    FROM cola_matricula WHERE id_periodo = p_id_periodo AND estado_cola = 'EN_TURNO';

    IF v_en_turno >= v_max THEN
        ROLLBACK;
        SET p_codigo_resp = 'TURNOS_OCUPADOS';
        SET p_mensaje     = CONCAT('Ya hay ', v_en_turno, ' estudiante(s) en turno. Máximo permitido: ', v_max, '.');
        LEAVE proc_atender;
    END IF;

    SELECT id_cola, id_estudiante, posicion
    INTO   v_id_cola, v_id_estudiante, v_posicion
    FROM   cola_matricula
    WHERE  id_periodo = p_id_periodo AND estado_cola = 'EN_ESPERA'
    ORDER BY priorizado DESC, posicion ASC
    LIMIT 1
    FOR UPDATE;

    IF v_id_cola IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'COLA_VACIA';
        SET p_mensaje     = 'No hay estudiantes en espera en la cola de matrícula.';
        LEAVE proc_atender;
    END IF;

    UPDATE cola_matricula
    SET estado_cola = 'EN_TURNO', hora_turno = NOW()
    WHERE id_cola = v_id_cola;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (NULL, 'COLA', 'LLAMAR_TURNO', 'cola_matricula', v_id_cola,
            CONCAT('Estudiante ', v_id_estudiante, ' pasa a EN_TURNO (posición ', v_posicion, ')'));

    COMMIT;
    SET p_id_cola       = v_id_cola;
    SET p_id_estudiante = v_id_estudiante;
    SET p_codigo_resp   = 'EXITO';
    SET p_mensaje       = CONCAT('Turno asignado al estudiante id: ', v_id_estudiante);
END proc_atender$$

DROP PROCEDURE IF EXISTS sp_finalizar_turno_cola$$
CREATE PROCEDURE sp_finalizar_turno_cola(
    IN  p_id_cola     INT UNSIGNED,
    OUT p_codigo_resp VARCHAR(25),
    OUT p_mensaje     VARCHAR(255)
)
proc_fin_cola: BEGIN
    DECLARE v_estado VARCHAR(20);

    UPDATE cola_matricula 
    SET estado_cola = 'FINALIZADO', hora_fin = NOW()
    WHERE id_cola     = p_id_cola
      AND estado_cola = 'EN_TURNO';

    IF ROW_COUNT() = 0 THEN
        SELECT estado_cola INTO v_estado FROM cola_matricula WHERE id_cola = p_id_cola;
        IF v_estado IS NULL THEN
            SET p_codigo_resp = 'TURNO_NO_EXISTE';
            SET p_mensaje     = 'El registro en cola especificado no existe.';
        ELSE
            SET p_codigo_resp = 'ESTADO_INVALIDO';
            SET p_mensaje     = CONCAT('El registro no se encontraba EN_TURNO (estado actual: ', v_estado, ').');
        END IF;
        LEAVE proc_fin_cola;
    END IF;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Turno en cola finalizado exitosamente.';
END proc_fin_cola$$

DROP PROCEDURE IF EXISTS sp_abandonar_cola$$
CREATE PROCEDURE sp_abandonar_cola(
    IN  p_id_estudiante INT UNSIGNED,
    IN  p_id_periodo    SMALLINT UNSIGNED,
    OUT p_codigo_resp   VARCHAR(25),
    OUT p_mensaje       VARCHAR(255)
)
proc_abandono: BEGIN
    DECLARE v_estado VARCHAR(20);

    UPDATE cola_matricula 
    SET estado_cola = 'ABANDONADO', hora_fin = NOW()
    WHERE id_estudiante = p_id_estudiante 
      AND id_periodo    = p_id_periodo
      AND estado_cola   IN ('EN_ESPERA', 'EN_TURNO');

    IF ROW_COUNT() = 0 THEN
        SELECT estado_cola INTO v_estado 
        FROM cola_matricula
        WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo;

        IF v_estado IS NULL THEN
            SET p_codigo_resp = 'NO_EN_COLA';
            SET p_mensaje     = 'El estudiante no tiene registro en la cola para este período.';
        ELSE
            SET p_codigo_resp = 'ESTADO_NO_ABANDONABLE';
            SET p_mensaje     = CONCAT('No se puede abandonar la cola en el estado actual: ', v_estado);
        END IF;
        LEAVE proc_abandono;
    END IF;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'El estudiante ha abandonado la cola de matrícula.';
END proc_abandono$$

-- Libera turnos que excedieron el tiempo permitido (ej. 15 minutos)
DROP PROCEDURE IF EXISTS sp_expirar_turnos_cola$$
CREATE PROCEDURE sp_expirar_turnos_cola(
    IN  p_id_periodo SMALLINT UNSIGNED,
    IN  p_minutos    SMALLINT UNSIGNED,
    OUT p_total      INT
)
BEGIN
    UPDATE cola_matricula
    SET estado_cola = 'ABANDONADO', hora_fin = NOW()
    WHERE id_periodo  = p_id_periodo
      AND estado_cola = 'EN_TURNO'
      AND hora_turno IS NOT NULL
      AND hora_turno < NOW() - INTERVAL p_minutos MINUTE;

    SET p_total = ROW_COUNT();
END$$

DROP PROCEDURE IF EXISTS sp_consultar_posicion_cola$$
CREATE PROCEDURE sp_consultar_posicion_cola(
    IN p_id_estudiante INT UNSIGNED,
    IN p_id_periodo    SMALLINT UNSIGNED
)
BEGIN
    SELECT 
        cm.id_cola, cm.id_estudiante, cm.id_periodo, cm.posicion, cm.estado_cola,
        cm.priorizado, cm.hora_ingreso, cm.hora_turno,
        CASE cm.estado_cola
            WHEN 'EN_ESPERA' THEN (
                SELECT COUNT(*) 
                FROM cola_matricula c2 
                WHERE c2.id_periodo = p_id_periodo 
                  AND c2.estado_cola = 'EN_ESPERA'
                  AND (
                      c2.priorizado > cm.priorizado 
                      OR (c2.priorizado = cm.priorizado AND c2.posicion < cm.posicion)
                  )
            )
            ELSE NULL
        END AS estudiantes_adelante
    FROM cola_matricula cm 
    WHERE cm.id_estudiante = p_id_estudiante 
      AND cm.id_periodo = p_id_periodo;
END$$

DELIMITER ;
