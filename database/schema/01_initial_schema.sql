-- =============================================================================
-- SISTEMA DE MATRÍCULA UNJBG - ESQUEMA DE BASE DE DATOS RELACIONAL
-- Motor: MySQL 8.x / MariaDB
-- Acceso: SQL Nativo sin ORM
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `db_matricula_unjbg`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_matricula_unjbg`;

-- 1. TABLA: roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(50) NOT NULL UNIQUE,
  `descripcion` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. TABLA: usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rol_id` INT NOT NULL,
  `dni` VARCHAR(8) NOT NULL UNIQUE,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido` VARCHAR(100) NOT NULL,
  `telefono` VARCHAR(20) NULL,
  `activo` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 3. TABLA: facultades
CREATE TABLE IF NOT EXISTS `facultades` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. TABLA: carreras (Escuelas Profesionales)
CREATE TABLE IF NOT EXISTS `carreras` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `facultad_id` INT NOT NULL,
  `codigo` VARCHAR(10) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `duracion_semestres` INT NOT NULL DEFAULT 10,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_carreras_facultad` FOREIGN KEY (`facultad_id`) REFERENCES `facultades` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 5. TABLA: planes_estudio
CREATE TABLE IF NOT EXISTS `planes_estudio` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `carrera_id` INT NOT NULL,
  `codigo` VARCHAR(20) NOT NULL,
  `anio` INT NOT NULL,
  `activo` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_planes_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE RESTRICT,
  UNIQUE KEY `uk_carrera_plan` (`carrera_id`, `codigo`)
) ENGINE=InnoDB;

-- 6. TABLA: estudiantes
CREATE TABLE IF NOT EXISTS `estudiantes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL UNIQUE,
  `carrera_id` INT NOT NULL,
  `plan_estudio_id` INT NOT NULL,
  `codigo_estudiante` VARCHAR(15) NOT NULL UNIQUE,
  `anio_ingreso` INT NOT NULL,
  `estado_academico` ENUM('REGULAR', 'OBSERVADO', 'EGRESADO', 'RETIRADO') DEFAULT 'REGULAR',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_estudiantes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estudiantes_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_estudiantes_plan` FOREIGN KEY (`plan_estudio_id`) REFERENCES `planes_estudio` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 7. TABLA: cursos
CREATE TABLE IF NOT EXISTS `cursos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `creditos` INT NOT NULL,
  `horas_teoricas` INT NOT NULL DEFAULT 2,
  `horas_practicas` INT NOT NULL DEFAULT 2,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 8. TABLA: plan_cursos (Malla curricular con ciclo)
CREATE TABLE IF NOT EXISTS `plan_cursos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_estudio_id` INT NOT NULL,
  `curso_id` INT NOT NULL,
  `ciclo` INT NOT NULL,
  `es_electivo` BOOLEAN NOT NULL DEFAULT FALSE,
  CONSTRAINT `fk_pc_plan` FOREIGN KEY (`plan_estudio_id`) REFERENCES `planes_estudio` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_plan_curso` (`plan_estudio_id`, `curso_id`)
) ENGINE=InnoDB;

-- 9. TABLA: prerrequisitos
CREATE TABLE IF NOT EXISTS `prerrequisitos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_curso_id` INT NOT NULL,
  `curso_requisito_id` INT NOT NULL,
  CONSTRAINT `fk_prereq_plancurso` FOREIGN KEY (`plan_curso_id`) REFERENCES `plan_cursos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prereq_cursoreq` FOREIGN KEY (`curso_requisito_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. TABLA: periodos_academicos
CREATE TABLE IF NOT EXISTS `periodos_academicos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `anio` INT NOT NULL,
  `semestre` ENUM('I', 'II', 'EXTRAORDINARIO') NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `fecha_inicio_matricula` DATETIME NOT NULL,
  `fecha_fin_matricula` DATETIME NOT NULL,
  `estado` ENUM('PLANIFICACION', 'MATRICULA_ABIERTA', 'EN_CURSO', 'CERRADO') DEFAULT 'PLANIFICACION',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 11. TABLA: aulas
CREATE TABLE IF NOT EXISTS `aulas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `pabellon` VARCHAR(50) NOT NULL,
  `capacidad` INT NOT NULL,
  `tipo` ENUM('TEORICA', 'LABORATORIO', 'TALLER') DEFAULT 'TEORICA'
) ENGINE=InnoDB;

-- 12. TABLA: secciones (Oferta académica por periodo)
CREATE TABLE IF NOT EXISTS `secciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `periodo_id` INT NOT NULL,
  `curso_id` INT NOT NULL,
  `docente_id` INT NULL,
  `letra_seccion` VARCHAR(2) NOT NULL,
  `capacidad_maxima` INT NOT NULL DEFAULT 40,
  `vacantes_disponibles` INT NOT NULL DEFAULT 40,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_secciones_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos_academicos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_secciones_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_secciones_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  UNIQUE KEY `uk_seccion_periodo_curso` (`periodo_id`, `curso_id`, `letra_seccion`)
) ENGINE=InnoDB;

-- 13. TABLA: horarios
CREATE TABLE IF NOT EXISTS `horarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `seccion_id` INT NOT NULL,
  `aula_id` INT NULL,
  `dia_semana` ENUM('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO') NOT NULL,
  `hora_inicio` TIME NOT NULL,
  `hora_fin` TIME NOT NULL,
  CONSTRAINT `fk_horarios_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_horarios_aula` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 14. TABLA: matriculas
CREATE TABLE IF NOT EXISTS `matriculas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `estudiante_id` INT NOT NULL,
  `periodo_id` INT NOT NULL,
  `codigo_matricula` VARCHAR(30) NOT NULL UNIQUE,
  `fecha_matricula` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_creditos` INT NOT NULL DEFAULT 0,
  `estado` ENUM('REGISTRADA', 'RECTIFICADA', 'ANULADA') NOT NULL DEFAULT 'REGISTRADA',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_matriculas_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_matriculas_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos_academicos` (`id`) ON DELETE RESTRICT,
  UNIQUE KEY `uk_estudiante_periodo` (`estudiante_id`, `periodo_id`)
) ENGINE=InnoDB;

-- 15. TABLA: matricula_detalles
CREATE TABLE IF NOT EXISTS `matricula_detalles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `matricula_id` INT NOT NULL,
  `seccion_id` INT NOT NULL,
  `creditos` INT NOT NULL,
  `estado_curso` ENUM('MATRICULADO', 'RETIRADO', 'APROBADO', 'DESAPROBADO') DEFAULT 'MATRICULADO',
  CONSTRAINT `fk_md_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_md_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE RESTRICT,
  UNIQUE KEY `uk_matricula_seccion` (`matricula_id`, `seccion_id`)
) ENGINE=InnoDB;
