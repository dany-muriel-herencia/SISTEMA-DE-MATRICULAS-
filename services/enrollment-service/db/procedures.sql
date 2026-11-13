-- Procedimientos Almacenados Transaccionales ACID (Motor v3.0)
USE sgau_enrollment;

DELIMITER $$

-- Inscribir sección con reserva atómica y bloqueo ordenado
CREATE PROCEDURE sp_inscribir_curso(
    IN p_id_matricula INT,
    IN p_id_seccion INT,
    IN p_creditos INT,
    OUT p_resultado VARCHAR(100)
)
BEGIN
    DECLARE v_creditos_actuales INT;
    DECLARE v_creditos_extra INT;
    DECLARE v_limite_base INT DEFAULT 22;

    -- Bloqueo canónico de la matrícula
    SELECT total_creditos, creditos_adicionales INTO v_creditos_actuales, v_creditos_extra
    FROM matriculas
    WHERE id_matricula = p_id_matricula
    FOR UPDATE;

    IF (v_creditos_actuales + p_creditos) > (v_limite_base + v_creditos_extra) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Exceso del límite de créditos permitidos';
    END IF;

    -- Inserción de detalle
    INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, origen)
    VALUES (p_id_matricula, p_id_seccion, 'INSCRITO', 'WEB');

    -- Actualización de créditos totales
    UPDATE matriculas
    SET total_creditos = total_creditos + p_creditos
    WHERE id_matricula = p_id_matricula;

    SET p_resultado = 'OK_INSCRITO';
END$$

-- Confirmar matrícula con emisión de constancia
CREATE PROCEDURE sp_confirmar_matricula(
    IN p_id_matricula INT,
    OUT p_nro_constancia VARCHAR(50)
)
BEGIN
    DECLARE v_anio INT;
    SET v_anio = YEAR(NOW());

    UPDATE matriculas
    SET estado = 'CONFIRMADA',
        nro_constancia = CONCAT('CONST-', v_anio, '-', LPAD(p_id_matricula, 6, '0')),
        fecha_matricula = NOW()
    WHERE id_matricula = p_id_matricula;

    SELECT nro_constancia INTO p_nro_constancia
    FROM matriculas
    WHERE id_matricula = p_id_matricula;
END$$

DELIMITER ;

