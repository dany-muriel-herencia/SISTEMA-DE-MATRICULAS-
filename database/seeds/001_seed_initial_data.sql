-- =============================================================================
-- SEED 001: DATOS INICIALES DEL SISTEMA
-- =============================================================================

USE `db_matricula_unjbg`;

-- 1. Roles del Sistema
INSERT INTO `roles` (`id`, `nombre`, `descripcion`) VALUES
(1, 'ADMIN', 'Administrador General del Sistema'),
(2, 'COORDINADOR', 'Coordinador de Escuela Profesional'),
(3, 'DOCENTE', 'Docente Universitario'),
(4, 'ESTUDIANTE', 'Estudiante de Pregrado')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 2. Usuario Administrador por Defecto
-- Hash bcrypt para 'Admin123*'
INSERT INTO `usuarios` (`id`, `rol_id`, `dni`, `email`, `password_hash`, `nombre`, `apellido`, `telefono`, `activo`) VALUES
(1, 1, '00000000', 'admin@unjbg.edu.pe', '$2a$10$wT/3G.mJ6yGzX0bNqP8Z6eB0s7y8bY0n1h6aV5bM7z.P0s9dE2r3a', 'Administrador', 'UNJBG', '952000000', TRUE)
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- 3. Facultades y Carreras (Ejemplo: FIAG - Ingeniería en Informática y Sistemas)
INSERT INTO `facultades` (`id`, `codigo`, `nombre`) VALUES
(1, 'FIAG', 'Facultad de Ingeniería Civil, Arquitectura y Geotecnia'),
(2, 'FAIN', 'Facultad de Ingeniería')
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

INSERT INTO `carreras` (`id`, `facultad_id`, `codigo`, `nombre`, `duracion_semestres`) VALUES
(1, 2, 'ESIS', 'Ingeniería en Informática y Sistemas', 10),
(2, 1, 'ESIC', 'Ingeniería Civil', 10)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- 4. Plan de Estudios
INSERT INTO `planes_estudio` (`id`, `carrera_id`, `codigo`, `anio`, `activo`) VALUES
(1, 1, 'PLAN-2023', 2023, TRUE)
ON DUPLICATE KEY UPDATE `activo` = VALUES(`activo`);

-- 5. Periodo Académico
INSERT INTO `periodos_academicos` (`id`, `codigo`, `anio`, `semestre`, `fecha_inicio`, `fecha_fin`, `fecha_inicio_matricula`, `fecha_fin_matricula`, `estado`) VALUES
(1, '2026-I', 2026, 'I', '2026-03-15', '2026-07-20', '2026-03-01 08:00:00', '2026-03-14 23:59:59', 'MATRICULA_ABIERTA')
ON DUPLICATE KEY UPDATE `estado` = VALUES(`estado`);

-- 6. Cursos de Muestra
INSERT INTO `cursos` (`id`, `codigo`, `nombre`, `creditos`, `horas_teoricas`, `horas_practicas`) VALUES
(1, 'IS-101', 'Introducción a la Programación', 4, 2, 4),
(2, 'IS-102', 'Cálculo I', 4, 3, 2),
(3, 'IS-201', 'Estructuras de Datos y Algoritmos', 4, 2, 4),
(4, 'IS-301', 'Base de Datos I', 4, 2, 4)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);
