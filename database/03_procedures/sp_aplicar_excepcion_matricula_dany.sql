USE bd_matricula_universitaria;

-- =============================================================================
-- PROCEDIMIENTO 05: sp_aplicar_excepcion_matricula (Autor: Dany Ronaldo Muriel Herencia)
-- Issues: ISSUE-05 / ISSUE-07 (M05 / M06: TX-06 Excepciones con Resolución)
-- Registra una Resolución Directoral/Decanal en excepciones_matricula y aplica
-- su beneficio en matriculas (créditos extra, cruce, médico, deuda o dirigido).
-- =============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_aplicar_excepcion_matricula$$
CREATE PROCEDURE sp_aplicar_excepcion_matricula( 
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

    IF p_tipo_excepcion NOT IN ('CREDITOS_EXTRAS', 'CRUCE_HORARIO', 'CURSO_DIRIGIDO', 'REQUISITO_MEDICO', 'DEUDA_PENDIENTE') THEN
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

    SELECT estado INTO v_estado
    FROM matriculas
    WHERE id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_estado IS NULL OR v_estado = 'ANULADA' THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_VALIDA';
        SET p_mensaje     = 'La matrícula no existe o está anulada.';
        LEAVE proc_exc;
    END IF;

    INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
    VALUES (p_id_matricula, p_tipo_excepcion, TRIM(p_numero_rd), COALESCE(p_descripcion, 'Sin descripción'), p_id_admin);

    IF p_tipo_excepcion = 'CREDITOS_EXTRAS' THEN
        UPDATE matriculas
        SET creditos_adicionales = creditos_adicionales + p_creditos_extra,
            resolucion_excep     = TRIM(p_numero_rd),
            id_admin             = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'CRUCE_HORARIO' THEN
        UPDATE matriculas
        SET bypass_cruces = 1, resolucion_excep = TRIM(p_numero_rd), id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'REQUISITO_MEDICO' THEN
        UPDATE matriculas
        SET bypass_medico = 1, resolucion_excep = TRIM(p_numero_rd), id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'DEUDA_PENDIENTE' THEN
        UPDATE matriculas
        SET bypass_deuda = 1, resolucion_excep = TRIM(p_numero_rd), id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;  
    ELSEIF p_tipo_excepcion = 'CURSO_DIRIGIDO' THEN
        UPDATE matriculas
        SET tipo_matricula = 'ESPECIAL', resolucion_excep = TRIM(p_numero_rd), id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    END IF;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'MATRICULA', 'APLICAR_EXCEPCION', 'matriculas', p_id_matricula,
            CONCAT(p_tipo_excepcion, ' | RD: ', TRIM(p_numero_rd)));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Excepción registrada y aplicada a la matrícula.';
END proc_exc$$

DELIMITER ;

-- Prueba rápida (Seed v4.0):
-- CALL sp_aplicar_excepcion_matricula(101, 1, 'CRUCE_HORARIO', 'RD-099-2026-FAIN', 'Autorización decanal de cruce', 0, @cod, @msg);
-- SELECT @cod, @msg;
