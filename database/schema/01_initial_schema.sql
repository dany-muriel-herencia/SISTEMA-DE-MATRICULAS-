-- ============================================================
-- BASE DE DATOS DEL SISTEMA DE GESTIÓN ACADÉMICA - SGAU
-- ============================================================

CREATE DATABASE IF NOT EXISTS sgau
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sgau;


-- ============================================================
-- 1. USUARIOS
-- ============================================================

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    contrasenha VARCHAR(255) NOT NULL,
    rol VARCHAR(50) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE estudiante (
    id_usuario INT PRIMARY KEY,
    codigo_universitario VARCHAR(30) NOT NULL UNIQUE,
    dni VARCHAR(8) NOT NULL UNIQUE,
    fecha_nacimiento DATE NOT NULL,
    fecha_ingreso DATE NOT NULL,
    promedio_academico DECIMAL(5,2) NOT NULL DEFAULT 0,

    CONSTRAINT fk_estudiante_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
);


CREATE TABLE docente (
    id_usuario INT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    especialidad VARCHAR(150) NOT NULL,
    grado_academico VARCHAR(100) NOT NULL,

    CONSTRAINT fk_docente_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
);


CREATE TABLE administrador (
    id_usuario INT PRIMARY KEY,
    nivel VARCHAR(50) NOT NULL,

    CONSTRAINT fk_administrador_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
);


-- ============================================================
-- 2. ESTRUCTURA ACADÉMICA
-- ============================================================

CREATE TABLE facultad (
    id_facultad INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    decano VARCHAR(150)
);


CREATE TABLE escuela (
    id_escuela INT AUTO_INCREMENT PRIMARY KEY,
    id_facultad INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    director VARCHAR(150),

    CONSTRAINT fk_escuela_facultad
        FOREIGN KEY (id_facultad)
        REFERENCES facultad(id_facultad)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);


CREATE TABLE carrera (
    id_carrera INT AUTO_INCREMENT PRIMARY KEY,
    id_escuela INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    duracion INT NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT fk_carrera_escuela
        FOREIGN KEY (id_escuela)
        REFERENCES escuela(id_escuela)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);


CREATE TABLE plan_estudio (
    id_plan INT AUTO_INCREMENT PRIMARY KEY,
    id_carrera INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    estado BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT fk_plan_carrera
        FOREIGN KEY (id_carrera)
        REFERENCES carrera(id_carrera)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);


-- ============================================================
-- 3. CURSOS Y PLAN DE ESTUDIOS
-- ============================================================

CREATE TABLE curso (
    id_curso INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    creditos INT NOT NULL,
    horas_teoria INT NOT NULL DEFAULT 0,
    horas_practica INT NOT NULL DEFAULT 0,
    ciclo VARCHAR(20) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);


CREATE TABLE curriculum (
    id_curriculum INT AUTO_INCREMENT PRIMARY KEY,
    id_plan INT NOT NULL,
    id_curso INT NOT NULL,
    ciclo VARCHAR(20) NOT NULL,
    obligatorio BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT fk_curriculum_plan
        FOREIGN KEY (id_plan)
        REFERENCES plan_estudio(id_plan)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_curriculum_curso
        FOREIGN KEY (id_curso)
        REFERENCES curso(id_curso)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT uk_curriculum_plan_curso
        UNIQUE (id_plan, id_curso)
);


CREATE TABLE prerequisito (
    id_prerequisito INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    id_curso_requerido INT NOT NULL,

    CONSTRAINT fk_prerequisito_curso
        FOREIGN KEY (id_curso)
        REFERENCES curso(id_curso)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_prerequisito_curso_requerido
        FOREIGN KEY (id_curso_requerido)
        REFERENCES curso(id_curso)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT uk_prerequisito
        UNIQUE (id_curso, id_curso_requerido)
);


-- ============================================================
-- 4. PERIODO ACADÉMICO
-- ============================================================

CREATE TABLE periodo_academico (
    id_periodo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    fecha_matricula_inicio DATE NOT NULL,
    fecha_matricula_fin DATE NOT NULL,
    estado VARCHAR(30) NOT NULL
);


-- ============================================================
-- 5. SECCIONES
-- ============================================================

CREATE TABLE seccion (
    id_seccion INT AUTO_INCREMENT PRIMARY KEY,
    id_curso INT NOT NULL,
    id_periodo INT NOT NULL,
    id_docente INT NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    vacantes INT NOT NULL,
    vacantes_disponibles INT NOT NULL,

    CONSTRAINT fk_seccion_curso
        FOREIGN KEY (id_curso)
        REFERENCES curso(id_curso)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_seccion_periodo
        FOREIGN KEY (id_periodo)
        REFERENCES periodo_academico(id_periodo)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_seccion_docente
        FOREIGN KEY (id_docente)
        REFERENCES docente(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT uk_seccion_periodo_codigo
        UNIQUE (id_periodo, codigo)
);


-- ============================================================
-- 6. AULAS
-- ============================================================

CREATE TABLE aula (
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(150),
    capacidad INT NOT NULL,
    tipo VARCHAR(50),
    disponible BOOLEAN NOT NULL DEFAULT TRUE,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);


-- ============================================================
-- 7. HORARIOS
-- ============================================================

CREATE TABLE horario (
    id_horario INT AUTO_INCREMENT PRIMARY KEY,
    id_seccion INT NOT NULL,
    id_aula INT NOT NULL,
    dia_semana VARCHAR(20) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    modalidad VARCHAR(50) NOT NULL,

    CONSTRAINT fk_horario_seccion
        FOREIGN KEY (id_seccion)
        REFERENCES seccion(id_seccion)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_horario_aula
        FOREIGN KEY (id_aula)
        REFERENCES aula(id_aula)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);


-- ============================================================
-- 8. MATRÍCULA
-- ============================================================

CREATE TABLE matricula (
    id_matricula INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_periodo INT NOT NULL,
    codigo_matricula VARCHAR(40) NOT NULL UNIQUE,
    fecha_matricula DATE NOT NULL,
    estado VARCHAR(30) NOT NULL,
    total_creditos INT NOT NULL DEFAULT 0,

    CONSTRAINT fk_matricula_estudiante
        FOREIGN KEY (id_estudiante)
        REFERENCES estudiante(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_matricula_periodo
        FOREIGN KEY (id_periodo)
        REFERENCES periodo_academico(id_periodo)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);


CREATE TABLE detalle_matricula (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT NOT NULL,
    id_seccion INT NOT NULL,
    estado VARCHAR(30) NOT NULL,

    CONSTRAINT fk_detalle_matricula
        FOREIGN KEY (id_matricula)
        REFERENCES matricula(id_matricula)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_detalle_seccion
        FOREIGN KEY (id_seccion)
        REFERENCES seccion(id_seccion)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT uk_detalle_matricula_seccion
        UNIQUE (id_matricula, id_seccion)
);


-- ============================================================
-- 9. PAGOS
-- ============================================================

CREATE TABLE concepto_pago (
    id_concepto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    monto DECIMAL(10,2) NOT NULL,
    obligatorio BOOLEAN NOT NULL DEFAULT TRUE
);


CREATE TABLE pago (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_concepto INT NOT NULL,
    fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago VARCHAR(50) NOT NULL,

    CONSTRAINT fk_pago_estudiante
        FOREIGN KEY (id_estudiante)
        REFERENCES estudiante(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_pago_concepto
        FOREIGN KEY (id_concepto)
        REFERENCES concepto_pago(id_concepto)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);


CREATE TABLE comprobante_pago (
    id_comprobante INT AUTO_INCREMENT PRIMARY KEY,
    id_pago INT NOT NULL UNIQUE,
    tipo VARCHAR(50) NOT NULL,
    numero VARCHAR(50) NOT NULL,
    serie VARCHAR(50),
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comprobante_pago
        FOREIGN KEY (id_pago)
        REFERENCES pago(id_pago)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- ============================================================
-- 10. SESIONES
-- ============================================================

CREATE TABLE sesion (
    id_sesion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin DATETIME,
    ip VARCHAR(45),
    activa BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT fk_sesion_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);


-- ============================================================
-- 11. AUDITORÍA
-- ============================================================

CREATE TABLE auditoria (
    id_auditoria INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(100) NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    ip VARCHAR(45),

    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);


-- ============================================================
-- ÍNDICES
-- ============================================================

CREATE INDEX idx_estudiante_codigo
    ON estudiante(codigo_universitario);

CREATE INDEX idx_estudiante_dni
    ON estudiante(dni);

CREATE INDEX idx_curso_codigo
    ON curso(codigo);

CREATE INDEX idx_seccion_curso
    ON seccion(id_curso);

CREATE INDEX idx_seccion_periodo
    ON seccion(id_periodo);

CREATE INDEX idx_matricula_estudiante
    ON matricula(id_estudiante);

CREATE INDEX idx_matricula_periodo
    ON matricula(id_periodo);

CREATE INDEX idx_detalle_matricula
    ON detalle_matricula(id_matricula);

CREATE INDEX idx_pago_estudiante
    ON pago(id_estudiante);

CREATE INDEX idx_sesion_usuario
    ON sesion(id_usuario);

CREATE INDEX idx_auditoria_usuario
    ON auditoria(id_usuario);