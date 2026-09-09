-- =============================================================================
-- CONSULTAS SQL EXPLÍCITAS DE REFERENCIA PARA EL SISTEMA DE MATRÍCULA
-- Acceso a datos directo sin ORM
-- =============================================================================

-- 1. Obtener oferta académica con vacantes para un periodo y carrera
SELECT 
  s.id AS seccion_id,
  c.codigo AS curso_codigo,
  c.nombre AS curso_nombre,
  c.creditos,
  s.letra_seccion,
  s.capacidad_maxima,
  s.vacantes_disponibles,
  CONCAT(u.nombre, ' ', u.apellido) AS docente_nombre,
  h.dia_semana,
  h.hora_inicio,
  h.hora_fin,
  a.codigo AS aula_codigo,
  a.pabellon
FROM secciones s
INNER JOIN cursos c ON s.curso_id = c.id
INNER JOIN plan_cursos pc ON pc.curso_id = c.id
INNER JOIN planes_estudio pe ON pc.plan_estudio_id = pe.id
LEFT JOIN usuarios u ON s.docente_id = u.id
LEFT JOIN horarios h ON h.seccion_id = s.id
LEFT JOIN aulas a ON h.aula_id = a.id
WHERE s.periodo_id = ? 
  AND pe.carrera_id = ?
ORDER BY pc.ciclo ASC, c.nombre ASC, s.letra_seccion ASC;

-- 2. Validar vacante y bloquear fila para actualización (Concurrencia segura)
SELECT id, vacantes_disponibles 
FROM secciones 
WHERE id = ? 
FOR UPDATE;

-- 3. Registrar cabecera de matrícula
INSERT INTO matriculas (estudiante_id, periodo_id, codigo_matricula, total_creditos, estado)
VALUES (?, ?, ?, ?, 'REGISTRADA');

-- 4. Registrar detalle de cursos en matrícula
INSERT INTO matricula_detalles (matricula_id, seccion_id, creditos, estado_curso)
VALUES (?, ?, ?, 'MATRICULADO');

-- 5. Decrementar vacante de sección
UPDATE secciones 
SET vacantes_disponibles = vacantes_disponibles - 1 
WHERE id = ? AND vacantes_disponibles > 0;
