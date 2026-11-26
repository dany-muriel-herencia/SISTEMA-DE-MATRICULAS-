-- =============================================================================
-- SISTEMA INTEGRADO DE GESTIÓN ACADÉMICA Y MATRÍCULA UNIVERSITARIA (UNJBG - ESIS)
-- MOTOR: MySQL 8.0+ / MariaDB 10.5+
-- BASE DE DATOS: bd_matricula_universitaria
-- VERSIÓN: v4.0  (DDL + Funciones + Procedimientos + Seed; SIN triggers)
--
-- COLAS:
--   1. Cola de turnos de matrícula      (cola_matricula)
--   2. Lista de espera por sección      (lista_espera_seccion)
--   3. Cola de solicitudes de rectificación (rectificaciones en estado SOLICITADO)
--
-- TRANSACCIONES (todo o nada):
--   TX-01 sp_registrar_matricula_completa   TX-02 sp_aprobar_rectificacion
--   TX-03 sp_registrar_pago                 TX-04 colas de turnos
--   TX-05 sp_cerrar_semestre_academico      TX-06 espera / anulación / excepciones
--
-- CONVENCIONES PARA EL BACKEND
--  * Todo procedimiento público devuelve OUT p_codigo_resp ('EXITO' o código de
--    negocio) y OUT p_mensaje. 'ERROR_SQL' = fallo inesperado (ya hubo ROLLBACK).
--  * Si llega 'ERROR_SQL' con "Deadlock" (error 1213) o "Lock wait timeout"
--    (1205), el backend debe REINTENTAR la operación.
--  * Procedimientos sp_i_* son INTERNOS (sin transacción): el backend NO debe
--    llamarlos; los usan los procedimientos públicos.
--  * Un procedimiento que abre transacción no debe llamarse dentro de otra
--    transacción abierta (START TRANSACTION hace COMMIT implícito).
--  * Al no haber triggers, créditos y vacantes se actualizan SOLO desde los
--    procedimientos. Un UPDATE/INSERT directo en detalle_matricula NO ajusta
--    total_creditos ni vacantes_ocupadas.
-- =============================================================================

-- SET GLOBAL log_bin_trust_function_creators = 1; -- Descomentar solo si MySQL da el error 1419 y el usuario es root

DROP DATABASE IF EXISTS bd_matricula_universitaria;
CREATE DATABASE bd_matricula_universitaria 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE bd_matricula_universitaria;

SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- PARTE 1: ESTRUCTURA DE TABLAS (DDL CON RESTRICCIONES CHECK)
-- =============================================================================

-- 1. MÓDULO DE SEGURIDAD Y AUDITORÍA
CREATE TABLE roles (
    id_rol TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(200) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE usuarios (
    id_usuario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_usuario VARCHAR(20) NOT NULL UNIQUE,
    email_institucional VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    dni CHAR(8) NOT NULL UNIQUE,
    id_rol TINYINT UNSIGNED NOT NULL,
    estado ENUM('ACTIVO', 'INACTIVO', 'BLOQUEADO') NOT NULL DEFAULT 'ACTIVO',
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_acceso TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_roles 
        FOREIGN KEY (id_rol) REFERENCES roles(id_rol) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE auditoria (
    id_auditoria BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NULL,
    modulo VARCHAR(50) NOT NULL,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    id_registro INT UNSIGNED NULL,
    detalle TEXT NULL,
    ip_origen VARCHAR(45) NULL,
    fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuarios 
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- 2. MÓDULO ACADÉMICO Y PLANES DE ESTUDIO
CREATE TABLE facultades (
    id_facultad TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_facultad CHAR(6) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    decano VARCHAR(150) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE escuelas_profesionales (
    id_escuela SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_facultad TINYINT UNSIGNED NOT NULL,
    codigo_escuela CHAR(8) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    duracion_anios TINYINT UNSIGNED NOT NULL DEFAULT 5,
    director VARCHAR(150) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_escuelas_facultades 
        FOREIGN KEY (id_facultad) REFERENCES facultades(id_facultad) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE planes_estudio (
    id_plan SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_escuela SMALLINT UNSIGNED NOT NULL,
    codigo_plan VARCHAR(20) NOT NULL,
    nombre_plan VARCHAR(100) NOT NULL,
    anio_vigencia YEAR NOT NULL,
    creditos_totales SMALLINT UNSIGNED NOT NULL,
    estado ENUM('VIGENTE', 'NO_VIGENTE', 'EXTINTO') NOT NULL DEFAULT 'VIGENTE',
    CONSTRAINT uq_plan_escuela UNIQUE (id_escuela, codigo_plan),
    CONSTRAINT fk_planes_escuelas 
        FOREIGN KEY (id_escuela) REFERENCES escuelas_profesionales(id_escuela) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE cursos (
    id_curso SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_curso VARCHAR(12) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    creditos TINYINT UNSIGNED NOT NULL,
    horas_teoria TINYINT UNSIGNED NOT NULL DEFAULT 0,
    horas_practica TINYINT UNSIGNED NOT NULL DEFAULT 0,
    tipo ENUM('OBLIGATORIO', 'ELECTIVO') NOT NULL DEFAULT 'OBLIGATORIO',
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE malla_curricular (
    id_malla INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_plan SMALLINT UNSIGNED NOT NULL,
    id_curso SMALLINT UNSIGNED NOT NULL,
    ciclo TINYINT UNSIGNED NOT NULL,
    CONSTRAINT uq_malla_plan_curso UNIQUE (id_plan, id_curso),
    CONSTRAINT fk_malla_planes 
        FOREIGN KEY (id_plan) REFERENCES planes_estudio(id_plan) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_malla_cursos 
        FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE prerrequisitos (
    id_prerequisito INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_curso SMALLINT UNSIGNED NOT NULL,
    id_curso_req SMALLINT UNSIGNED NOT NULL,
    CONSTRAINT uq_curso_prerequisito UNIQUE (id_curso, id_curso_req),
    CONSTRAINT fk_prereq_curso_principal 
        FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_prereq_curso_requerido 
        FOREIGN KEY (id_curso_req) REFERENCES cursos(id_curso) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 3. MÓDULO DE ESTUDIANTE Y BIENESTAR UNIVERSITARIO
CREATE TABLE estudiantes (
    id_estudiante INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT UNSIGNED NOT NULL UNIQUE,
    id_escuela SMALLINT UNSIGNED NOT NULL,
    id_plan SMALLINT UNSIGNED NOT NULL,
    codigo_universitario VARCHAR(15) NOT NULL UNIQUE,
    anio_ingreso YEAR NOT NULL,
    modalidad_ingreso VARCHAR(80) NOT NULL,
    ciclo_actual TINYINT UNSIGNED NOT NULL DEFAULT 1,
    estado_academico ENUM('REGULAR', 'OBSERVADO', 'EGRESADO', 'RETIRADO') NOT NULL DEFAULT 'REGULAR',
    promedio_ponderado DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    promedio_semestre_anterior DECIMAL(4,2) NULL DEFAULT NULL,
    creditos_aprobados SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_estudiantes_usuarios 
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_estudiantes_escuelas 
        FOREIGN KEY (id_escuela) REFERENCES escuelas_profesionales(id_escuela) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_estudiantes_planes 
        FOREIGN KEY (id_plan) REFERENCES planes_estudio(id_plan) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE ficha_medica (
    id_ficha INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT UNSIGNED NOT NULL UNIQUE,
    grupo_sanguineo VARCHAR(5) NULL,
    peso_kg DECIMAL(5,2) NULL,
    talla_m DECIMAL(3,2) NULL,
    alergias TEXT NULL,
    antecedentes_medicos TEXT NULL,
    contacto_emergencia_nombre VARCHAR(100) NULL,
    contacto_emergencia_telefono VARCHAR(15) NULL,
    apto_medico TINYINT(1) NOT NULL DEFAULT 0,
    fecha_examen DATE NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ficha_estudiantes 
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) 
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE resoluciones_estudiante (
    id_resolucion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT UNSIGNED NOT NULL,
    tipo_resolucion ENUM('AMPLIACION_CREDITOS', 'CONVALIDACION', 'TRASLADO', 'REINCORPORACION', 'EXCEPCION_MATRICULA') NOT NULL,
    numero_doc VARCHAR(60) NOT NULL UNIQUE,
    descripcion TEXT NOT NULL,
    fecha_efecto DATE NOT NULL,
    id_admin INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resoluciones_estudiantes 
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_resoluciones_admin 
        FOREIGN KEY (id_admin) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 4. MÓDULO DE PERIODOS, INFRAESTRUCTURA Y OFERTA HORARIA
CREATE TABLE periodos_academicos (
    id_periodo SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_periodo VARCHAR(10) NOT NULL UNIQUE,
    anio YEAR NOT NULL,
    semestre TINYINT UNSIGNED NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    inicio_matricula DATETIME NOT NULL,
    fin_matricula DATETIME NOT NULL,
    inicio_rectificacion DATETIME NOT NULL,
    fin_rectificacion DATETIME NOT NULL,
    estado ENUM('PROGRAMADO', 'ACTIVO', 'CERRADO') NOT NULL DEFAULT 'PROGRAMADO',
    CONSTRAINT chk_periodos_fechas CHECK (fecha_fin > fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE aulas (
    id_aula SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_facultad TINYINT UNSIGNED NOT NULL,
    codigo_aula VARCHAR(15) NOT NULL UNIQUE,
    nombre VARCHAR(80) NOT NULL,
    tipo ENUM('TEORIA', 'LABORATORIO', 'VIRTUAL') NOT NULL DEFAULT 'TEORIA',
    aforo_maximo SMALLINT UNSIGNED NOT NULL,
    equipamiento VARCHAR(200) NULL,
    estado ENUM('DISPONIBLE', 'MANTENIMIENTO', 'CLAUSURADO') NOT NULL DEFAULT 'DISPONIBLE',
    CONSTRAINT fk_aulas_facultades 
        FOREIGN KEY (id_facultad) REFERENCES facultades(id_facultad) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE secciones (
    id_seccion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_periodo SMALLINT UNSIGNED NOT NULL,
    id_curso SMALLINT UNSIGNED NOT NULL,
    id_aula SMALLINT UNSIGNED NOT NULL,
    id_docente INT UNSIGNED NOT NULL,
    codigo_seccion VARCHAR(5) NOT NULL,
    turno ENUM('MANANA', 'TARDE', 'NOCHE') NOT NULL DEFAULT 'MANANA',
    vacantes_total SMALLINT UNSIGNED NOT NULL,
    vacantes_ocupadas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    vacantes_extra SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT uq_seccion_periodo_curso UNIQUE (id_periodo, id_curso, codigo_seccion),
    CONSTRAINT chk_secciones_vacantes CHECK (vacantes_ocupadas <= vacantes_total + vacantes_extra),
    CONSTRAINT fk_secciones_periodos 
        FOREIGN KEY (id_periodo) REFERENCES periodos_academicos(id_periodo) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_secciones_cursos 
        FOREIGN KEY (id_curso) REFERENCES cursos(id_curso) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_secciones_aulas 
        FOREIGN KEY (id_aula) REFERENCES aulas(id_aula) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_secciones_docente 
        FOREIGN KEY (id_docente) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE horarios_seccion (
    id_horario INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_seccion INT UNSIGNED NOT NULL,
    dia_semana ENUM('LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    CONSTRAINT chk_horarios_rango CHECK (hora_fin > hora_inicio),
    CONSTRAINT fk_horarios_secciones 
        FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion) 
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. MÓDULO FINANCIERO (CONCEPTOS, DEUDAS Y PAGOS)
CREATE TABLE conceptos_pago (
    id_concepto SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_tupa VARCHAR(15) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    monto DECIMAL(8,2) NOT NULL,
    tipo ENUM('MATRICULA', 'TRAMITE', 'PENALIDAD', 'CONSTANCIA') NOT NULL DEFAULT 'MATRICULA',
    vigente TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE deudas_estudiante (
    id_deuda INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT UNSIGNED NOT NULL,
    id_concepto SMALLINT UNSIGNED NOT NULL,
    id_periodo SMALLINT UNSIGNED NOT NULL,
    monto DECIMAL(8,2) NOT NULL,
    estado ENUM('PENDIENTE', 'PAGADO', 'FRACCIONADO', 'ANULADO') NOT NULL DEFAULT 'PENDIENTE',
    fecha_vencimiento DATE NOT NULL,
    CONSTRAINT fk_deudas_estudiantes 
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_deudas_conceptos 
        FOREIGN KEY (id_concepto) REFERENCES conceptos_pago(id_concepto) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_deudas_periodos 
        FOREIGN KEY (id_periodo) REFERENCES periodos_academicos(id_periodo) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE pagos (
    id_pago INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_deuda INT UNSIGNED NOT NULL,
    id_estudiante INT UNSIGNED NOT NULL,
    id_admin INT UNSIGNED NULL,
    monto_pagado DECIMAL(8,2) NOT NULL,
    metodo_pago ENUM('VENTANILLA', 'BANCO', 'TARJETA', 'TRANSFERENCIA') NOT NULL,
    nro_operacion VARCHAR(60) NOT NULL UNIQUE,
    fecha_pago TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagos_deudas 
        FOREIGN KEY (id_deuda) REFERENCES deudas_estudiante(id_deuda) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pagos_estudiantes 
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pagos_admin 
        FOREIGN KEY (id_admin) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE comprobantes (
    id_comprobante INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pago INT UNSIGNED NOT NULL UNIQUE,
    serie CHAR(4) NOT NULL,
    correlativo INT UNSIGNED NOT NULL,
    tipo_comprobante ENUM('BOLETA', 'FACTURA', 'RECIBO_INTERNO') NOT NULL DEFAULT 'BOLETA',
    monto_total DECIMAL(8,2) NOT NULL,
    hash_qr VARCHAR(255) NULL,
    fecha_emision TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_serie_correlativo UNIQUE (serie, correlativo),
    CONSTRAINT fk_comprobantes_pagos 
        FOREIGN KEY (id_pago) REFERENCES pagos(id_pago) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- 6. MÓDULO DE MATRÍCULA Y REGISTRO ACADÉMICO
-- COLA 1: turnos de matrícula (v4: se agregan hora_turno y hora_fin)
CREATE TABLE cola_matricula (
    id_cola INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT UNSIGNED NOT NULL,
    id_periodo SMALLINT UNSIGNED NOT NULL,
    posicion SMALLINT UNSIGNED NOT NULL,
    estado_cola ENUM('EN_ESPERA', 'EN_TURNO', 'FINALIZADO', 'ABANDONADO') NOT NULL DEFAULT 'EN_ESPERA',
    priorizado TINYINT(1) NOT NULL DEFAULT 0,
    hora_ingreso TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hora_turno TIMESTAMP NULL DEFAULT NULL,
    hora_fin TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT uq_cola_estudiante_periodo UNIQUE (id_estudiante, id_periodo),
    INDEX idx_cola_periodo_estado (id_periodo, estado_cola, priorizado, posicion),
    CONSTRAINT fk_cola_estudiantes 
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cola_periodos 
        FOREIGN KEY (id_periodo) REFERENCES periodos_academicos(id_periodo) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE matriculas (
    id_matricula INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT UNSIGNED NOT NULL,
    id_periodo SMALLINT UNSIGNED NOT NULL,
    tipo_matricula ENUM('REGULAR', 'EXTEMPORANEA', 'ESPECIAL') NOT NULL DEFAULT 'REGULAR',
    estado ENUM('CONFIRMADA', 'PENDIENTE', 'ANULADA', 'OBSERVADA') NOT NULL DEFAULT 'PENDIENTE',
    total_creditos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    creditos_adicionales TINYINT UNSIGNED NOT NULL DEFAULT 0,
    id_admin INT UNSIGNED NULL,
    bypass_cruces TINYINT(1) NOT NULL DEFAULT 0,
    bypass_deuda TINYINT(1) NOT NULL DEFAULT 0,
    bypass_medico TINYINT(1) NOT NULL DEFAULT 0,
    resolucion_excep VARCHAR(80) NULL,
    fecha_matricula TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    nro_constancia VARCHAR(25) NOT NULL UNIQUE,
    CONSTRAINT uq_matricula_estudiante_periodo UNIQUE (id_estudiante, id_periodo),
    CONSTRAINT fk_matriculas_estudiantes 
        FOREIGN KEY (id_estudiante) REFERENCES estudiantes(id_estudiante) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_matriculas_periodos 
        FOREIGN KEY (id_periodo) REFERENCES periodos_academicos(id_periodo) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_matriculas_admin 
        FOREIGN KEY (id_admin) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE detalle_matricula (
    id_detalle INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT UNSIGNED NOT NULL,
    id_seccion INT UNSIGNED NOT NULL,
    estado ENUM('INSCRITO', 'RETIRADO', 'CONVALIDADO') NOT NULL DEFAULT 'INSCRITO',
    nota_final DECIMAL(4,2) NULL,
    nota_teoria DECIMAL(4,2) NULL,
    nota_practica DECIMAL(4,2) NULL,
    origen ENUM('PRIMERA_VEZ', 'SEGUNDA_VEZ', 'TERCERA_VEZ') NOT NULL DEFAULT 'PRIMERA_VEZ',
    CONSTRAINT uq_detalle_mat_seccion UNIQUE (id_matricula, id_seccion),
    CONSTRAINT chk_detalle_nota_final CHECK (nota_final IS NULL OR (nota_final >= 0.00 AND nota_final <= 20.00)),
    CONSTRAINT chk_detalle_nota_teoria CHECK (nota_teoria IS NULL OR (nota_teoria >= 0.00 AND nota_teoria <= 20.00)),
    CONSTRAINT chk_detalle_nota_practica CHECK (nota_practica IS NULL OR (nota_practica >= 0.00 AND nota_practica <= 20.00)),
    CONSTRAINT fk_detalle_matriculas 
        FOREIGN KEY (id_matricula) REFERENCES matriculas(id_matricula) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_secciones 
        FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- v4: se agrega DEUDA_PENDIENTE para registrar el bypass de deuda con resolución
CREATE TABLE excepciones_matricula (
    id_excepcion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT UNSIGNED NOT NULL,
    tipo_excepcion ENUM('CREDITOS_EXTRAS', 'CRUCE_HORARIO', 'CURSO_DIRIGIDO', 'REQUISITO_MEDICO', 'DEUDA_PENDIENTE') NOT NULL,
    numero_rd VARCHAR(80) NOT NULL,
    descripcion TEXT NOT NULL,
    id_admin INT UNSIGNED NOT NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_excepciones_matriculas 
        FOREIGN KEY (id_matricula) REFERENCES matriculas(id_matricula) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_excepciones_admin 
        FOREIGN KEY (id_admin) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- COLA 2 (NUEVA): lista de espera por sección sin vacantes
CREATE TABLE lista_espera_seccion (
    id_espera INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_seccion INT UNSIGNED NOT NULL,
    id_matricula INT UNSIGNED NOT NULL,
    posicion SMALLINT UNSIGNED NOT NULL,
    estado ENUM('EN_ESPERA', 'ASIGNADO', 'CANCELADO', 'EXPIRADO') NOT NULL DEFAULT 'EN_ESPERA',
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_atencion TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT uq_espera_seccion_matricula UNIQUE (id_seccion, id_matricula),
    INDEX idx_espera_cola (id_seccion, estado, posicion),
    CONSTRAINT fk_espera_secciones 
        FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_espera_matriculas 
        FOREIGN KEY (id_matricula) REFERENCES matriculas(id_matricula) 
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. MÓDULO DE RECTIFICACIONES DE MATRÍCULA
-- COLA 3: las filas con estado SOLICITADO forman la cola FIFO de solicitudes
CREATE TABLE rectificaciones (
    id_rectificacion INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT UNSIGNED NOT NULL,
    id_admin INT UNSIGNED NULL,
    nro_acta VARCHAR(35) NOT NULL UNIQUE,
    estado ENUM('SOLICITADO', 'APROBADO', 'RECHAZADO') NOT NULL DEFAULT 'SOLICITADO',
    observaciones TEXT NULL,
    fecha_inicio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_confirmacion TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_rectificaciones_matriculas 
        FOREIGN KEY (id_matricula) REFERENCES matriculas(id_matricula) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rectificaciones_admin 
        FOREIGN KEY (id_admin) REFERENCES usuarios(id_usuario) 
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE detalle_rectificacion (
    id_det_rectif INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_rectificacion INT UNSIGNED NOT NULL,
    id_seccion INT UNSIGNED NOT NULL,
    tipo_movimiento ENUM('RETIRO', 'AGREGAR', 'CAMBIO_SECCION') NOT NULL,
    seccion_destino INT UNSIGNED NULL,
    CONSTRAINT fk_det_rectif_cabecera 
        FOREIGN KEY (id_rectificacion) REFERENCES rectificaciones(id_rectificacion) 
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_det_rectif_seccion_origen 
        FOREIGN KEY (id_seccion) REFERENCES secciones(id_seccion) 
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_det_rectif_seccion_destino 
        FOREIGN KEY (seccion_destino) REFERENCES secciones(id_seccion) 
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
