USE bd_matricula_universitaria;

-- =============================================================================
-- FUNCIÓN 02: fn_detectar_cruce_horario (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-05 (Motor de Matrícula / Semáforo de Cruces CU-27)
-- Detecta solapamiento en el mismo día (A1 < B2 AND A2 > B1) entre los bloques
-- de p_id_seccion y las secciones ya INSCRITO en p_id_matricula.
-- Retorna 1 si existe cruce de horario, 0 si está libre.
-- =============================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS fn_detectar_cruce_horario$$
CREATE FUNCTION fn_detectar_cruce_horario( 
    p_id_matricula INT UNSIGNED,
    p_id_seccion   INT UNSIGNED
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_cruce INT DEFAULT 0;

    IF NOT EXISTS (SELECT 1 FROM matriculas WHERE id_matricula = p_id_matricula) THEN
        RETURN 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM secciones WHERE id_seccion = p_id_seccion) THEN
        RETURN 0;
    END IF;

    SELECT COUNT(*) INTO v_cruce
    FROM horarios_seccion h_nueva
    JOIN horarios_seccion h_existente
        ON h_nueva.dia_semana = h_existente.dia_semana
       AND h_nueva.hora_inicio < h_existente.hora_fin
       AND h_nueva.hora_fin > h_existente.hora_inicio
    JOIN detalle_matricula dm
        ON dm.id_seccion = h_existente.id_seccion
    WHERE h_nueva.id_seccion = p_id_seccion
      AND dm.id_matricula = p_id_matricula
      AND dm.estado = 'INSCRITO'
      AND h_existente.id_seccion != p_id_seccion;

    RETURN IF(v_cruce > 0, 1, 0);
END$$

DELIMITER ;

-- Prueba rápida (Seed v4.0):
-- SELECT fn_detectar_cruce_horario(101, 1) AS sin_cruce;
