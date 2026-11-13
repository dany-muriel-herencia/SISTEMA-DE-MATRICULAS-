-- Esquema de Gestión de Estudiantes (Módulo 3)
CREATE DATABASE IF NOT EXISTS sgau_student CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sgau_student;

CREATE TABLE IF NOT EXISTS estudiantes (
    id_estudiante INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    id_escuela INT NOT NULL,
    id_plan INT NOT NULL,
    codigo_estudiante VARCHAR(20) NOT NULL UNIQUE,
    estado_academico ENUM('REGULAR', 'OBSERVADO', 'INVICTO', 'EGRESADO', 'GRADUADO', 'SUSPENDIDO') NOT NULL DEFAULT 'REGULAR',
    promedio_semestre_anterior DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    promedio_ponderado_acumulado DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    creditos_aprobados INT NOT NULL DEFAULT 0,
    anio_ingreso YEAR NOT NULL,
    modalidad_ingreso VARCHAR(50) NOT NULL DEFAULT 'EXAMEN_ORDINARIO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ficha_medica (
    id_ficha INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL UNIQUE,
    apto_para_matricula BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_evaluacion DATETIME NULL,
    observaciones TEXT NULL,
    CONSTRAINT fk_ficha_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes (id_estudiante)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resoluciones_estudiante (
    id_resolucion INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    nro_resolucion VARCHAR(50) NOT NULL,
    tipo_resolucion ENUM('AMPLIACION_CREDITOS', 'CONVALIDACION', 'TRASLADO_INTERNO', 'TRASLADO_EXTERNO', 'SUSPENSION') NOT NULL,
    creditos_otorgados INT NOT NULL DEFAULT 0,
    fecha_emision DATE NOT NULL,
    archivo_sustento_url VARCHAR(255) DEFAULT '',
    CONSTRAINT fk_res_estudiante FOREIGN KEY (id_estudiante) REFERENCES estudiantes (id_estudiante)
) ENGINE=InnoDB;

