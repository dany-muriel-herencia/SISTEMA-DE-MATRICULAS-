USE bd_matricula_universitaria;

-- =============================================================================
-- FUNCIÓN 04: fn_calcular_creditos_matricula (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-05 (Motor de Matrícula)
-- Suma en tiempo real los créditos de todas las asignaturas con estado INSCRITO
-- en detalle_matricula para validar límite de carga y auditar total_creditos.
-- =============================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS fn_calcular_creditos_matricula$$
CREATE FUNCTION fn_calcular_creditos_matricula( 
    p_id_matricula INT UNSIGNED
)
RETURNS SMALLINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_creditos SMALLINT UNSIGNED DEFAULT 0;

    SELECT COALESCE(SUM(c.creditos), 0)
    INTO v_total_creditos
    FROM detalle_matricula dm
    JOIN secciones s ON s.id_seccion = dm.id_seccion
    JOIN cursos c    ON c.id_curso = s.id_curso
    WHERE dm.id_matricula = p_id_matricula
      AND dm.estado = 'INSCRITO';

    RETURN v_total_creditos;
END$$

DELIMITER ;

-- Prueba rápida (Seed v4.0):
-- SELECT fn_calcular_creditos_matricula(101) AS cred_mat101; -- Esperado: 11 (4+4+3)
