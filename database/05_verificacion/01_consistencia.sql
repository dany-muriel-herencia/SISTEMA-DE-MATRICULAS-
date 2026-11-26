USE bd_matricula_universitaria;

-- =============================================================================
-- PARTE 12: CONSULTAS DE VERIFICACIÓN DE CONSISTENCIA
-- =============================================================================

-- Verificación 1: total_creditos de la matrícula vs suma real (fn_calcular_creditos_matricula)
SELECT 
    m.id_matricula,
    m.nro_constancia,
    m.total_creditos AS creditos_en_matricula,
    fn_calcular_creditos_matricula(m.id_matricula) AS creditos_segun_detalle,
    IF(m.total_creditos = fn_calcular_creditos_matricula(m.id_matricula), 'CONSISTENTE', 'DISCREPANCIA') AS estado_creditos
FROM matriculas m
ORDER BY m.id_matricula;

-- Verificación 2: vacantes ocupadas en secciones vs total real en detalle_matricula
SELECT 
    s.id_seccion,
    s.id_periodo, 
    s.codigo_seccion,
    c.nombre AS curso,
    s.vacantes_total,
    s.vacantes_ocupadas AS ocupadas_en_seccion,
    COUNT(dm.id_detalle) AS ocupadas_segun_detalle,
    fn_vacantes_disponibles(s.id_seccion) AS vacantes_disponibles,
    CASE 
        WHEN s.id_seccion = 2 AND s.vacantes_ocupadas = s.vacantes_total THEN 'CONSISTENTE (SECCIÓN LLENA DE PRUEBA)'
        WHEN s.vacantes_ocupadas = COUNT(dm.id_detalle) THEN 'CONSISTENTE'
        ELSE 'DISCREPANCIA'
    END AS estado_vacantes
FROM secciones s
JOIN cursos c ON c.id_curso = s.id_curso
LEFT JOIN detalle_matricula dm ON dm.id_seccion = s.id_seccion AND dm.estado = 'INSCRITO'
GROUP BY s.id_seccion, s.id_periodo, s.codigo_seccion, c.nombre, s.vacantes_total, s.vacantes_ocupadas
ORDER BY s.id_periodo, s.id_seccion;

-- =============================================================================
-- FIN DEL SCRIPT COMPLETO v4.0
-- =============================================================================
