USE bd_matricula_universitaria;

-- Vistas de consulta para el backend
CREATE OR REPLACE VIEW v_cola_matricula AS
SELECT
    cm.id_cola, cm.id_periodo, p.codigo_periodo, cm.posicion, cm.priorizado, cm.estado_cola,
    e.id_estudiante, e.codigo_universitario,
    CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
    e.promedio_semestre_anterior,
    cm.hora_ingreso, cm.hora_turno, cm.hora_fin,
    TIMESTAMPDIFF(MINUTE, cm.hora_ingreso, COALESCE(cm.hora_turno, NOW())) AS minutos_espera
FROM cola_matricula cm
JOIN periodos_academicos p ON p.id_periodo = cm.id_periodo
JOIN estudiantes e         ON e.id_estudiante = cm.id_estudiante
JOIN usuarios u            ON u.id_usuario = e.id_usuario;

CREATE OR REPLACE VIEW v_lista_espera AS
SELECT
    le.id_espera, le.id_seccion, c.codigo_curso, c.nombre AS curso, s.codigo_seccion,
    le.id_matricula, e.codigo_universitario,
    CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
    le.posicion, le.estado, le.fecha_registro, le.fecha_atencion
FROM lista_espera_seccion le
JOIN secciones s   ON s.id_seccion = le.id_seccion
JOIN cursos c      ON c.id_curso = s.id_curso
JOIN matriculas m  ON m.id_matricula = le.id_matricula
JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
JOIN usuarios u    ON u.id_usuario = e.id_usuario;

CREATE OR REPLACE VIEW v_rectificaciones_pendientes AS
SELECT
    r.id_rectificacion, r.nro_acta, r.fecha_inicio,
    m.id_matricula, m.id_periodo, p.codigo_periodo,
    e.id_estudiante, e.codigo_universitario,
    CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
    dr.tipo_movimiento, dr.id_seccion AS seccion_origen, dr.seccion_destino,
    ROW_NUMBER() OVER (PARTITION BY m.id_periodo ORDER BY r.fecha_inicio, r.id_rectificacion) AS orden_cola
FROM rectificaciones r
JOIN detalle_rectificacion dr ON dr.id_rectificacion = r.id_rectificacion
JOIN matriculas m             ON m.id_matricula = r.id_matricula
JOIN periodos_academicos p    ON p.id_periodo = m.id_periodo
JOIN estudiantes e            ON e.id_estudiante = m.id_estudiante
JOIN usuarios u               ON u.id_usuario = e.id_usuario
WHERE r.estado = 'SOLICITADO';
