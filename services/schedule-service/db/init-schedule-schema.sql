-- Esquema de Infraestructura y Horarios (Módulo 7)
CREATE DATABASE IF NOT EXISTS sgau_schedule CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sgau_schedule;

CREATE TABLE IF NOT EXISTS aulas (
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    codigo_aula VARCHAR(20) NOT NULL UNIQUE,
    pabellon VARCHAR(50) NOT NULL,
    piso INT NOT NULL,
    capacidad_aforo INT NOT NULL,
    tipo ENUM('TEORIA', 'LABORATORIO', 'AUDITORIO') NOT NULL DEFAULT 'TEORIA'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS secciones (
    id_seccion INT AUTO_INCREMENT PRIMARY KEY,
    id_periodo INT NOT NULL,
    id_curso INT NOT NULL,
    id_aula INT NOT NULL,
    id_docente INT NOT NULL,
    codigo_seccion VARCHAR(10) NOT NULL,
    turno ENUM('MANANA', 'TARDE', 'NOCHE') NOT NULL DEFAULT 'MANANA',
    vacantes_total INT NOT NULL DEFAULT 40,
    vacantes_ocupadas INT NOT NULL DEFAULT 0,
    vacantes_extra INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_secciones_aulas FOREIGN KEY (id_aula) REFERENCES aulas (id_aula),
    UNIQUE KEY uk_curso_periodo_sec (id_periodo, id_curso, codigo_seccion)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS horarios_seccion (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_seccion INT NOT NULL,
    dia_semana ENUM('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    CONSTRAINT fk_horario_seccion FOREIGN KEY (id_seccion) REFERENCES secciones (id_seccion)
) ENGINE=InnoDB;

