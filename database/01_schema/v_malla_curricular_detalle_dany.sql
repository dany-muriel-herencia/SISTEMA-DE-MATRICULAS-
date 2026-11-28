USE bd_matricula_universitaria;

-- =============================================================================
-- VISTA 02: v_malla_curricular_detalle (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-04 (M04: Gestión Académica y Malla Curricular)
-- Devuelve los cursos de cada plan de estudios agrupados por ciclo (I al X) con
-- créditos, horas y sus prerrequisitos concatenados vía GROUP_CONCAT.
-- Compatible con ONLY_FULL_GROUP_BY en MySQL 8.0+ / MariaDB 10.5+.
-- =============================================================================

CREATE OR REPLACE VIEW v_malla_curricular_detalle AS -- Dany
SELECT
    mc.id_malla,
    f.id_facultad,
    f.codigo_facultad,
    f.nombre AS facultad,
    ep.id_escuela,
    ep.codigo_escuela,
    ep.nombre AS escuela,
    pe.id_plan,
    pe.codigo_plan,
    pe.nombre_plan,
    pe.anio_vigencia,
    pe.creditos_totales AS creditos_totales_plan,
    pe.estado AS estado_plan,
    mc.ciclo,
    CASE mc.ciclo
        WHEN 1  THEN 'I'
        WHEN 2  THEN 'II'
        WHEN 3  THEN 'III'
        WHEN 4  THEN 'IV'
        WHEN 5  THEN 'V'
        WHEN 6  THEN 'VI'
        WHEN 7  THEN 'VII'
        WHEN 8  THEN 'VIII'
        WHEN 9  THEN 'IX'
        WHEN 10 THEN 'X'
        ELSE CAST(mc.ciclo AS CHAR)
    END AS ciclo_romano,
    c.id_curso,
    c.codigo_curso,
    c.nombre AS curso,
    c.creditos,
    c.horas_teoria,
    c.horas_practica,
    (c.horas_teoria + c.horas_practica) AS total_horas,
    c.tipo,
    c.activo AS curso_activo,
    COUNT(pr.id_curso_req) AS total_prerrequisitos,
    COALESCE(
        GROUP_CONCAT(DISTINCT cr.codigo_curso ORDER BY cr.codigo_curso SEPARATOR ', '),
        'NINGUNO'
    ) AS prerrequisitos_codigos,
    COALESCE(
        GROUP_CONCAT(DISTINCT CONCAT(cr.codigo_curso, ' - ', cr.nombre) ORDER BY cr.codigo_curso SEPARATOR ' | '),
        'Sin prerrequisitos'
    ) AS prerrequisitos_detalle
FROM malla_curricular mc
INNER JOIN planes_estudio pe ON pe.id_plan = mc.id_plan
INNER JOIN escuelas_profesionales ep ON ep.id_escuela = pe.id_escuela
INNER JOIN facultades f ON f.id_facultad = ep.id_facultad
INNER JOIN cursos c ON c.id_curso = mc.id_curso
LEFT JOIN prerrequisitos pr ON pr.id_curso = c.id_curso
LEFT JOIN cursos cr ON cr.id_curso = pr.id_curso_req
GROUP BY
    mc.id_malla,
    f.id_facultad,
    f.codigo_facultad,
    f.nombre,
    ep.id_escuela,
    ep.codigo_escuela,
    ep.nombre,
    pe.id_plan,
    pe.codigo_plan,
    pe.nombre_plan,
    pe.anio_vigencia,
    pe.creditos_totales,
    pe.estado,
    mc.ciclo,
    c.id_curso,
    c.codigo_curso,
    c.nombre,
    c.creditos,
    c.horas_teoria,
    c.horas_practica,
    c.tipo,
    c.activo;

-- Pruebas rápidas (Seed v4.0):
-- SELECT ciclo_romano, codigo_curso, curso, creditos, total_horas, prerrequisitos_codigos
-- FROM v_malla_curricular_detalle WHERE id_plan = 1 ORDER BY ciclo, codigo_curso;
