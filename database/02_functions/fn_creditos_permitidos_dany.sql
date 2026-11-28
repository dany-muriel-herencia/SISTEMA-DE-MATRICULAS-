USE bd_matricula_universitaria;

-- =============================================================================
-- FUNCIÓN 03: fn_creditos_permitidos (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-05 (Motor de Matrícula)
-- Fusiona límite base (22) + créditos adicionales (+4 = 26) si el estudiante
-- alcanzó promedio_semestre_anterior >= 14.00 o cuenta con resolución vigente de
-- AMPLIACION_CREDITOS dentro del mismo año del período académico.
-- =============================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS fn_creditos_permitidos$$
CREATE FUNCTION fn_creditos_permitidos( 
    p_id_estudiante INT UNSIGNED,
    p_id_periodo    SMALLINT UNSIGNED
)
RETURNS TINYINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_existe   INT DEFAULT 0;
    DECLARE v_promedio DECIMAL(4,2) DEFAULT NULL;
    DECLARE v_anio     YEAR DEFAULT NULL;

    SELECT COUNT(*), MAX(promedio_semestre_anterior)
    INTO v_existe, v_promedio
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_existe = 0 THEN
        RETURN 0;
    END IF;

    -- Mérito académico (tercio superior >= 14.00)
    IF v_promedio IS NOT NULL AND v_promedio >= 14.00 THEN
        RETURN 26;
    END IF;

    -- Ampliación de créditos por resolución vigente en el año del período
    SELECT anio INTO v_anio
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo;

    IF v_anio IS NOT NULL AND EXISTS (
        SELECT 1
        FROM resoluciones_estudiante
        WHERE id_estudiante = p_id_estudiante
          AND tipo_resolucion = 'AMPLIACION_CREDITOS'
          AND fecha_efecto <= CURDATE()
          AND YEAR(fecha_efecto) = v_anio
    ) THEN
        RETURN 26;
    END IF;

    RETURN 22;
END$$

DELIMITER ;

-- Prueba rápida (Seed v4.0):
-- SELECT fn_creditos_permitidos(1, 2) AS cred_est1_merito; -- Esperado: 26 (promedio anterior 16.20 >= 14.00)
-- SELECT fn_creditos_permitidos(3, 2) AS cred_est3_normal; -- Esperado: 22 (promedio anterior 12.80 < 14.00)
