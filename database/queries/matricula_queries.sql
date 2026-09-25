-- Consultas parametrizadas de referencia: ? debe enlazarse mediante PDO.
SELECT DISTINCT s.id_seccion,c.codigo,c.nombre,c.creditos,s.codigo AS seccion,
 s.vacantes,s.vacantes_disponibles
FROM seccion s JOIN curso c ON c.id_curso=s.id_curso
JOIN curriculum cu ON cu.id_curso=c.id_curso
JOIN plan_estudio p ON p.id_plan=cu.id_plan
WHERE s.id_periodo=? AND p.id_carrera=? AND p.estado=1 AND c.estado=1;

-- Dentro de una transacción: bloquear estudiante antes de comprobar duplicados.
SELECT id_usuario FROM estudiante WHERE id_usuario=? FOR UPDATE;
SELECT id_matricula FROM matricula WHERE id_estudiante=? AND id_periodo=? AND estado='REGISTRADA';
SELECT id_seccion,vacantes_disponibles FROM seccion WHERE id_seccion=? FOR UPDATE;
INSERT INTO matricula(id_estudiante,id_periodo,codigo_matricula,fecha_matricula,total_creditos,estado)
VALUES(?,?,?,CURRENT_DATE,?,'REGISTRADA');
INSERT INTO detalle_matricula(id_matricula,id_seccion,estado) VALUES(?,?,'MATRICULADO');
UPDATE seccion SET vacantes_disponibles=vacantes_disponibles-1 WHERE id_seccion=? AND vacantes_disponibles>0;
