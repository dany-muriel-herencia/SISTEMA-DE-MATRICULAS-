-- Datos académicos de ejemplo para el esquema actual. No crea cuentas ni contraseñas.
USE sgau;
START TRANSACTION;
INSERT INTO facultad(id_facultad,nombre,descripcion,decano)
 VALUES(1,'Facultad de Ingeniería','Datos de ejemplo',NULL)
 ON DUPLICATE KEY UPDATE id_facultad=id_facultad;
INSERT INTO escuela(id_escuela,id_facultad,nombre,descripcion,director)
 VALUES(1,1,'Escuela de Informática y Sistemas',NULL,NULL)
 ON DUPLICATE KEY UPDATE id_escuela=id_escuela;
INSERT INTO carrera(id_carrera,id_escuela,nombre,codigo,duracion,estado)
 VALUES(1,1,'Ingeniería en Informática y Sistemas','ESIS',10,1)
 ON DUPLICATE KEY UPDATE id_carrera=id_carrera;
INSERT INTO plan_estudio(id_plan,id_carrera,nombre,fecha_inicio,fecha_fin,estado)
 VALUES(1,1,'Plan 2026','2026-01-01',NULL,1)
 ON DUPLICATE KEY UPDATE id_plan=id_plan;
INSERT INTO curso(id_curso,nombre,codigo,creditos,horas_teoria,horas_practica,ciclo,estado)
 VALUES(1,'Programación I','IS-101',4,2,4,'I',1),(2,'Estructuras de datos','IS-201',4,2,4,'II',1)
 ON DUPLICATE KEY UPDATE id_curso=id_curso;
INSERT INTO curriculum(id_curriculum,id_plan,id_curso,ciclo,obligatorio)
 VALUES(1,1,1,'I',1),(2,1,2,'II',1)
 ON DUPLICATE KEY UPDATE id_curriculum=id_curriculum;
INSERT INTO prerequisito(id_prerequisito,id_curso,id_curso_requerido) VALUES(1,2,1)
 ON DUPLICATE KEY UPDATE id_prerequisito=id_prerequisito;
INSERT INTO periodo_academico(id_periodo,nombre,fecha_inicio,fecha_fin,fecha_matricula_inicio,fecha_matricula_fin,estado)
 VALUES(1,'2026-II','2026-08-01','2026-12-31','2026-07-01','2026-07-31','CERRADO')
 ON DUPLICATE KEY UPDATE id_periodo=id_periodo;
COMMIT;
