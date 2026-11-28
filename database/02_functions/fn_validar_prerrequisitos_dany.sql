USE bd_matricula_universitaria;

-- =============================================================================
-- FUNCIÓN 01: fn_validar_prerrequisitos (Autor: Dany Ronaldo Muriel Herencia)
-- Issues: ISSUE-04 (Gestión Curricular) / ISSUE-05 (Motor de Matrícula)
-- Fusiona validación de pertenencia al plan curricular + prerrequisitos aprobados.
-- Retorna 1 si el curso pertenece al id_plan del estudiante y cumple todos los
-- prerrequisitos (nota >= 10.50 en período CERRADO o CONVALIDADO). Retorna 0 si no.
-- =============================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS fn_validar_prerrequisitos$$
CREATE FUNCTION fn_validar_prerrequisitos(
    p_id_estudiante INT UNSIGNED,
    p_id_curso      SMALLINT UNSIGNED
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id_plan          SMALLINT UNSIGNED DEFAULT NULL;
    DECLARE v_en_plan          INT DEFAULT 0;
    DECLARE v_total_prereq     INT DEFAULT 0;
    DECLARE v_prereq_cumplidos INT DEFAULT 0;

    -- 1) Obtener el plan curricular del estudiante
    SELECT id_plan INTO v_id_plan
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_id_plan IS NULL THEN
        RETURN 0;
    END IF;

    -- 2) Verificar que el curso pertenezca a su malla curricular
    SELECT COUNT(*) INTO v_en_plan
    FROM malla_curricular
    WHERE id_plan = v_id_plan
      AND id_curso = p_id_curso;

    IF v_en_plan = 0 THEN
        RETURN 0;
    END IF;

    -- 3) Verificar cuántos prerrequisitos exige el curso
    SELECT COUNT(*) INTO v_total_prereq
    FROM prerrequisitos
    WHERE id_curso = p_id_curso;

    IF v_total_prereq = 0 THEN
        RETURN 1;
    END IF;

    -- 4) Contar prerrequisitos aprobados en períodos cerrados o convalidados
    SELECT COUNT(DISTINCT pr.id_curso_req) INTO v_prereq_cumplidos
    FROM prerrequisitos pr
    JOIN secciones s            ON s.id_curso = pr.id_curso_req
    JOIN detalle_matricula dm   ON dm.id_seccion = s.id_seccion
    JOIN matriculas m           ON m.id_matricula = dm.id_matricula
    JOIN periodos_academicos pa ON pa.id_periodo = m.id_periodo
    WHERE pr.id_curso = p_id_curso
      AND m.id_estudiante = p_id_estudiante
      AND m.estado != 'ANULADA'
      AND (
            (dm.estado = 'INSCRITO'
             AND dm.nota_final IS NOT NULL
             AND dm.nota_final >= 10.50
             AND pa.estado = 'CERRADO')
            OR dm.estado = 'CONVALIDADO'
          );

    RETURN IF(v_prereq_cumplidos >= v_total_prereq, 1, 0);
END$$

DELIMITER ;

-- Prueba rápida (Seed v4.0):
-- SELECT fn_validar_prerrequisitos(1, 10) AS est1_cumple_bd2; -- Esperado: 1 (Juan Pérez aprobó BD I con 16.50)
-- SELECT fn_validar_prerrequisitos(3, 10) AS est3_cumple_bd2; -- Esperado: 0 (Carlos Mamani desaprobó BD I con 09.50)
