USE bd_matricula_universitaria;

DELIMITER $$

-- =============================================================================
-- PARTE 9: PAGOS (TX-03) Y CIERRE DE SEMESTRE (TX-05)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- sp_registrar_pago (TX-03)
-- FOR UPDATE sobre la deuda serializa los pagos de una misma deuda. El correlativo
-- de la serie B001 se calcula con FOR UPDATE dentro de la transacción; el UNIQUE
-- uq_serie_correlativo es la red de seguridad: ante colisión se hace ROLLBACK y
-- se devuelve ERROR_SQL para que el backend reintente.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_registrar_pago$$
CREATE PROCEDURE sp_registrar_pago(
    IN  p_id_deuda        INT UNSIGNED,
    IN  p_id_admin        INT UNSIGNED,
    IN  p_monto           DECIMAL(8,2),
    IN  p_metodo          VARCHAR(20),
    IN  p_nro_operacion   VARCHAR(60),
    OUT p_codigo_resp     VARCHAR(25),
    OUT p_mensaje         VARCHAR(255)
)
proc_pago: BEGIN
    DECLARE v_id_estudiante   INT UNSIGNED;
    DECLARE v_estado_deuda    VARCHAR(20);
    DECLARE v_saldo_actual    DECIMAL(8,2);
    DECLARE v_nuevo_saldo     DECIMAL(8,2);
    DECLARE v_id_pago         INT UNSIGNED;
    DECLARE v_correlativo     INT UNSIGNED;
    DECLARE v_nuevo_estado    VARCHAR(20);
    DECLARE v_errno           INT;
    DECLARE v_msg             TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_monto IS NULL OR p_monto <= 0 THEN
        SET p_codigo_resp = 'MONTO_INVALIDO';
        SET p_mensaje     = 'El monto del pago debe ser estrictamente mayor a 0.';
        LEAVE proc_pago;
    END IF;

    IF p_metodo NOT IN ('VENTANILLA', 'BANCO', 'TARJETA', 'TRANSFERENCIA') THEN
        SET p_codigo_resp = 'METODO_INVALIDO';
        SET p_mensaje     = 'El método de pago no es válido. Debe ser VENTANILLA, BANCO, TARJETA o TRANSFERENCIA.';
        LEAVE proc_pago;
    END IF;

    IF EXISTS (SELECT 1 FROM pagos WHERE nro_operacion = p_nro_operacion) THEN
        SET p_codigo_resp = 'OPERACION_EXISTE';
        SET p_mensaje     = 'El número de operación ya fue registrado previamente en el sistema.';
        LEAVE proc_pago;
    END IF;

    START TRANSACTION;

    SELECT id_estudiante, estado
    INTO   v_id_estudiante, v_estado_deuda
    FROM   deudas_estudiante
    WHERE  id_deuda = p_id_deuda
    FOR UPDATE;

    IF v_id_estudiante IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'DEUDA_NO_EXISTE';
        SET p_mensaje     = 'La deuda indicada no existe en el sistema.';
        LEAVE proc_pago;
    END IF;

    IF v_estado_deuda = 'PAGADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'DEUDA_YA_PAGADA';
        SET p_mensaje     = 'La deuda ya se encuentra cancelada en su totalidad.';
        LEAVE proc_pago;
    END IF;

    IF v_estado_deuda = 'ANULADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'DEUDA_ANULADA';
        SET p_mensaje     = 'La deuda se encuentra anulada y no admite pagos.';
        LEAVE proc_pago;
    END IF;

    SET v_saldo_actual = fn_calcular_saldo_deuda(p_id_deuda);

    IF p_monto > v_saldo_actual THEN
        ROLLBACK;
        SET p_codigo_resp = 'MONTO_EXCEDE_SALDO';
        SET p_mensaje     = CONCAT('El monto ingresado (S/ ', p_monto, ') excede el saldo pendiente (S/ ', v_saldo_actual, ').');
        LEAVE proc_pago;
    END IF;

    INSERT INTO pagos (id_deuda, id_estudiante, id_admin, monto_pagado, metodo_pago, nro_operacion, fecha_pago)
    VALUES (p_id_deuda, v_id_estudiante, p_id_admin, p_monto, p_metodo, p_nro_operacion, NOW());

    SET v_id_pago = LAST_INSERT_ID();

    SET v_nuevo_saldo = fn_calcular_saldo_deuda(p_id_deuda);

    IF v_nuevo_saldo <= 0.00 THEN
        SET v_nuevo_estado = 'PAGADO';
    ELSE
        SET v_nuevo_estado = 'FRACCIONADO';
    END IF;

    UPDATE deudas_estudiante SET estado = v_nuevo_estado WHERE id_deuda = p_id_deuda;

    SELECT COALESCE(MAX(correlativo), 100) + 1
    INTO   v_correlativo
    FROM   comprobantes
    WHERE  serie = 'B001'
    FOR UPDATE;

    INSERT INTO comprobantes (id_pago, serie, correlativo, tipo_comprobante, monto_total, fecha_emision)
    VALUES (v_id_pago, 'B001', v_correlativo, 'BOLETA', p_monto, NOW());

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (
        COALESCE(p_id_admin, (SELECT id_usuario FROM estudiantes WHERE id_estudiante = v_id_estudiante)),
        'FINANZAS', 'REGISTRAR_PAGO', 'pagos', v_id_pago,
        CONCAT('Pago S/ ', p_monto, ' | Op: ', p_nro_operacion, ' | Comprobante B001-', LPAD(v_correlativo, 6, '0'), ' | Estado deuda: ', v_nuevo_estado)
    );

    COMMIT;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Pago registrado correctamente. Comprobante: B001-', LPAD(v_correlativo, 6, '0'), ' (Saldo restante: S/ ', v_nuevo_saldo, ')');
END proc_pago$$

-- -----------------------------------------------------------------------------
-- sp_cerrar_semestre_academico (TX-05)
-- Promedio del semestre, créditos aprobados y promedio ponderado acumulado; cierra
-- las colas del período (turnos y listas de espera) y marca el período CERRADO.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_cerrar_semestre_academico$$
CREATE PROCEDURE sp_cerrar_semestre_academico(
    IN  p_id_periodo         SMALLINT UNSIGNED,
    OUT p_total_actualizados INT,
    OUT p_mensaje            VARCHAR(255)
)
proc_cierre: BEGIN
    DECLARE v_estado_periodo VARCHAR(20);
    DECLARE v_errno          INT;
    DECLARE v_msg            TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_total_actualizados = 0;
        SET p_mensaje = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_total_actualizados = 0;
    START TRANSACTION;

    SELECT estado INTO v_estado_periodo
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo
    FOR UPDATE;

    IF v_estado_periodo IS NULL THEN
        ROLLBACK;
        SET p_mensaje = 'El período académico indicado no existe.';
        LEAVE proc_cierre;
    END IF;

    IF v_estado_periodo = 'CERRADO' THEN
        ROLLBACK;
        SET p_mensaje = 'El período académico ya se encuentra cerrado.';
        LEAVE proc_cierre;
    END IF;

    UPDATE estudiantes e
    JOIN (
        SELECT 
            m.id_estudiante,
            ROUND(SUM(dm.nota_final * c.creditos) / NULLIF(SUM(c.creditos), 0), 2) AS pps_calculado,
            SUM(IF(dm.nota_final >= fn_nota_minima(), c.creditos, 0)) AS creditos_aprobados_ciclo
        FROM matriculas m
        JOIN detalle_matricula dm ON dm.id_matricula = m.id_matricula
        JOIN secciones s ON s.id_seccion = dm.id_seccion
        JOIN cursos c ON c.id_curso = s.id_curso
        WHERE m.id_periodo = p_id_periodo
          AND dm.estado = 'INSCRITO'
          AND dm.nota_final IS NOT NULL
          AND m.estado != 'ANULADA'
        GROUP BY m.id_estudiante
    ) res ON e.id_estudiante = res.id_estudiante
    SET 
        e.promedio_semestre_anterior = res.pps_calculado,
        e.creditos_aprobados = e.creditos_aprobados + res.creditos_aprobados_ciclo;

    SET p_total_actualizados = ROW_COUNT();

    -- Promedio ponderado acumulado (todo el historial) de los matriculados del período
    UPDATE estudiantes e
    JOIN (
        SELECT 
            m.id_estudiante,
            ROUND(SUM(dm.nota_final * c.creditos) / NULLIF(SUM(c.creditos), 0), 2) AS pp
        FROM matriculas m
        JOIN detalle_matricula dm ON dm.id_matricula = m.id_matricula
        JOIN secciones s ON s.id_seccion = dm.id_seccion
        JOIN cursos c ON c.id_curso = s.id_curso
        WHERE dm.estado = 'INSCRITO'
          AND dm.nota_final IS NOT NULL
          AND m.estado != 'ANULADA'
          AND m.id_estudiante IN (SELECT id_estudiante FROM matriculas WHERE id_periodo = p_id_periodo)
        GROUP BY m.id_estudiante
    ) h ON h.id_estudiante = e.id_estudiante
    SET e.promedio_ponderado = h.pp
    WHERE h.pp IS NOT NULL;

    -- Cierre de las colas del período
    UPDATE cola_matricula
    SET estado_cola = 'ABANDONADO', hora_fin = NOW()
    WHERE id_periodo = p_id_periodo AND estado_cola IN ('EN_ESPERA', 'EN_TURNO');

    UPDATE lista_espera_seccion le
    JOIN secciones s ON s.id_seccion = le.id_seccion
    SET le.estado = 'EXPIRADO', le.fecha_atencion = NOW()
    WHERE s.id_periodo = p_id_periodo AND le.estado = 'EN_ESPERA';

    UPDATE periodos_academicos SET estado = 'CERRADO' WHERE id_periodo = p_id_periodo;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (NULL, 'ACADEMICO', 'CIERRE_SEMESTRE', 'periodos_academicos', p_id_periodo,
            CONCAT('Cierre formal del período id ', p_id_periodo, '. Estudiantes actualizados: ', p_total_actualizados));

    COMMIT;

    SET p_mensaje = CONCAT('Semestre académico cerrado exitosamente. Estudiantes actualizados: ', p_total_actualizados);
END proc_cierre$$

DELIMITER ;
