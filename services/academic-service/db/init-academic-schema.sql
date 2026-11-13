-- Esquema de Gestión Curricular y Académica (Módulo 4)
CREATE DATABASE IF NOT EXISTS sgau_academic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sgau_academic;

CREATE TABLE IF NOT EXISTS facultades (
    id_facultad INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    decano VARCHAR(150) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS escuelas_profesionales (
    id_escuela INT AUTO_INCREMENT PRIMARY KEY,
    id_facultad INT NOT NULL,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    director VARCHAR(150) NOT NULL,
    CONSTRAINT fk_escuelas_facultades FOREIGN KEY (id_facultad) REFERENCES facultades (id_facultad)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS planes_estudio (
    id_plan INT AUTO_INCREMENT PRIMARY KEY,
    id_escuela INT NOT NULL,
    codigo_plan VARCHAR(20) NOT NULL,
    anio_vigencia YEAR NOT NULL,
    total_creditos_requeridos INT NOT NULL DEFAULT 200,
    estado ENUM('VIGENTE', 'CERRADO', 'EN_DISENIO') NOT NULL DEFAULT 'VIGENTE',
    CONSTRAINT fk_planes_escuelas FOREIGN KEY (id_escuela) REFERENCES escuelas_profesionales (id_escuela),
    UNIQUE KEY uk_escuela_plan (id_escuela, codigo_plan)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cursos (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    codigo_curso VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    creditos INT NOT NULL,
    horas_teoria INT NOT NULL DEFAULT 0,
    horas_practica INT NOT NULL DEFAULT 0,
    tipo ENUM('OBLIGATORIO', 'ELECTIVO') NOT NULL DEFAULT 'OBLIGATORIO'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mallas_curriculares (
    id_malla INT AUTO_INCREMENT PRIMARY KEY,
    id_plan INT NOT NULL,
    id_curso INT NOT NULL,
    ciclo INT NOT NULL,
    es_obligatorio BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_malla_planes FOREIGN KEY (id_plan) REFERENCES planes_estudio (id_plan),
    CONSTRAINT fk_malla_cursos FOREIGN KEY (id_curso) REFERENCES cursos (id_curso),
    UNIQUE KEY uk_plan_curso (id_plan, id_curso)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prerrequisitos (
    id_curso INT NOT NULL,
    id_curso_req INT NOT NULL,
    PRIMARY KEY (id_curso, id_curso_req),
    CONSTRAINT fk_prerreq_curso FOREIGN KEY (id_curso) REFERENCES cursos (id_curso),
    CONSTRAINT fk_prerreq_curso_req FOREIGN KEY (id_curso_req) REFERENCES cursos (id_curso)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS periodos_academicos (
    id_periodo INT AUTO_INCREMENT PRIMARY KEY,
    codigo_periodo VARCHAR(20) NOT NULL UNIQUE,
    anio YEAR NOT NULL,
    semestre ENUM('I', 'II', 'VERANO') NOT NULL,
    inicio_matricula DATETIME NOT NULL,
    fin_matricula DATETIME NOT NULL,
    inicio_clases DATE NOT NULL,
    fin_clases DATE NOT NULL,
    estado ENUM('PLANIFICACION', 'APERTURADO', 'EN_MATRICULA', 'EN_CURSO', 'CERRADO') NOT NULL DEFAULT 'PLANIFICACION'
) ENGINE=InnoDB;

