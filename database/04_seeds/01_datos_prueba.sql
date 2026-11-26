USE bd_matricula_universitaria;

-- =============================================================================
-- PARTE 11: CARGA MASIVA DE DATOS (SEED COHERENTE SIN TRIGGERS)
-- Como no hay triggers, total_creditos y vacantes_ocupadas del historial se
-- cargan EXPLÍCITOS y coinciden con el detalle (ver verificaciones al final).
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Roles y Usuarios
INSERT INTO roles (id_rol, nombre_rol, descripcion) VALUES
(1, 'ADMINISTRADOR', 'Personal de Dirección de Asuntos Académicos y OTI'),
(2, 'DOCENTE',       'Catedrático responsable de dictado y evaluación'),
(3, 'ESTUDIANTE',    'Alumno de pregrado con acceso al portal de matrícula');

INSERT INTO usuarios (id_usuario, codigo_usuario, email_institucional, password_hash, nombres, apellidos, dni, id_rol, estado) VALUES
(1,  'ADM-001', 'admin.siga@unjbg.edu.pe',     SHA2('Admin123*', 256), 'Carlos',    'Vargas Luna',       '00458921', 1, 'ACTIVO'),
(2,  'ADM-002', 'mflores.admin@unjbg.edu.pe', SHA2('Admin123*', 256), 'Mariluz',   'Quispe Mamani',    '00489912', 1, 'ACTIVO'),
(3,  'ADM-003', 'soporte.oti@unjbg.edu.pe',    SHA2('Admin123*', 256), 'Jorge',     'Albarracín Tapia', '00412389', 1, 'ACTIVO'),
(4,  'DOC-201', 'pcalizaya@unjbg.edu.pe',      SHA2('Doc2026*', 256),  'Patrick',   'Calizaya Condori', '41235689', 2, 'ACTIVO'),
(5,  'DOC-202', 'mflores@unjbg.edu.pe',        SHA2('Doc2026*', 256),  'Manuel',    'Flores Chura',     '40568923', 2, 'ACTIVO'),
(6,  'DOC-203', 'rcalizaya@unjbg.edu.pe',      SHA2('Doc2026*', 256),  'Rosa',      'Calizaya Mamani',  '43567891', 2, 'ACTIVO'),
(7,  'DOC-204', 'econdori@unjbg.edu.pe',       SHA2('Doc2026*', 256),  'Erbert',    'Condori Quispe',   '42118902', 2, 'ACTIVO'),
(8,  'DOC-205', 'gparedes@unjbg.edu.pe',       SHA2('Doc2026*', 256),  'Guido',     'Paredes Aguilar',  '44890123', 2, 'ACTIVO'),
(9,  'DOC-206', 'acastro@unjbg.edu.pe',        SHA2('Doc2026*', 256),  'Ana',       'Castro Ticona',    '45901234', 2, 'ACTIVO'),
(10, 'DOC-207', 'hvaldivia@unjbg.edu.pe',      SHA2('Doc2026*', 256),  'Hugo',      'Valdivia Cárdenas','46012345', 2, 'ACTIVO'),
(11, '2024-119001', 'est.jperez@unjbg.edu.pe',     SHA2('Est2026*', 256), 'Juan',      'Pérez Quispe',      '72345671', 3, 'ACTIVO'),
(12, '2024-119002', 'est.aquispe@unjbg.edu.pe',    SHA2('Est2026*', 256), 'Ana',       'Quispe Ticona',     '72345672', 3, 'ACTIVO'),
(13, '2024-119003', 'est.cmamani@unjbg.edu.pe',    SHA2('Est2026*', 256), 'Carlos',    'Mamani Ramos',      '72345673', 3, 'ACTIVO'),
(14, '2024-119004', 'est.mchura@unjbg.edu.pe',     SHA2('Est2026*', 256), 'María',     'Chura Choque',      '72345674', 3, 'ACTIVO'),
(15, '2024-119005', 'est.dcaceres@unjbg.edu.pe',   SHA2('Est2026*', 256), 'David',     'Cáceres Flores',    '72345675', 3, 'ACTIVO'),
(16, '2024-119006', 'est.lvalle@unjbg.edu.pe',     SHA2('Est2026*', 256), 'Lucía',     'Vallejos Coayla',   '72345676', 3, 'ACTIVO'),
(17, '2024-119007', 'est.rfernandez@unjbg.edu.pe', SHA2('Est2026*', 256), 'Renato',    'Fernández Loza',    '72345677', 3, 'ACTIVO'),
(18, '2024-119008', 'est.jticona@unjbg.edu.pe',    SHA2('Est2026*', 256), 'Joel',      'Ticona Cutipa',     '72345678', 3, 'ACTIVO'),
(19, '2024-119009', 'est.sbravo@unjbg.edu.pe',     SHA2('Est2026*', 256), 'Sofía',     'Bravo Centeno',     '72345679', 3, 'ACTIVO'),
(20, '2024-119010', 'est.gnavarro@unjbg.edu.pe',   SHA2('Est2026*', 256), 'Gabriel',   'Navarro Luque',     '72345680', 3, 'ACTIVO');

-- 2. Facultades, Escuelas y Planes
INSERT INTO facultades (id_facultad, codigo_facultad, nombre, decano, activo) VALUES
(1, 'FAIN', 'Facultad de Ingeniería',                         'Dr. Edwin Huarachi Huayta', 1),
(2, 'FCAG', 'Facultad de Ciencias Agropecuarias',             'Dr. Luis Ramos Zeballos',   1),
(3, 'FCJE', 'Facultad de Ciencias Jurídicas y Empresariales', 'Dra. Elena Vargas Ponce',   1);

INSERT INTO escuelas_profesionales (id_escuela, id_facultad, codigo_escuela, nombre, duracion_anios, director, activo) VALUES
(1, 1, 'ESIS',  'Ingeniería en Informática y Sistemas', 5, 'Dr. Erbert Condori Quispe', 1),
(2, 1, 'CIVIL', 'Ingeniería Civil',                    5, 'Ing. Roberto Cáceres Pino',   1);

INSERT INTO planes_estudio (id_plan, id_escuela, codigo_plan, nombre_plan, anio_vigencia, creditos_totales, estado) VALUES
(1, 1, 'ESIS-2022', 'Plan Curricular Flexible 2022 - ESIS', 2022, 210, 'VIGENTE'),
(2, 2, 'CIV-2022',  'Plan Curricular Flexible 2022 - Civil', 2022, 215, 'VIGENTE');

-- 3. Cursos, Malla y Prerrequisitos
INSERT INTO cursos (id_curso, codigo_curso, nombre, creditos, horas_teoria, horas_practica, tipo, activo) VALUES
(1,  'ESIS-101', 'Cálculo I',                      4, 3, 2, 'OBLIGATORIO', 1),
(2,  'ESIS-102', 'Algoritmos y Programación',       4, 3, 2, 'OBLIGATORIO', 1),
(3,  'ESIS-103', 'Introducción a la Computación',   3, 2, 2, 'OBLIGATORIO', 1),
(4,  'ESIS-201', 'Cálculo II',                     4, 3, 2, 'OBLIGATORIO', 1),
(5,  'ESIS-202', 'Estructuras de Datos',           4, 3, 2, 'OBLIGATORIO', 1),
(6,  'ESIS-203', 'Física General',                 4, 3, 2, 'OBLIGATORIO', 1),
(7,  'ESIS-301', 'Base de Datos I',                4, 3, 2, 'OBLIGATORIO', 1),
(8,  'ESIS-302', 'Programación Orientada a Objetos',4, 3, 2, 'OBLIGATORIO', 1),
(9,  'ESIS-303', 'Estadística y Probabilidades',   3, 2, 2, 'OBLIGATORIO', 1),
(10, 'ESIS-401', 'Base de Datos II',               4, 3, 2, 'OBLIGATORIO', 1),
(11, 'ESIS-402', 'Inteligencia Artificial',        4, 3, 2, 'OBLIGATORIO', 1),
(12, 'ESIS-403', 'Redes de Computadoras',          4, 3, 2, 'OBLIGATORIO', 1),
(13, 'ESIS-404', 'Análisis y Diseño de Software',  4, 3, 2, 'OBLIGATORIO', 1);

INSERT INTO malla_curricular (id_plan, id_curso, ciclo) VALUES
(1, 1, 1), (1, 2, 1), (1, 3, 1),
(1, 4, 2), (1, 5, 2), (1, 6, 2),
(1, 7, 3), (1, 8, 3), (1, 9, 3),
(1, 10, 4), (1, 11, 4), (1, 12, 4), (1, 13, 4);

INSERT INTO prerrequisitos (id_curso, id_curso_req) VALUES
(4, 1),   -- Cálculo II requiere Cálculo I
(5, 2),   -- Estructuras de Datos requiere Algoritmos
(7, 5),   -- Base de Datos I requiere Estructuras de Datos
(8, 2),   -- POO requiere Algoritmos
(10, 7),  -- Base de Datos II requiere Base de Datos I
(11, 5),  -- IA requiere Estructuras de Datos
(12, 3),  -- Redes requiere Introducción a la Computación
(13, 8);  -- Análisis y Diseño requiere POO

-- 4. Estudiantes y Ficha Médica
INSERT INTO estudiantes (id_estudiante, id_usuario, id_escuela, id_plan, codigo_universitario, anio_ingreso, modalidad_ingreso, ciclo_actual, estado_academico, promedio_ponderado, promedio_semestre_anterior, creditos_aprobados) VALUES
(1,  11, 1, 1, '2024-119001', 2024, 'EXAMEN ORDINARIO', 4, 'REGULAR', 15.80, 16.20, 64),
(2,  12, 1, 1, '2024-119002', 2024, 'CNA',              4, 'REGULAR', 14.90, 15.10, 64),
(3,  13, 1, 1, '2024-119003', 2024, 'EXAMEN ORDINARIO', 4, 'REGULAR', 13.20, 12.80, 60),
(4,  14, 1, 1, '2024-119004', 2024, 'TRASLADO INTERNO', 4, 'REGULAR', 12.50, 13.00, 60),
(5,  15, 1, 1, '2024-119005', 2024, 'EXAMEN ORDINARIO', 4, 'REGULAR', 14.10, 14.50, 64),
(6,  16, 1, 1, '2024-119006', 2024, 'CEPU',             4, 'REGULAR', 11.80, 11.20, 56),
(7,  17, 1, 1, '2024-119007', 2024, 'EXAMEN ORDINARIO', 4, 'OBSERVADO', 10.40, 10.10, 48),
(8,  18, 1, 1, '2024-119008', 2024, 'EXAMEN ORDINARIO', 4, 'REGULAR', 13.80, 13.90, 60),
(9,  19, 1, 1, '2024-119009', 2024, 'CNA',              4, 'REGULAR', 16.50, 17.00, 64),
(10, 20, 1, 1, '2024-119010', 2024, 'EXAMEN ORDINARIO', 4, 'REGULAR', 12.90, 13.40, 60);

INSERT INTO ficha_medica (id_estudiante, grupo_sanguineo, peso_kg, talla_m, alergias, apto_medico, fecha_examen) VALUES
(1,  'O+',  68.50, 1.73, 'Ninguna',    1, '2026-03-02'),
(2,  'A+',  54.25, 1.62, 'Penicilina', 1, '2026-03-02'),
(3,  'O+',  74.10, 1.70, 'Ninguna',    1, '2026-03-03'),
(4,  'B+',  59.00, 1.65, 'Ninguna',    1, '2026-03-03'),
(5,  'O+',  65.30, 1.68, 'Ninguna',    1, '2026-03-04'),
(6,  'AB+', 70.00, 1.71, 'Ninguna',    1, '2026-03-04'),
(7,  'O-',  63.80, 1.66, 'Ninguna',    0, '2026-03-05'), -- Sin apto médico (para pruebas)
(8,  'A+',  66.20, 1.69, 'Ninguna',    1, '2026-03-05'),
(9,  'O+',  52.40, 1.58, 'Ninguna',    1, '2026-03-05'),
(10, 'O+',  71.90, 1.74, 'Ninguna',    1, '2026-03-06');

-- 5. Periodos, Aulas, Secciones y Horarios
INSERT INTO periodos_academicos (id_periodo, codigo_periodo, anio, semestre, fecha_inicio, fecha_fin, inicio_matricula, fin_matricula, inicio_rectificacion, fin_rectificacion, estado) VALUES
(1, '2026-I',  2026, 1, '2026-03-16', '2026-07-17', '2026-03-01 08:00:00', '2026-03-10 23:59:59', '2026-03-11 08:00:00', '2026-03-14 23:59:59', 'CERRADO'),
(2, '2026-II', 2026, 2, '2026-08-10', '2026-12-11', NOW() - INTERVAL 5 DAY, NOW() + INTERVAL 10 DAY, NOW() - INTERVAL 2 DAY, NOW() + INTERVAL 12 DAY, 'ACTIVO');

INSERT INTO aulas (id_aula, id_facultad, codigo_aula, nombre, tipo, aforo_maximo, equipamiento, estado) VALUES
(1, 1, 'LAB-301', 'Laboratorio de Computación 301 - Pabellón FAIN', 'LABORATORIO', 30, '30 PCs i7, Proyector 4K',       'DISPONIBLE'),
(2, 1, 'LAB-302', 'Laboratorio de Redes y Telecomunicaciones',      'LABORATORIO', 25, '25 PCs, Switches Cisco',         'DISPONIBLE'),
(3, 1, 'AUL-201', 'Aula Magna Teórica 201 - Pabellón ESIS',         'TEORIA',      45, 'Pizarra interactiva, Proyector', 'DISPONIBLE');

-- Secciones del Período 2 (2026-II)
-- Sección 2 inicia 30/30 (LLENA para pruebas). Sección 7 tiene UNA sola vacante
-- (para demostrar lista de espera y promoción con estudiantes reales).
INSERT INTO secciones (id_seccion, id_periodo, id_curso, id_aula, id_docente, codigo_seccion, turno, vacantes_total, vacantes_ocupadas, vacantes_extra) VALUES
(1, 2, 10, 1, 4, 'A', 'MANANA', 30, 0,  0), -- Base de Datos II (Sección A)
(2, 2, 10, 1, 4, 'B', 'TARDE',  30, 30, 0), -- Base de Datos II (Sección B - LLENA)
(3, 2, 11, 3, 5, 'A', 'MANANA', 35, 0,  0), -- Inteligencia Artificial (Sección A)
(4, 2, 11, 3, 5, 'B', 'TARDE',  35, 0,  0), -- Inteligencia Artificial (Sección B)
(5, 2, 12, 2, 6, 'A', 'MANANA', 25, 0,  0), -- Redes de Computadoras (Sección A)
(6, 2, 13, 3, 7, 'A', 'MANANA', 35, 0,  0), -- Análisis y Diseño de Software (Sección A)
(7, 2, 10, 1, 4, 'C', 'NOCHE',   1, 0,  0); -- Base de Datos II (Sección C - 1 SOLA VACANTE)

-- Secciones del Período 1 (2026-I - Historial); vacantes_ocupadas = alumnos del historial
INSERT INTO secciones (id_seccion, id_periodo, id_curso, id_aula, id_docente, codigo_seccion, turno, vacantes_total, vacantes_ocupadas, vacantes_extra) VALUES
(91, 1, 7,  1, 4, 'A', 'MANANA', 30, 7, 0), -- BD I (Curso 7): matrículas 101-107
(92, 1, 8,  3, 6, 'A', 'MANANA', 30, 1, 0), -- POO (Curso 8): matrícula 106
(93, 1, 11, 3, 5, 'A', 'MANANA', 30, 1, 0), -- IA (Curso 11): matrícula 104
(94, 1, 5,  1, 4, 'A', 'MANANA', 30, 6, 0), -- Estructuras (Curso 5): 101,103,104,105,106,107
(95, 1, 3,  2, 2, 'A', 'MANANA', 30, 1, 0); -- Introducción a la Computación (Curso 3): matrícula 101

-- Horarios completos para todas las secciones
-- Cruce intencional: Sección 1 y Sección 3 coinciden los LUNES de 09:00 a 10:00
INSERT INTO horarios_seccion (id_seccion, dia_semana, hora_inicio, hora_fin) VALUES
(1, 'LUNES',     '08:00:00', '10:00:00'),
(1, 'MIERCOLES', '08:00:00', '10:00:00'),
(2, 'LUNES',     '14:00:00', '16:00:00'),
(2, 'MIERCOLES', '14:00:00', '16:00:00'),
(3, 'LUNES',     '09:00:00', '11:00:00'),  -- CRUCE con Sección 1
(3, 'MIERCOLES', '09:00:00', '11:00:00'),
(4, 'MARTES',    '14:00:00', '16:00:00'),
(4, 'JUEVES',    '14:00:00', '16:00:00'),
(5, 'MARTES',    '08:00:00', '10:00:00'),
(5, 'JUEVES',    '08:00:00', '10:00:00'),
(6, 'VIERNES',   '08:00:00', '12:00:00'),
(7, 'SABADO',    '08:00:00', '10:00:00');  -- Sección 7: sin cruces con las demás

-- 6. Finanzas
INSERT INTO conceptos_pago (id_concepto, codigo_tupa, nombre, monto, tipo, vigente) VALUES
(1, 'TUPA-MAT-01', 'Derecho de Matrícula Regular 2026-II', 35.00, 'MATRICULA', 1),
(2, 'TUPA-CAR-02', 'Carné Universitario 2026',              16.00, 'TRAMITE',   1);

INSERT INTO deudas_estudiante (id_deuda, id_estudiante, id_concepto, id_periodo, monto, estado, fecha_vencimiento) VALUES
(1, 1, 1, 2, 35.00, 'PAGADO',    CURDATE() + INTERVAL 5 DAY),
(2, 2, 1, 2, 35.00, 'PAGADO',    CURDATE() + INTERVAL 5 DAY),
(3, 3, 1, 2, 35.00, 'PAGADO',    CURDATE() + INTERVAL 5 DAY),
(4, 4, 1, 2, 35.00, 'PAGADO',    CURDATE() + INTERVAL 5 DAY),
(5, 5, 1, 2, 35.00, 'PAGADO',    CURDATE() + INTERVAL 5 DAY),
(6, 6, 1, 2, 35.00, 'PENDIENTE', CURDATE() - INTERVAL 2 DAY), -- Deuda vencida para pruebas
(7, 7, 1, 2, 35.00, 'PAGADO',    CURDATE() + INTERVAL 5 DAY);

INSERT INTO pagos (id_pago, id_deuda, id_estudiante, id_admin, monto_pagado, metodo_pago, nro_operacion, fecha_pago) VALUES
(1, 1, 1, 1, 35.00, 'BANCO',        'OP-BN-20260801-001', '2026-08-01 10:15:00'),
(2, 2, 2, 1, 35.00, 'BANCO',        'OP-BN-20260801-002', '2026-08-01 10:20:00'),
(3, 3, 3, 1, 35.00, 'BANCO',        'OP-BN-20260801-003', '2026-08-01 10:25:00'),
(4, 4, 4, 1, 35.00, 'BANCO',        'OP-BN-20260801-004', '2026-08-01 10:30:00'),
(5, 5, 5, 1, 35.00, 'TARJETA',      'OP-TJ-20260801-005', '2026-08-01 10:35:00'),
(6, 7, 7, 1, 35.00, 'VENTANILLA',   'OP-VT-20260801-006', '2026-08-01 10:40:00');

INSERT INTO comprobantes (id_comprobante, id_pago, serie, correlativo, tipo_comprobante, monto_total, fecha_emision) VALUES
(1, 1, 'B001', 101, 'BOLETA', 35.00, '2026-08-01 10:15:05'),
(2, 2, 'B001', 102, 'BOLETA', 35.00, '2026-08-01 10:20:05'),
(3, 3, 'B001', 103, 'BOLETA', 35.00, '2026-08-01 10:25:05'),
(4, 4, 'B001', 104, 'BOLETA', 35.00, '2026-08-01 10:30:05'),
(5, 5, 'B001', 105, 'BOLETA', 35.00, '2026-08-01 10:35:05'),
(6, 6, 'B001', 106, 'BOLETA', 35.00, '2026-08-01 10:40:05');

-- 7. Historial Académico 2026-I (total_creditos explícito = suma de créditos del detalle)
INSERT INTO matriculas (id_matricula, id_estudiante, id_periodo, tipo_matricula, estado, total_creditos, creditos_adicionales, fecha_matricula, nro_constancia) VALUES
(101, 1,  1, 'REGULAR', 'CONFIRMADA', 11, 0, '2026-03-02 08:30:00', 'MAT-2026-I-000001'), -- 4+4+3
(102, 3,  1, 'REGULAR', 'CONFIRMADA',  4, 0, '2026-03-02 09:00:00', 'MAT-2026-I-000002'), -- 4
(103, 2,  1, 'REGULAR', 'CONFIRMADA',  8, 0, '2026-03-02 09:30:00', 'MAT-2026-I-000003'), -- 4+4
(104, 5,  1, 'REGULAR', 'CONFIRMADA', 12, 0, '2026-03-02 10:00:00', 'MAT-2026-I-000004'), -- 4+4+4
(105, 8,  1, 'REGULAR', 'CONFIRMADA',  8, 0, '2026-03-02 10:30:00', 'MAT-2026-I-000005'), -- 4+4
(106, 9,  1, 'REGULAR', 'CONFIRMADA', 12, 0, '2026-03-02 11:00:00', 'MAT-2026-I-000006'), -- 4+4+4
(107, 10, 1, 'REGULAR', 'CONFIRMADA',  8, 0, '2026-03-02 11:30:00', 'MAT-2026-I-000007'); -- 4+4

-- Juan Pérez (Est 1): aprobó BD I (16.50), Estructuras (16.00) e Introducción a la Computación (17.00)
INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, nota_final, nota_teoria, nota_practica, origen) VALUES
(101, 91, 'INSCRITO', 16.50, 16.00, 17.00, 'PRIMERA_VEZ'),
(101, 94, 'INSCRITO', 16.00, 16.00, 16.00, 'PRIMERA_VEZ'),
(101, 95, 'INSCRITO', 17.00, 17.00, 17.00, 'PRIMERA_VEZ');

-- Carlos Mamani (Est 3): desaprobó BD I (09.50) -> Faltará prerrequisito para BD II
INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, nota_final, nota_teoria, nota_practica, origen) VALUES
(102, 91, 'INSCRITO', 09.50, 09.00, 10.00, 'PRIMERA_VEZ');

-- Ana Quispe (Est 2): aprobó BD I (15.00) y Estructuras (16.00)
INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, nota_final, nota_teoria, nota_practica, origen) VALUES
(103, 91, 'INSCRITO', 15.00, 15.00, 15.00, 'PRIMERA_VEZ'),
(103, 94, 'INSCRITO', 16.00, 16.00, 16.00, 'PRIMERA_VEZ');

-- David Cáceres (Est 5): aprobó BD I (14.00), desaprobó IA (08.50) y aprobó Estructuras (15.00)
-- Cumple el prerrequisito para llevar IA por SEGUNDA_VEZ demostrable
INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, nota_final, nota_teoria, nota_practica, origen) VALUES
(104, 91, 'INSCRITO', 14.00, 14.00, 14.00, 'PRIMERA_VEZ'),
(104, 93, 'INSCRITO', 08.50, 08.00, 09.00, 'PRIMERA_VEZ'),
(104, 94, 'INSCRITO', 15.00, 15.00, 15.00, 'PRIMERA_VEZ');

-- Joel Ticona (Est 8): aprobó BD I (13.00) y Estructuras (13.50)
INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, nota_final, nota_teoria, nota_practica, origen) VALUES
(105, 91, 'INSCRITO', 13.00, 13.00, 13.00, 'PRIMERA_VEZ'),
(105, 94, 'INSCRITO', 13.50, 13.00, 14.00, 'PRIMERA_VEZ');

-- Sofía Bravo (Est 9): aprobó BD I (18.00), POO (19.00) y Estructuras (17.00)
INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, nota_final, nota_teoria, nota_practica, origen) VALUES
(106, 91, 'INSCRITO', 18.00, 18.00, 18.00, 'PRIMERA_VEZ'),
(106, 92, 'INSCRITO', 19.00, 19.00, 19.00, 'PRIMERA_VEZ'),
(106, 94, 'INSCRITO', 17.00, 17.00, 17.00, 'PRIMERA_VEZ');

-- Gabriel Navarro (Est 10): aprobó BD I (12.00) y Estructuras (14.00)
INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, nota_final, nota_teoria, nota_practica, origen) VALUES
(107, 91, 'INSCRITO', 12.00, 12.00, 12.00, 'PRIMERA_VEZ'),
(107, 94, 'INSCRITO', 14.00, 14.00, 14.00, 'PRIMERA_VEZ');

-- 8. Tablas complementarias
INSERT INTO cola_matricula (id_cola, id_estudiante, id_periodo, posicion, estado_cola, priorizado, hora_ingreso, hora_turno, hora_fin) VALUES
(1, 1, 2, 1, 'FINALIZADO', 1, NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 110 MINUTE, NOW() - INTERVAL 100 MINUTE),
(2, 2, 2, 2, 'FINALIZADO', 1, NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 50 MINUTE,  NOW() - INTERVAL 40 MINUTE),
(3, 8, 2, 3, 'EN_TURNO',   0, NOW() - INTERVAL 5 MINUTE, NOW(), NULL);

INSERT INTO excepciones_matricula (id_excepcion, id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin, fecha) VALUES
(1, 101, 'CREDITOS_EXTRAS', 'RD-015-2026-FAIN', 'Ampliación de créditos por rendimiento académico sobresaliente', 1, '2026-03-01 10:00:00'),
(2, 102, 'CURSO_DIRIGIDO',  'RD-016-2026-FAIN', 'Autorización de curso dirigido por adecuación curricular',        1, '2026-03-01 11:00:00');

INSERT INTO resoluciones_estudiante (id_resolucion, id_estudiante, tipo_resolucion, numero_doc, descripcion, fecha_efecto, id_admin, created_at) VALUES
(1, 1, 'AMPLIACION_CREDITOS', 'R-CU-045-2026', 'Aprobación de 4 créditos adicionales por tercio superior', '2026-02-15', 1, '2026-02-15 09:00:00'),
(2, 4, 'TRASLADO',            'R-CU-022-2024', 'Convalidación por traslado interno de carrera',            '2024-03-10', 1, '2024-03-10 10:00:00');

INSERT INTO rectificaciones (id_rectificacion, id_matricula, id_admin, nro_acta, estado, observaciones, fecha_inicio, fecha_confirmacion) VALUES
(1, 101, 1, 'ACTA-REC-2026-I-001', 'APROBADO',  'Inclusión de asignatura en periodo de rectificación',                     '2026-03-12 10:00:00', '2026-03-12 10:30:00'),
(2, 102, 1, 'ACTA-REC-2026-I-002', 'RECHAZADO', 'Cambio de sección no procedente; estudiante se mantiene en sección origen', '2026-03-12 11:00:00', '2026-03-12 11:15:00');

INSERT INTO detalle_rectificacion (id_det_rectif, id_rectificacion, id_seccion, tipo_movimiento, seccion_destino) VALUES
(1, 1, 94, 'AGREGAR',        NULL),
(2, 2, 91, 'CAMBIO_SECCION', 94);

INSERT INTO auditoria (id_auditoria, id_usuario, modulo, accion, tabla_afectada, id_registro, detalle, ip_origen, fecha_hora) VALUES
(1, 1, 'SEGURIDAD', 'INICIO_SESION',         'usuarios',            1, 'Inicio de sesión exitoso administrador Carlos Vargas', '192.168.1.10', NOW() - INTERVAL 1 DAY),
(2, 1, 'MATRICULA', 'CONFIGURACION_PERIODO', 'periodos_academicos', 2, 'Apertura de período académico 2026-II',               '192.168.1.10', NOW() - INTERVAL 5 DAY);

SET FOREIGN_KEY_CHECKS = 1;
