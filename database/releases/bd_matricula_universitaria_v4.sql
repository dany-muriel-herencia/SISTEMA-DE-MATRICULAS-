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

-- Vistas de consulta para el backend
CREATE OR REPLACE VIEW v_cola_matricula AS
SELECT
    cm.id_cola, cm.id_periodo, p.codigo_periodo, cm.posicion, cm.priorizado, cm.estado_cola,
    e.id_estudiante, e.codigo_universitario,
    CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
    e.promedio_semestre_anterior,
    cm.hora_ingreso, cm.hora_turno, cm.hora_fin,
    TIMESTAMPDIFF(MINUTE, cm.hora_ingreso, COALESCE(cm.hora_turno, NOW())) AS minutos_espera
FROM cola_matricula cm
JOIN periodos_academicos p ON p.id_periodo = cm.id_periodo
JOIN estudiantes e         ON e.id_estudiante = cm.id_estudiante
JOIN usuarios u            ON u.id_usuario = e.id_usuario;

CREATE OR REPLACE VIEW v_lista_espera AS
SELECT
    le.id_espera, le.id_seccion, c.codigo_curso, c.nombre AS curso, s.codigo_seccion,
    le.id_matricula, e.codigo_universitario,
    CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
    le.posicion, le.estado, le.fecha_registro, le.fecha_atencion
FROM lista_espera_seccion le
JOIN secciones s   ON s.id_seccion = le.id_seccion
JOIN cursos c      ON c.id_curso = s.id_curso
JOIN matriculas m  ON m.id_matricula = le.id_matricula
JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
JOIN usuarios u    ON u.id_usuario = e.id_usuario;

CREATE OR REPLACE VIEW v_rectificaciones_pendientes AS
SELECT
    r.id_rectificacion, r.nro_acta, r.fecha_inicio,
    m.id_matricula, m.id_periodo, p.codigo_periodo,
    e.id_estudiante, e.codigo_universitario,
    CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
    dr.tipo_movimiento, dr.id_seccion AS seccion_origen, dr.seccion_destino,
    ROW_NUMBER() OVER (PARTITION BY m.id_periodo ORDER BY r.fecha_inicio, r.id_rectificacion) AS orden_cola
FROM rectificaciones r
JOIN detalle_rectificacion dr ON dr.id_rectificacion = r.id_rectificacion
JOIN matriculas m             ON m.id_matricula = r.id_matricula
JOIN periodos_academicos p    ON p.id_periodo = m.id_periodo
JOIN estudiantes e            ON e.id_estudiante = m.id_estudiante
JOIN usuarios u               ON u.id_usuario = e.id_usuario
WHERE r.estado = 'SOLICITADO';


-- =============================================================================
-- PARTE 2: FUNCIONES ALMACENADAS
-- =============================================================================

DELIMITER $$

-- ---- Parámetros del reglamento en un solo lugar -----------------------------
DROP FUNCTION IF EXISTS fn_nota_minima$$
CREATE FUNCTION fn_nota_minima() RETURNS DECIMAL(4,2)
DETERMINISTIC NO SQL
RETURN 10.50$$

DROP FUNCTION IF EXISTS fn_limite_creditos_base$$
CREATE FUNCTION fn_limite_creditos_base() RETURNS TINYINT UNSIGNED
DETERMINISTIC NO SQL
RETURN 22$$

DROP FUNCTION IF EXISTS fn_promedio_merito$$
CREATE FUNCTION fn_promedio_merito() RETURNS DECIMAL(4,2)
DETERMINISTIC NO SQL
RETURN 14.00$$

-- ---- 1. fn_detectar_cruce_horario -------------------------------------------
-- Intervalo [A1,A2] solapa con [B1,B2] <=> A1 < B2 Y A2 > B1 en el mismo día.
-- Solo compara contra cursos INSCRITOS de la matrícula. Retorna 1 si hay cruce.
DROP FUNCTION IF EXISTS fn_detectar_cruce_horario$$
CREATE FUNCTION fn_detectar_cruce_horario(
    p_id_matricula INT UNSIGNED,
    p_id_seccion   INT UNSIGNED
) 
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_cruce INT DEFAULT 0;

    IF NOT EXISTS (SELECT 1 FROM matriculas WHERE id_matricula = p_id_matricula) THEN
        RETURN 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM secciones WHERE id_seccion = p_id_seccion) THEN
        RETURN 0;
    END IF;

    SELECT COUNT(*) INTO v_cruce
    FROM horarios_seccion h_nueva
    JOIN horarios_seccion h_existente 
        ON h_nueva.dia_semana = h_existente.dia_semana
       AND h_nueva.hora_inicio < h_existente.hora_fin
       AND h_nueva.hora_fin > h_existente.hora_inicio
    JOIN detalle_matricula dm 
        ON dm.id_seccion = h_existente.id_seccion
    WHERE h_nueva.id_seccion = p_id_seccion
      AND dm.id_matricula = p_id_matricula
      AND dm.estado = 'INSCRITO'
      AND h_existente.id_seccion != p_id_seccion;

    RETURN IF(v_cruce > 0, 1, 0);
END$$

-- ---- 2. fn_validar_prerrequisitos -------------------------------------------
-- Cumple un prerrequisito si el estudiante lo aprobó (nota >= mínima) en un
-- período CERRADO, o si el curso fue CONVALIDADO. Excluye matrículas anuladas
-- y retiros. Retorna 1 si cumple todos (o el curso no tiene prerrequisitos).
DROP FUNCTION IF EXISTS fn_validar_prerrequisitos$$
CREATE FUNCTION fn_validar_prerrequisitos(
    p_id_estudiante INT UNSIGNED,
    p_id_curso      SMALLINT UNSIGNED
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_prereq     INT DEFAULT 0;
    DECLARE v_prereq_cumplidos INT DEFAULT 0;

    IF NOT EXISTS (SELECT 1 FROM estudiantes WHERE id_estudiante = p_id_estudiante) THEN
        RETURN 0;
    END IF;

    SELECT COUNT(*) INTO v_total_prereq
    FROM prerrequisitos
    WHERE id_curso = p_id_curso;

    IF v_total_prereq = 0 THEN
        RETURN 1;
    END IF;

    SELECT COUNT(DISTINCT pr.id_curso_req) INTO v_prereq_cumplidos
    FROM prerrequisitos pr
    JOIN secciones s ON s.id_curso = pr.id_curso_req
    JOIN detalle_matricula dm ON dm.id_seccion = s.id_seccion
    JOIN matriculas m ON m.id_matricula = dm.id_matricula
    JOIN periodos_academicos pa ON pa.id_periodo = m.id_periodo
    WHERE pr.id_curso = p_id_curso
      AND m.id_estudiante = p_id_estudiante
      AND m.estado != 'ANULADA'
      AND (
            (dm.estado = 'INSCRITO'
             AND dm.nota_final IS NOT NULL
             AND dm.nota_final >= fn_nota_minima()
             AND pa.estado = 'CERRADO')
            OR dm.estado = 'CONVALIDADO'
          );

    RETURN IF(v_prereq_cumplidos >= v_total_prereq, 1, 0);
END$$

-- ---- 3. fn_vacantes_disponibles ---------------------------------------------
-- Retorna INT (NULL si la sección no existe; >= 0 si existe).
DROP FUNCTION IF EXISTS fn_vacantes_disponibles$$
CREATE FUNCTION fn_vacantes_disponibles(
    p_id_seccion INT UNSIGNED
)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_disponibles INT DEFAULT NULL;

    SELECT (CAST(vacantes_total AS SIGNED) + CAST(vacantes_extra AS SIGNED)) - CAST(vacantes_ocupadas AS SIGNED)
    INTO v_disponibles
    FROM secciones
    WHERE id_seccion = p_id_seccion;

    IF v_disponibles IS NULL THEN
        RETURN NULL;
    END IF;

    RETURN GREATEST(v_disponibles, 0);
END$$

-- ---- 4. fn_calcular_creditos_matricula --------------------------------------
-- Suma real de créditos de los cursos INSCRITOS (sirve para auditar total_creditos).
DROP FUNCTION IF EXISTS fn_calcular_creditos_matricula$$
CREATE FUNCTION fn_calcular_creditos_matricula(
    p_id_matricula INT UNSIGNED
)
RETURNS SMALLINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_creditos SMALLINT UNSIGNED DEFAULT 0;

    SELECT COALESCE(SUM(c.creditos), 0)
    INTO v_total_creditos
    FROM detalle_matricula dm
    JOIN secciones s ON s.id_seccion = dm.id_seccion
    JOIN cursos c ON c.id_curso = s.id_curso
    WHERE dm.id_matricula = p_id_matricula
      AND dm.estado = 'INSCRITO';

    RETURN v_total_creditos;
END$$

-- ---- 5. fn_validar_limite_creditos ------------------------------------------
-- 1 si total_creditos + nuevos <= límite base + creditos_adicionales; 0 si excede
-- o si la matrícula no existe.
DROP FUNCTION IF EXISTS fn_validar_limite_creditos$$
CREATE FUNCTION fn_validar_limite_creditos(
    p_id_matricula    INT UNSIGNED,
    p_creditos_nuevos TINYINT UNSIGNED
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_creditos       TINYINT UNSIGNED DEFAULT NULL;
    DECLARE v_creditos_adicionales TINYINT UNSIGNED DEFAULT 0;

    SELECT total_creditos, creditos_adicionales
    INTO v_total_creditos, v_creditos_adicionales
    FROM matriculas
    WHERE id_matricula = p_id_matricula;

    IF v_total_creditos IS NULL THEN
        RETURN 0;
    END IF;

    IF (CAST(v_total_creditos AS UNSIGNED) + CAST(p_creditos_nuevos AS UNSIGNED))
       <= (CAST(fn_limite_creditos_base() AS UNSIGNED) + CAST(v_creditos_adicionales AS UNSIGNED)) THEN
        RETURN 1;
    END IF;

    RETURN 0;
END$$

-- ---- 6. fn_validar_curso_en_plan --------------------------------------------
DROP FUNCTION IF EXISTS fn_validar_curso_en_plan$$
CREATE FUNCTION fn_validar_curso_en_plan(
    p_id_estudiante INT UNSIGNED,
    p_id_curso      SMALLINT UNSIGNED
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id_plan   SMALLINT UNSIGNED DEFAULT NULL;
    DECLARE v_pertenece INT DEFAULT 0;

    SELECT id_plan INTO v_id_plan
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_id_plan IS NULL THEN
        RETURN 0;
    END IF;

    SELECT COUNT(*) INTO v_pertenece
    FROM malla_curricular
    WHERE id_plan = v_id_plan
      AND id_curso = p_id_curso;

    RETURN IF(v_pertenece > 0, 1, 0);
END$$

-- ---- 7. fn_calcular_saldo_deuda ---------------------------------------------
DROP FUNCTION IF EXISTS fn_calcular_saldo_deuda$$
CREATE FUNCTION fn_calcular_saldo_deuda(
    p_id_deuda INT UNSIGNED
)
RETURNS DECIMAL(8,2)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_monto_deuda DECIMAL(8,2) DEFAULT NULL;
    DECLARE v_total_pagos DECIMAL(8,2) DEFAULT 0.00;

    SELECT monto INTO v_monto_deuda
    FROM deudas_estudiante
    WHERE id_deuda = p_id_deuda;

    IF v_monto_deuda IS NULL THEN
        RETURN NULL;
    END IF;

    SELECT COALESCE(SUM(monto_pagado), 0.00) INTO v_total_pagos
    FROM pagos
    WHERE id_deuda = p_id_deuda;

    RETURN GREATEST(v_monto_deuda - v_total_pagos, 0.00);
END$$

-- ---- 8. fn_creditos_adicionales (NUEVA: regla única de créditos extra) ------
-- +4 créditos si el promedio del semestre anterior es >= fn_promedio_merito(),
-- o si el estudiante tiene una resolución AMPLIACION_CREDITOS vigente: con
-- fecha_efecto ya cumplida y dentro del MISMO AÑO del período. (La tabla
-- resoluciones_estudiante no guarda cantidad de créditos; la ampliación es fija.)
DROP FUNCTION IF EXISTS fn_creditos_adicionales$$
CREATE FUNCTION fn_creditos_adicionales(
    p_id_estudiante INT UNSIGNED,
    p_id_periodo    SMALLINT UNSIGNED
)
RETURNS TINYINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_promedio DECIMAL(4,2) DEFAULT NULL;
    DECLARE v_anio     SMALLINT DEFAULT NULL;

    SELECT promedio_semestre_anterior INTO v_promedio
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_promedio IS NOT NULL AND v_promedio >= fn_promedio_merito() THEN
        RETURN 4;
    END IF;

    SELECT anio INTO v_anio
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo;

    IF v_anio IS NOT NULL AND EXISTS (
        SELECT 1 FROM resoluciones_estudiante
        WHERE id_estudiante = p_id_estudiante
          AND tipo_resolucion = 'AMPLIACION_CREDITOS'
          AND fecha_efecto <= CURDATE()
          AND YEAR(fecha_efecto) = v_anio
    ) THEN
        RETURN 4;
    END IF;

    RETURN 0;
END$$


-- =============================================================================
-- PARTE 3: TRIGGERS
-- =============================================================================
-- v4 NO usa triggers. Toda la mutación (créditos, vacantes, estados de deuda,
-- créditos adicionales, auditoría) queda explícita en procedimientos
-- transaccionales, con orden de bloqueo controlado: matriculas -> secciones.


-- =============================================================================
-- PARTE 4: PROCEDIMIENTOS INTERNOS (sp_i_*) — SIN TRANSACCIÓN PROPIA
-- =============================================================================

-- -----------------------------------------------------------------------------
-- sp_i_inscribir_curso
-- p_modo = 'MATRICULA' : exige estar dentro de la ventana de matrícula.
-- p_modo = 'INTERNO'   : omite la ventana (ya la validó el llamador: rectificación
--                        aprobada o promoción desde lista de espera). El período
--                        debe seguir ACTIVO en ambos modos.
-- Orden de bloqueos: matriculas -> secciones. Si la sección ya se retiró antes
-- en la misma matrícula, se REACTIVA la fila (evita violar uq_detalle_mat_seccion).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_i_inscribir_curso$$
CREATE PROCEDURE sp_i_inscribir_curso(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    IN  p_modo         VARCHAR(10),
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_ins: BEGIN
    DECLARE v_id_estudiante     INT UNSIGNED;
    DECLARE v_id_periodo_mat    SMALLINT UNSIGNED;
    DECLARE v_estado_matricula  VARCHAR(20);
    DECLARE v_bypass_cruces     TINYINT(1) DEFAULT 0;
    DECLARE v_id_curso          SMALLINT UNSIGNED;
    DECLARE v_id_periodo_sec    SMALLINT UNSIGNED;
    DECLARE v_creditos_curso    TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_vac_total         SMALLINT UNSIGNED;
    DECLARE v_vac_ocup          SMALLINT UNSIGNED;
    DECLARE v_vac_extra         SMALLINT UNSIGNED;
    DECLARE v_inicio_matricula  DATETIME;
    DECLARE v_fin_matricula     DATETIME;
    DECLARE v_estado_periodo    VARCHAR(20);
    DECLARE v_veces_desaprobado INT DEFAULT 0;
    DECLARE v_origen_calc       VARCHAR(20);
    DECLARE v_id_detalle_prev   INT UNSIGNED;

    -- PASO 1: bloquear la MATRÍCULA
    SELECT m.id_estudiante, m.id_periodo, m.estado, m.bypass_cruces
    INTO   v_id_estudiante, v_id_periodo_mat, v_estado_matricula, v_bypass_cruces
    FROM   matriculas m
    WHERE  m.id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_id_estudiante IS NULL THEN
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'No se encontró la matrícula indicada.';
        LEAVE proc_ins;
    END IF;

    IF v_estado_matricula NOT IN ('PENDIENTE', 'CONFIRMADA') THEN
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula se encuentra en estado "', v_estado_matricula, '" y no admite inscripciones.');
        LEAVE proc_ins;
    END IF;

    -- PASO 2: período
    SELECT inicio_matricula, fin_matricula, estado
    INTO   v_inicio_matricula, v_fin_matricula, v_estado_periodo
    FROM   periodos_academicos
    WHERE  id_periodo = v_id_periodo_mat;

    IF v_estado_periodo IS NULL THEN
        SET p_codigo_resp = 'PERIODO_NO_EXISTE';
        SET p_mensaje     = 'El período académico de la matrícula no existe en el sistema.';
        LEAVE proc_ins;
    END IF;

    IF v_estado_periodo <> 'ACTIVO'
       OR (p_modo = 'MATRICULA' AND (NOW() < v_inicio_matricula OR NOW() > v_fin_matricula)) THEN
        SET p_codigo_resp = 'FUERA_DE_PERIODO';
        SET p_mensaje     = 'El período de inscripción no está activo o se encuentra fuera de fechas hábiles.';
        LEAVE proc_ins;
    END IF;

    -- PASO 3: bloquear la SECCIÓN
    SELECT s.id_curso, s.id_periodo, s.vacantes_total, s.vacantes_ocupadas, s.vacantes_extra, c.creditos
    INTO   v_id_curso, v_id_periodo_sec, v_vac_total, v_vac_ocup, v_vac_extra, v_creditos_curso
    FROM   secciones s
    JOIN   cursos c ON c.id_curso = s.id_curso
    WHERE  s.id_seccion = p_id_seccion
    FOR UPDATE;

    IF v_id_curso IS NULL THEN
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe en el sistema.';
        LEAVE proc_ins;
    END IF;

    IF v_id_periodo_sec <> v_id_periodo_mat THEN
        SET p_codigo_resp = 'SECCION_OTRO_PERIODO';
        SET p_mensaje     = 'La sección pertenece a un período académico distinto al de la matrícula.';
        LEAVE proc_ins;
    END IF;

    -- PASO 4: vacantes (tras el bloqueo pesimista)
    IF ((CAST(v_vac_total AS SIGNED) + CAST(v_vac_extra AS SIGNED)) - CAST(v_vac_ocup AS SIGNED)) <= 0 THEN
        SET p_codigo_resp = 'SIN_VACANTES';
        SET p_mensaje     = 'No quedan vacantes en la sección. Puede anotarse en la lista de espera.';
        LEAVE proc_ins;
    END IF;

    -- PASO 5: reglas de negocio
    IF EXISTS (
        SELECT 1 FROM detalle_matricula 
        WHERE id_matricula = p_id_matricula AND id_seccion = p_id_seccion AND estado = 'INSCRITO'
    ) THEN
        SET p_codigo_resp = 'YA_INSCRITO';
        SET p_mensaje     = 'El estudiante ya se encuentra registrado en esta sección.';
        LEAVE proc_ins;
    END IF;

    IF EXISTS (
        SELECT 1 FROM detalle_matricula dm
        JOIN secciones sec ON sec.id_seccion = dm.id_seccion
        WHERE dm.id_matricula = p_id_matricula 
          AND sec.id_curso = v_id_curso 
          AND dm.estado = 'INSCRITO'
    ) THEN
        SET p_codigo_resp = 'CURSO_DUPLICADO';
        SET p_mensaje     = 'El estudiante ya cuenta con otra sección inscrita para este mismo curso.';
        LEAVE proc_ins;
    END IF;

    IF fn_validar_curso_en_plan(v_id_estudiante, v_id_curso) = 0 THEN
        SET p_codigo_resp = 'CURSO_NO_EN_PLAN';
        SET p_mensaje     = 'La asignatura no pertenece al plan de estudios curricular del estudiante.';
        LEAVE proc_ins;
    END IF;

    IF fn_validar_prerrequisitos(v_id_estudiante, v_id_curso) = 0 THEN
        SET p_codigo_resp = 'FALTAN_PRERREQUISITOS';
        SET p_mensaje     = 'El estudiante no ha aprobado todos los prerrequisitos de esta asignatura en períodos cerrados.';
        LEAVE proc_ins;
    END IF;

    IF v_bypass_cruces = 0 AND fn_detectar_cruce_horario(p_id_matricula, p_id_seccion) = 1 THEN
        SET p_codigo_resp = 'CRUCE_HORARIO';
        SET p_mensaje     = 'La sección seleccionada presenta cruce de horario con otra asignatura ya inscrita.';
        LEAVE proc_ins;
    END IF;

    -- PASO 6: límite de créditos
    IF fn_validar_limite_creditos(p_id_matricula, v_creditos_curso) = 0 THEN
        SET p_codigo_resp = 'LIMITE_CREDITOS';
        SET p_mensaje     = CONCAT('Supera el límite de créditos permitidos. Curso solicitado: ',
                                   v_creditos_curso, ' créditos.');
        LEAVE proc_ins;
    END IF;

    -- PASO 7: origen según desaprobaciones previas
    SELECT COUNT(*) INTO v_veces_desaprobado
    FROM detalle_matricula dm
    JOIN secciones sec ON sec.id_seccion = dm.id_seccion
    JOIN matriculas mat ON mat.id_matricula = dm.id_matricula
    WHERE mat.id_estudiante = v_id_estudiante
      AND sec.id_curso = v_id_curso
      AND dm.nota_final IS NOT NULL
      AND dm.nota_final < fn_nota_minima();

    IF v_veces_desaprobado = 0 THEN
        SET v_origen_calc = 'PRIMERA_VEZ';
    ELSEIF v_veces_desaprobado = 1 THEN
        SET v_origen_calc = 'SEGUNDA_VEZ';
    ELSE
        SET v_origen_calc = 'TERCERA_VEZ';
    END IF;

    -- PASO 8: inscribir (reactivando la fila si había sido retirada)
    SELECT id_detalle INTO v_id_detalle_prev
    FROM detalle_matricula
    WHERE id_matricula = p_id_matricula AND id_seccion = p_id_seccion AND estado = 'RETIRADO'
    LIMIT 1;

    IF v_id_detalle_prev IS NOT NULL THEN
        UPDATE detalle_matricula
        SET estado = 'INSCRITO', origen = v_origen_calc
        WHERE id_detalle = v_id_detalle_prev;
    ELSE
        INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, origen)
        VALUES (p_id_matricula, p_id_seccion, 'INSCRITO', v_origen_calc);
    END IF;

    UPDATE secciones 
    SET vacantes_ocupadas = vacantes_ocupadas + 1
    WHERE id_seccion = p_id_seccion;

    UPDATE matriculas 
    SET total_creditos = total_creditos + v_creditos_curso
    WHERE id_matricula = p_id_matricula;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Asignatura inscrita correctamente en la matrícula.';
END proc_ins$$

-- -----------------------------------------------------------------------------
-- sp_i_espera_promover
-- Mientras la sección tenga vacantes, asigna al primero de su lista de espera.
-- Si no cumple las reglas (prerrequisitos, cruce, créditos...), queda EXPIRADO y
-- se continúa con el siguiente. NOTA: bloquea la matrícula del promovido estando
-- ya bloqueada la sección (orden inverso al canónico); si hay deadlock, InnoDB
-- aborta una transacción y el backend reintenta (ver convenciones).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_i_espera_promover$$
CREATE PROCEDURE sp_i_espera_promover(IN p_id_seccion INT UNSIGNED)
BEGIN
    DECLARE v_seguir       TINYINT DEFAULT 1;
    DECLARE v_id_espera    INT UNSIGNED;
    DECLARE v_id_matricula INT UNSIGNED;
    DECLARE v_resp         VARCHAR(25);
    DECLARE v_msg          VARCHAR(255);

    WHILE v_seguir = 1 DO
        SET v_id_espera = NULL;

        IF COALESCE(fn_vacantes_disponibles(p_id_seccion), 0) <= 0 THEN
            SET v_seguir = 0;
        ELSE
            SELECT id_espera, id_matricula INTO v_id_espera, v_id_matricula
            FROM lista_espera_seccion
            WHERE id_seccion = p_id_seccion AND estado = 'EN_ESPERA'
            ORDER BY posicion ASC
            LIMIT 1
            FOR UPDATE;

            IF v_id_espera IS NULL THEN
                SET v_seguir = 0;
            ELSE
                CALL sp_i_inscribir_curso(v_id_matricula, p_id_seccion, 'INTERNO', v_resp, v_msg);

                IF v_resp = 'EXITO' THEN
                    UPDATE lista_espera_seccion
                    SET estado = 'ASIGNADO', fecha_atencion = NOW()
                    WHERE id_espera = v_id_espera;

                    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
                    VALUES (NULL, 'MATRICULA', 'ESPERA_ASIGNADA', 'lista_espera_seccion', v_id_espera,
                            CONCAT('Matrícula ', v_id_matricula, ' asignada a la sección ', p_id_seccion));
                ELSE
                    UPDATE lista_espera_seccion
                    SET estado = 'EXPIRADO', fecha_atencion = NOW()
                    WHERE id_espera = v_id_espera;

                    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
                    VALUES (NULL, 'MATRICULA', 'ESPERA_EXPIRADA', 'lista_espera_seccion', v_id_espera,
                            CONCAT('No se pudo asignar: ', v_resp));
                END IF;
            END IF;
        END IF;
    END WHILE;
END$$

-- -----------------------------------------------------------------------------
-- sp_i_retirar_curso
-- Retira el curso (UPDATE condicional atómico + ROW_COUNT), descuenta créditos,
-- libera la vacante y PROMUEVE al primero de la lista de espera de esa sección.
-- Orden de bloqueos: matriculas -> secciones.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_i_retirar_curso$$
CREATE PROCEDURE sp_i_retirar_curso(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_ret: BEGIN
    DECLARE v_estado_mat VARCHAR(20);
    DECLARE v_creditos   TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_id_usuario INT UNSIGNED;
    DECLARE v_dummy_sec  INT UNSIGNED;

    SELECT m.estado, e.id_usuario
    INTO   v_estado_mat, v_id_usuario
    FROM   matriculas m
    JOIN   estudiantes e ON e.id_estudiante = m.id_estudiante
    WHERE  m.id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_estado_mat IS NULL THEN
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'La matrícula indicada no existe.';
        LEAVE proc_ret;
    END IF;

    IF v_estado_mat = 'ANULADA' THEN
        SET p_codigo_resp = 'MATRICULA_ANULADA';
        SET p_mensaje     = 'No se pueden retirar cursos de una matrícula anulada.';
        LEAVE proc_ret;
    END IF;

    SELECT s.id_seccion, c.creditos
    INTO   v_dummy_sec, v_creditos
    FROM   secciones s
    JOIN   cursos c ON c.id_curso = s.id_curso
    WHERE  s.id_seccion = p_id_seccion
    FOR UPDATE;

    IF v_dummy_sec IS NULL THEN
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe en el sistema.';
        LEAVE proc_ret;
    END IF;

    UPDATE detalle_matricula 
    SET estado = 'RETIRADO'
    WHERE id_matricula = p_id_matricula 
      AND id_seccion = p_id_seccion 
      AND estado = 'INSCRITO';

    IF ROW_COUNT() = 0 THEN
        SET p_codigo_resp = 'NO_INSCRITO';
        SET p_mensaje     = 'El curso no se encuentra en estado INSCRITO o ya fue retirado previamente.';
        LEAVE proc_ret;
    END IF;

    UPDATE matriculas 
    SET total_creditos = GREATEST(CAST(total_creditos AS SIGNED) - CAST(v_creditos AS SIGNED), 0)
    WHERE id_matricula = p_id_matricula;

    UPDATE secciones 
    SET vacantes_ocupadas = GREATEST(CAST(vacantes_ocupadas AS SIGNED) - 1, 0)
    WHERE id_seccion = p_id_seccion;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (v_id_usuario, 'MATRICULA', 'RETIRO_CURSO', 'detalle_matricula', p_id_matricula,
            CONCAT('Retiro de sección ', p_id_seccion, ' (', v_creditos, ' créditos)'));

    -- La vacante liberada pasa al primero de la lista de espera
    CALL sp_i_espera_promover(p_id_seccion);

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Curso retirado exitosamente de la matrícula.';
END proc_ret$$

-- -----------------------------------------------------------------------------
-- sp_crear_matricula_pendiente (auxiliar de TX-01)
-- Valida período, estudiante, duplicidad, DEUDA VENCIDA y APTO MÉDICO. Para
-- saltarse deuda o ficha médica se requiere administrador Y número de resolución;
-- queda registrado en excepciones_matricula y en los flags bypass_*.
-- Créditos adicionales: fn_creditos_adicionales (regla única).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_crear_matricula_pendiente$$
CREATE PROCEDURE sp_crear_matricula_pendiente(
    IN  p_id_estudiante  INT UNSIGNED,
    IN  p_id_periodo     SMALLINT UNSIGNED,
    IN  p_id_admin       INT UNSIGNED,
    IN  p_numero_rd      VARCHAR(80),
    OUT p_id_matricula   INT UNSIGNED,
    OUT p_nro_constancia VARCHAR(25),
    OUT p_codigo_resp    VARCHAR(25),
    OUT p_mensaje        VARCHAR(255)
)
proc_crear_mat: BEGIN
    DECLARE v_periodo_valido INT DEFAULT 0;
    DECLARE v_codigo_periodo VARCHAR(10);
    DECLARE v_estado_acad    VARCHAR(20);
    DECLARE v_cred_adic      TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_constancia     VARCHAR(25);
    DECLARE v_deuda          INT DEFAULT 0;
    DECLARE v_apto           TINYINT(1) DEFAULT 0;
    DECLARE v_bypass_deuda   TINYINT(1) DEFAULT 0;
    DECLARE v_bypass_medico  TINYINT(1) DEFAULT 0;
    DECLARE v_rd             VARCHAR(80);

    SET p_id_matricula   = NULL;
    SET p_nro_constancia = NULL;
    SET v_rd = NULLIF(TRIM(p_numero_rd), '');

    SELECT COUNT(*), COALESCE(MAX(codigo_periodo), '')
    INTO v_periodo_valido, v_codigo_periodo
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo
      AND estado = 'ACTIVO'
      AND NOW() BETWEEN inicio_matricula AND fin_matricula;

    IF v_periodo_valido = 0 THEN
        SET p_codigo_resp = 'PERIODO_INACTIVO';
        SET p_mensaje     = 'El período académico no existe, no está activo o se encuentra fuera de fechas de matrícula.';
        LEAVE proc_crear_mat;
    END IF;

    SELECT estado_academico INTO v_estado_acad
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_estado_acad IS NULL THEN
        SET p_codigo_resp = 'ESTUDIANTE_NO_EXISTE';
        SET p_mensaje     = 'El estudiante indicado no existe en el sistema.';
        LEAVE proc_crear_mat;
    END IF;

    IF v_estado_acad IN ('EGRESADO', 'RETIRADO') THEN
        SET p_codigo_resp = 'ESTUDIANTE_NO_HABIL';
        SET p_mensaje     = CONCAT('El estudiante presenta estado académico "', v_estado_acad, '" y no puede matricularse.');
        LEAVE proc_crear_mat;
    END IF;

    IF EXISTS (
        SELECT 1 FROM matriculas 
        WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo
    ) THEN
        SET p_codigo_resp = 'MATRICULA_DUPLICADA';
        SET p_mensaje     = 'El estudiante ya cuenta con un registro de matrícula en este período académico.';
        LEAVE proc_crear_mat;
    END IF;

    -- Deuda vencida y ficha médica
    SELECT COUNT(*) INTO v_deuda
    FROM deudas_estudiante
    WHERE id_estudiante = p_id_estudiante
      AND estado IN ('PENDIENTE', 'FRACCIONADO')
      AND fecha_vencimiento <= CURDATE();

    SELECT COALESCE(MAX(apto_medico), 0) INTO v_apto
    FROM ficha_medica
    WHERE id_estudiante = p_id_estudiante;

    IF v_deuda > 0 OR v_apto = 0 THEN
        IF p_id_admin IS NULL THEN
            IF v_deuda > 0 THEN
                SET p_codigo_resp = 'DEUDA_PENDIENTE';
                SET p_mensaje     = 'El estudiante registra deudas pendientes de pago.';
            ELSE
                SET p_codigo_resp = 'SIN_APTO_MEDICO';
                SET p_mensaje     = 'El estudiante no cuenta con la ficha médica aprobada.';
            END IF;
            LEAVE proc_crear_mat;
        END IF;

        IF v_rd IS NULL THEN
            SET p_codigo_resp = 'REQUIERE_RESOLUCION';
            SET p_mensaje     = 'Para matricular con deuda o sin apto médico debe indicar el número de resolución.';
            LEAVE proc_crear_mat;
        END IF;

        IF v_deuda > 0 THEN SET v_bypass_deuda  = 1; END IF;
        IF v_apto = 0  THEN SET v_bypass_medico = 1; END IF;
    END IF;

    SET v_cred_adic  = fn_creditos_adicionales(p_id_estudiante, p_id_periodo);
    SET v_constancia = CONCAT('MAT-', v_codigo_periodo, '-', LPAD(p_id_estudiante, 5, '0'));

    INSERT INTO matriculas (
        id_estudiante, id_periodo, tipo_matricula, estado,
        total_creditos, creditos_adicionales, id_admin,
        bypass_cruces, bypass_deuda, bypass_medico, resolucion_excep,
        fecha_matricula, nro_constancia
    ) VALUES (
        p_id_estudiante, p_id_periodo, 'REGULAR', 'PENDIENTE',
        0, v_cred_adic, p_id_admin,
        0, v_bypass_deuda, v_bypass_medico,
        IF(v_bypass_deuda = 1 OR v_bypass_medico = 1, v_rd, NULL),
        NOW(), v_constancia
    );

    SET p_id_matricula   = LAST_INSERT_ID();
    SET p_nro_constancia = v_constancia;

    IF v_bypass_deuda = 1 THEN
        INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
        VALUES (p_id_matricula, 'DEUDA_PENDIENTE', v_rd, 'Matrícula autorizada con deuda pendiente', p_id_admin);
    END IF;

    IF v_bypass_medico = 1 THEN
        INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
        VALUES (p_id_matricula, 'REQUISITO_MEDICO', v_rd, 'Matrícula autorizada sin apto médico', p_id_admin);
    END IF;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Matrícula aperturada en estado PENDIENTE exitosamente.';
END proc_crear_mat$$

-- -----------------------------------------------------------------------------
-- sp_confirmar_matricula (auxiliar de TX-01): PENDIENTE -> CONFIRMADA
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_confirmar_matricula$$
CREATE PROCEDURE sp_confirmar_matricula(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_admin     INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_conf_mat: BEGIN
    DECLARE v_estado_actual    VARCHAR(20);
    DECLARE v_cursos_inscritos INT DEFAULT 0;
    DECLARE v_id_usuario_est   INT UNSIGNED;
    DECLARE v_constancia       VARCHAR(25);
    DECLARE v_total_cred       TINYINT UNSIGNED;

    SELECT m.estado, m.nro_constancia, m.total_creditos, e.id_usuario
    INTO v_estado_actual, v_constancia, v_total_cred, v_id_usuario_est
    FROM matriculas m
    JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
    WHERE m.id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_estado_actual IS NULL THEN
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'La matrícula indicada no existe.';
        LEAVE proc_conf_mat;
    END IF;

    IF v_estado_actual <> 'PENDIENTE' THEN
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula no se encuentra en estado PENDIENTE (estado actual: ', v_estado_actual, ').');
        LEAVE proc_conf_mat;
    END IF;

    SELECT COUNT(*) INTO v_cursos_inscritos
    FROM detalle_matricula
    WHERE id_matricula = p_id_matricula AND estado = 'INSCRITO';

    IF v_cursos_inscritos = 0 THEN
        SET p_codigo_resp = 'SIN_CURSOS_INSCRITOS';
        SET p_mensaje     = 'No se puede confirmar una matrícula sin al menos una asignatura en estado INSCRITO.';
        LEAVE proc_conf_mat;
    END IF;

    UPDATE matriculas SET estado = 'CONFIRMADA' WHERE id_matricula = p_id_matricula;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (COALESCE(p_id_admin, v_id_usuario_est), 'MATRICULA', 'CONFIRMAR_MATRICULA', 'matriculas',
            p_id_matricula,
            CONCAT('Matrícula confirmada. Constancia: ', v_constancia, ' | Asignaturas: ', v_cursos_inscritos, ' | Créditos: ', v_total_cred));

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Matrícula confirmada exitosamente.';
END proc_conf_mat$$


-- =============================================================================
-- PARTE 5: COLA 1 — TURNOS DE MATRÍCULA (TX-04)
-- Atención: primero los priorizados (promedio anterior >= fn_promedio_merito()),
-- luego por orden de llegada. La fila del período actúa como mutex de la cola.
-- =============================================================================

DROP PROCEDURE IF EXISTS sp_ingresar_cola_matricula$$
CREATE PROCEDURE sp_ingresar_cola_matricula(
    IN  p_id_estudiante INT UNSIGNED,
    IN  p_id_periodo    SMALLINT UNSIGNED,
    OUT p_posicion      SMALLINT UNSIGNED,
    OUT p_codigo_resp   VARCHAR(25),
    OUT p_mensaje       VARCHAR(255)
)
proc_cola_in: BEGIN
    DECLARE v_lock        SMALLINT UNSIGNED;
    DECLARE v_periodo_ok  INT DEFAULT 0;
    DECLARE v_id_usuario  INT UNSIGNED;
    DECLARE v_estado_acad VARCHAR(20);
    DECLARE v_prom_ant    DECIMAL(4,2);
    DECLARE v_id_cola     INT UNSIGNED;
    DECLARE v_estado_cola VARCHAR(20);
    DECLARE v_pos_actual  SMALLINT UNSIGNED;
    DECLARE v_prioridad   TINYINT(1) DEFAULT 0;
    DECLARE v_nueva_pos   SMALLINT UNSIGNED;
    DECLARE v_errno       INT;
    DECLARE v_msg         TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_posicion    = NULL;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_posicion = NULL;
    START TRANSACTION;

    SELECT id_periodo INTO v_lock
    FROM periodos_academicos WHERE id_periodo = p_id_periodo FOR UPDATE;

    IF v_lock IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'PERIODO_NO_EXISTE';
        SET p_mensaje     = 'El período académico indicado no existe.';
        LEAVE proc_cola_in;
    END IF;

    SELECT COUNT(*) INTO v_periodo_ok
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo
      AND estado = 'ACTIVO'
      AND NOW() BETWEEN inicio_matricula AND fin_matricula;

    IF v_periodo_ok = 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'PERIODO_INACTIVO';
        SET p_mensaje     = 'El período no está activo o está fuera de la fecha de matrícula.';
        LEAVE proc_cola_in;
    END IF;

    SELECT id_usuario, estado_academico, promedio_semestre_anterior
    INTO   v_id_usuario, v_estado_acad, v_prom_ant
    FROM   estudiantes
    WHERE  id_estudiante = p_id_estudiante;

    IF v_estado_acad IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTUDIANTE_NO_EXISTE';
        SET p_mensaje     = 'El estudiante no existe en el sistema.';
        LEAVE proc_cola_in;
    END IF;

    IF v_estado_acad IN ('EGRESADO', 'RETIRADO') THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTUDIANTE_NO_HABIL';
        SET p_mensaje     = CONCAT('El estudiante tiene estado "', v_estado_acad, '" y no puede ingresar a la cola.');
        LEAVE proc_cola_in;
    END IF;

    IF EXISTS (SELECT 1 FROM matriculas WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo) THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_MATRICULADO';
        SET p_mensaje     = 'El estudiante ya tiene matrícula en este período.';
        LEAVE proc_cola_in;
    END IF;

    SELECT id_cola, estado_cola, posicion
    INTO   v_id_cola, v_estado_cola, v_pos_actual
    FROM   cola_matricula
    WHERE  id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo;

    IF v_estado_cola IN ('EN_ESPERA', 'EN_TURNO') THEN
        ROLLBACK;
        SET p_posicion    = v_pos_actual;
        SET p_codigo_resp = IF(v_estado_cola = 'EN_TURNO', 'EN_TURNO', 'YA_EN_COLA');
        SET p_mensaje     = CONCAT('El estudiante ya está en la cola (estado ', v_estado_cola, ', turno N° ', v_pos_actual, ').');
        LEAVE proc_cola_in;
    END IF;

    IF v_estado_cola = 'FINALIZADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'TURNO_FINALIZADO';
        SET p_mensaje     = 'El proceso de matrícula de este estudiante ya concluyó en este período.';
        LEAVE proc_cola_in;
    END IF;

    IF v_prom_ant IS NOT NULL AND v_prom_ant >= fn_promedio_merito() THEN
        SET v_prioridad = 1;
    END IF;

    SELECT COALESCE(MAX(posicion), 0) + 1 INTO v_nueva_pos
    FROM cola_matricula WHERE id_periodo = p_id_periodo;

    IF v_id_cola IS NULL THEN
        INSERT INTO cola_matricula (id_estudiante, id_periodo, posicion, estado_cola, priorizado)
        VALUES (p_id_estudiante, p_id_periodo, v_nueva_pos, 'EN_ESPERA', v_prioridad);
        SET v_id_cola = LAST_INSERT_ID();
    ELSE
        -- Reingreso tras abandono, con nueva posición
        UPDATE cola_matricula
        SET posicion = v_nueva_pos, estado_cola = 'EN_ESPERA', priorizado = v_prioridad,
            hora_ingreso = NOW(), hora_turno = NULL, hora_fin = NULL
        WHERE id_cola = v_id_cola;
    END IF;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (v_id_usuario, 'COLA', 'INGRESO_COLA', 'cola_matricula', v_id_cola,
            CONCAT('Posición ', v_nueva_pos, ' | Priorizado: ', v_prioridad));

    COMMIT;
    SET p_posicion    = v_nueva_pos;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Ingreso exitoso a la cola con turno N° ', v_nueva_pos,
                               IF(v_prioridad = 1, ' (con prioridad por rendimiento).', '.'));
END proc_cola_in$$

DROP PROCEDURE IF EXISTS sp_atender_siguiente_cola$$
CREATE PROCEDURE sp_atender_siguiente_cola(
    IN  p_id_periodo     SMALLINT UNSIGNED,
    IN  p_max_en_turno   TINYINT UNSIGNED,
    OUT p_id_cola        INT UNSIGNED,
    OUT p_id_estudiante  INT UNSIGNED,
    OUT p_codigo_resp    VARCHAR(25),
    OUT p_mensaje        VARCHAR(255)
)
proc_atender: BEGIN
    DECLARE v_lock          SMALLINT UNSIGNED;
    DECLARE v_max           TINYINT UNSIGNED;
    DECLARE v_en_turno      INT DEFAULT 0;
    DECLARE v_id_cola       INT UNSIGNED;
    DECLARE v_id_estudiante INT UNSIGNED;
    DECLARE v_posicion      SMALLINT UNSIGNED;
    DECLARE v_errno         INT;
    DECLARE v_msg           TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_id_cola       = NULL;
        SET p_id_estudiante = NULL;
        SET p_codigo_resp   = 'ERROR_SQL';
        SET p_mensaje       = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_cola       = NULL;
    SET p_id_estudiante = NULL;
    SET v_max = COALESCE(NULLIF(p_max_en_turno, 0), 1);

    START TRANSACTION;

    SELECT id_periodo INTO v_lock
    FROM periodos_academicos WHERE id_periodo = p_id_periodo FOR UPDATE;

    IF v_lock IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'PERIODO_NO_EXISTE';
        SET p_mensaje     = 'El período académico indicado no existe.';
        LEAVE proc_atender;
    END IF;

    SELECT COUNT(*) INTO v_en_turno
    FROM cola_matricula WHERE id_periodo = p_id_periodo AND estado_cola = 'EN_TURNO';

    IF v_en_turno >= v_max THEN
        ROLLBACK;
        SET p_codigo_resp = 'TURNOS_OCUPADOS';
        SET p_mensaje     = CONCAT('Ya hay ', v_en_turno, ' estudiante(s) en turno. Máximo permitido: ', v_max, '.');
        LEAVE proc_atender;
    END IF;

    SELECT id_cola, id_estudiante, posicion
    INTO   v_id_cola, v_id_estudiante, v_posicion
    FROM   cola_matricula
    WHERE  id_periodo = p_id_periodo AND estado_cola = 'EN_ESPERA'
    ORDER BY priorizado DESC, posicion ASC
    LIMIT 1
    FOR UPDATE;

    IF v_id_cola IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'COLA_VACIA';
        SET p_mensaje     = 'No hay estudiantes en espera en la cola de matrícula.';
        LEAVE proc_atender;
    END IF;

    UPDATE cola_matricula
    SET estado_cola = 'EN_TURNO', hora_turno = NOW()
    WHERE id_cola = v_id_cola;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (NULL, 'COLA', 'LLAMAR_TURNO', 'cola_matricula', v_id_cola,
            CONCAT('Estudiante ', v_id_estudiante, ' pasa a EN_TURNO (posición ', v_posicion, ')'));

    COMMIT;
    SET p_id_cola       = v_id_cola;
    SET p_id_estudiante = v_id_estudiante;
    SET p_codigo_resp   = 'EXITO';
    SET p_mensaje       = CONCAT('Turno asignado al estudiante id: ', v_id_estudiante);
END proc_atender$$

DROP PROCEDURE IF EXISTS sp_finalizar_turno_cola$$
CREATE PROCEDURE sp_finalizar_turno_cola(
    IN  p_id_cola     INT UNSIGNED,
    OUT p_codigo_resp VARCHAR(25),
    OUT p_mensaje     VARCHAR(255)
)
proc_fin_cola: BEGIN
    DECLARE v_estado VARCHAR(20);

    UPDATE cola_matricula 
    SET estado_cola = 'FINALIZADO', hora_fin = NOW()
    WHERE id_cola     = p_id_cola
      AND estado_cola = 'EN_TURNO';

    IF ROW_COUNT() = 0 THEN
        SELECT estado_cola INTO v_estado FROM cola_matricula WHERE id_cola = p_id_cola;
        IF v_estado IS NULL THEN
            SET p_codigo_resp = 'TURNO_NO_EXISTE';
            SET p_mensaje     = 'El registro en cola especificado no existe.';
        ELSE
            SET p_codigo_resp = 'ESTADO_INVALIDO';
            SET p_mensaje     = CONCAT('El registro no se encontraba EN_TURNO (estado actual: ', v_estado, ').');
        END IF;
        LEAVE proc_fin_cola;
    END IF;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Turno en cola finalizado exitosamente.';
END proc_fin_cola$$

DROP PROCEDURE IF EXISTS sp_abandonar_cola$$
CREATE PROCEDURE sp_abandonar_cola(
    IN  p_id_estudiante INT UNSIGNED,
    IN  p_id_periodo    SMALLINT UNSIGNED,
    OUT p_codigo_resp   VARCHAR(25),
    OUT p_mensaje       VARCHAR(255)
)
proc_abandono: BEGIN
    DECLARE v_estado VARCHAR(20);

    UPDATE cola_matricula 
    SET estado_cola = 'ABANDONADO', hora_fin = NOW()
    WHERE id_estudiante = p_id_estudiante 
      AND id_periodo    = p_id_periodo
      AND estado_cola   IN ('EN_ESPERA', 'EN_TURNO');

    IF ROW_COUNT() = 0 THEN
        SELECT estado_cola INTO v_estado 
        FROM cola_matricula
        WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo;

        IF v_estado IS NULL THEN
            SET p_codigo_resp = 'NO_EN_COLA';
            SET p_mensaje     = 'El estudiante no tiene registro en la cola para este período.';
        ELSE
            SET p_codigo_resp = 'ESTADO_NO_ABANDONABLE';
            SET p_mensaje     = CONCAT('No se puede abandonar la cola en el estado actual: ', v_estado);
        END IF;
        LEAVE proc_abandono;
    END IF;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'El estudiante ha abandonado la cola de matrícula.';
END proc_abandono$$

-- Libera turnos que excedieron el tiempo permitido (ej. 15 minutos)
DROP PROCEDURE IF EXISTS sp_expirar_turnos_cola$$
CREATE PROCEDURE sp_expirar_turnos_cola(
    IN  p_id_periodo SMALLINT UNSIGNED,
    IN  p_minutos    SMALLINT UNSIGNED,
    OUT p_total      INT
)
BEGIN
    UPDATE cola_matricula
    SET estado_cola = 'ABANDONADO', hora_fin = NOW()
    WHERE id_periodo  = p_id_periodo
      AND estado_cola = 'EN_TURNO'
      AND hora_turno IS NOT NULL
      AND hora_turno < NOW() - INTERVAL p_minutos MINUTE;

    SET p_total = ROW_COUNT();
END$$

DROP PROCEDURE IF EXISTS sp_consultar_posicion_cola$$
CREATE PROCEDURE sp_consultar_posicion_cola(
    IN p_id_estudiante INT UNSIGNED,
    IN p_id_periodo    SMALLINT UNSIGNED
)
BEGIN
    SELECT 
        cm.id_cola, cm.id_estudiante, cm.id_periodo, cm.posicion, cm.estado_cola,
        cm.priorizado, cm.hora_ingreso, cm.hora_turno,
        CASE cm.estado_cola
            WHEN 'EN_ESPERA' THEN (
                SELECT COUNT(*) 
                FROM cola_matricula c2 
                WHERE c2.id_periodo = p_id_periodo 
                  AND c2.estado_cola = 'EN_ESPERA'
                  AND (
                      c2.priorizado > cm.priorizado 
                      OR (c2.priorizado = cm.priorizado AND c2.posicion < cm.posicion)
                  )
            )
            ELSE NULL
        END AS estudiantes_adelante
    FROM cola_matricula cm 
    WHERE cm.id_estudiante = p_id_estudiante 
      AND cm.id_periodo = p_id_periodo;
END$$


-- =============================================================================
-- PARTE 6: MATRÍCULA (TX-01) Y OPERACIONES SOBRE CURSOS
-- =============================================================================

-- -----------------------------------------------------------------------------
-- sp_registrar_matricula_completa  (TX-01) — PUNTO DE ENTRADA ÚNICO
-- Sin p_id_admin el estudiante debe estar EN_TURNO en la cola; con administrador
-- puede matricularse sin pasar por la cola. Todo o nada: matrícula, cursos,
-- confirmación, cierre del turno y auditoría. Secciones procesadas en orden
-- ascendente (previene deadlocks entre matrículas simultáneas).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_registrar_matricula_completa$$
CREATE PROCEDURE sp_registrar_matricula_completa(
    IN  p_id_estudiante   INT UNSIGNED,
    IN  p_id_periodo      SMALLINT UNSIGNED,
    IN  p_id_admin        INT UNSIGNED,
    IN  p_numero_rd       VARCHAR(80),
    IN  p_lista_secciones VARCHAR(255),
    OUT p_id_matricula    INT UNSIGNED,
    OUT p_codigo_resp     VARCHAR(25),
    OUT p_mensaje         VARCHAR(255)
)
proc_matricula: BEGIN
    DECLARE v_seccion_str  VARCHAR(20);
    DECLARE v_seccion_id   INT UNSIGNED;
    DECLARE v_pos_coma     INT;
    DECLARE v_lista_rem    VARCHAR(255);
    DECLARE v_constancia   VARCHAR(25);
    DECLARE v_resp_aux     VARCHAR(25);
    DECLARE v_msg_aux      VARCHAR(255);
    DECLARE v_estado_cola  VARCHAR(20);
    DECLARE v_errno        INT;
    DECLARE v_msg          TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_id_matricula = NULL;
        SET p_codigo_resp  = 'ERROR_SQL';
        SET p_mensaje      = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_matricula = NULL;

    IF p_lista_secciones IS NULL OR CHAR_LENGTH(TRIM(p_lista_secciones)) = 0 THEN
        SET p_codigo_resp = 'LISTA_VACIA';
        SET p_mensaje     = 'Debe indicar al menos una sección para matricularse.';
        LEAVE proc_matricula;
    END IF;

    -- Tabla temporal (DDL de tablas TEMPORARY no confirma transacciones)
    CREATE TEMPORARY TABLE IF NOT EXISTS tmp_secciones_matricula (
        id_sec INT UNSIGNED PRIMARY KEY
    ) ENGINE = MEMORY;

    DELETE FROM tmp_secciones_matricula;

    SET v_lista_rem = TRIM(p_lista_secciones);

    WHILE CHAR_LENGTH(v_lista_rem) > 0 DO
        SET v_pos_coma = INSTR(v_lista_rem, ',');
        IF v_pos_coma > 0 THEN
            SET v_seccion_str = TRIM(SUBSTRING(v_lista_rem, 1, v_pos_coma - 1));
            SET v_lista_rem   = TRIM(SUBSTRING(v_lista_rem, v_pos_coma + 1));
        ELSE
            SET v_seccion_str = TRIM(v_lista_rem);
            SET v_lista_rem   = '';
        END IF;

        IF CHAR_LENGTH(v_seccion_str) > 0 THEN
            INSERT IGNORE INTO tmp_secciones_matricula (id_sec) VALUES (CAST(v_seccion_str AS UNSIGNED));
        END IF;
    END WHILE;

    IF NOT EXISTS (SELECT 1 FROM tmp_secciones_matricula) THEN
        SET p_codigo_resp = 'LISTA_VACIA';
        SET p_mensaje     = 'Debe indicar al menos una sección válida para matricularse.';
        LEAVE proc_matricula;
    END IF;

    START TRANSACTION;

    -- 0. Control de turno (solo cuando NO actúa un administrador)
    SELECT estado_cola INTO v_estado_cola
    FROM cola_matricula
    WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo
    FOR UPDATE;

    IF p_id_admin IS NULL THEN
        IF v_estado_cola IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'NO_EN_COLA';
            SET p_mensaje     = 'El estudiante no ingresó a la cola de matrícula.';
            LEAVE proc_matricula;
        ELSEIF v_estado_cola <> 'EN_TURNO' THEN
            ROLLBACK;
            SET p_codigo_resp = 'SIN_TURNO';
            SET p_mensaje     = CONCAT('Aún no es su turno. Estado en cola: ', v_estado_cola, '.');
            LEAVE proc_matricula;
        END IF;
    END IF;

    -- 1. Aperturar matrícula PENDIENTE
    CALL sp_crear_matricula_pendiente(
        p_id_estudiante, p_id_periodo, p_id_admin, p_numero_rd,
        p_id_matricula, v_constancia, v_resp_aux, v_msg_aux
    );

    IF v_resp_aux <> 'EXITO' THEN
        ROLLBACK;
        SET p_id_matricula = NULL;
        SET p_codigo_resp  = v_resp_aux;
        SET p_mensaje      = v_msg_aux;
        LEAVE proc_matricula;
    END IF;

    -- 2. Inscribir cada sección en orden ascendente
    WHILE EXISTS (SELECT 1 FROM tmp_secciones_matricula) DO
        SELECT MIN(id_sec) INTO v_seccion_id FROM tmp_secciones_matricula;

        CALL sp_i_inscribir_curso(p_id_matricula, v_seccion_id, 'MATRICULA', v_resp_aux, v_msg_aux);

        IF v_resp_aux <> 'EXITO' THEN
            ROLLBACK;
            SET p_id_matricula = NULL;
            SET p_codigo_resp  = v_resp_aux;
            SET p_mensaje      = CONCAT('Error en sección ', v_seccion_id, ': ', v_msg_aux);
            LEAVE proc_matricula;
        END IF;

        DELETE FROM tmp_secciones_matricula WHERE id_sec = v_seccion_id;
    END WHILE;

    -- 3. Confirmar (PENDIENTE -> CONFIRMADA)
    CALL sp_confirmar_matricula(p_id_matricula, p_id_admin, v_resp_aux, v_msg_aux);

    IF v_resp_aux <> 'EXITO' THEN
        ROLLBACK;
        SET p_id_matricula = NULL;
        SET p_codigo_resp  = v_resp_aux;
        SET p_mensaje      = CONCAT('Error al confirmar matrícula: ', v_msg_aux);
        LEAVE proc_matricula;
    END IF;

    -- 4. Cerrar el turno en la cola (si existe)
    UPDATE cola_matricula
    SET estado_cola = 'FINALIZADO', hora_fin = NOW()
    WHERE id_estudiante = p_id_estudiante
      AND id_periodo    = p_id_periodo
      AND estado_cola IN ('EN_TURNO', 'EN_ESPERA');

    -- 5. Auditoría
    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (
        COALESCE(p_id_admin, (SELECT id_usuario FROM estudiantes WHERE id_estudiante = p_id_estudiante)),
        'MATRICULA', 'REGISTRO_COMPLETO', 'matriculas', p_id_matricula,
        CONCAT('Matrícula integral procesada. Constancia: ', v_constancia, ' | Secciones: ', p_lista_secciones)
    );

    COMMIT;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Matrícula completa procesada exitosamente. Constancia: ', v_constancia);
END proc_matricula$$

-- -----------------------------------------------------------------------------
-- sp_inscribir_curso_matricula (público, transaccional)
-- Agrega un curso a una matrícula ya existente (dentro de la ventana de matrícula).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_inscribir_curso_matricula$$
CREATE PROCEDURE sp_inscribir_curso_matricula(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    DECLARE v_errno INT;
    DECLARE v_msg   TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    START TRANSACTION;
    CALL sp_i_inscribir_curso(p_id_matricula, p_id_seccion, 'MATRICULA', p_codigo_resp, p_mensaje);
    IF p_codigo_resp = 'EXITO' THEN
        COMMIT;
    ELSE
        ROLLBACK;
    END IF;
END$$

-- -----------------------------------------------------------------------------
-- sp_retirar_curso (público, transaccional)
-- Solo entre el inicio de matrícula y el fin de rectificación del período.
-- Libera la vacante y promueve la lista de espera en la misma transacción.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_retirar_curso$$
CREATE PROCEDURE sp_retirar_curso(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_retiro: BEGIN
    DECLARE v_ini    DATETIME;
    DECLARE v_fin    DATETIME;
    DECLARE v_estado VARCHAR(20);
    DECLARE v_errno  INT;
    DECLARE v_msg    TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SELECT p.inicio_matricula, p.fin_rectificacion, p.estado
    INTO   v_ini, v_fin, v_estado
    FROM   matriculas m
    JOIN   periodos_academicos p ON p.id_periodo = m.id_periodo
    WHERE  m.id_matricula = p_id_matricula;

    IF v_ini IS NULL THEN
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'La matrícula indicada no existe.';
        LEAVE proc_retiro;
    END IF;

    IF v_estado <> 'ACTIVO' OR NOW() < v_ini OR NOW() > v_fin THEN
        SET p_codigo_resp = 'FUERA_DE_PERIODO';
        SET p_mensaje     = 'El retiro de cursos no está habilitado en este momento.';
        LEAVE proc_retiro;
    END IF;

    START TRANSACTION;
    CALL sp_i_retirar_curso(p_id_matricula, p_id_seccion, p_codigo_resp, p_mensaje);
    IF p_codigo_resp = 'EXITO' THEN
        COMMIT;
    ELSE
        ROLLBACK;
    END IF;
END proc_retiro$$

-- -----------------------------------------------------------------------------
-- sp_anular_matricula (TX-06, administrador)
-- Retira todos los cursos (liberando vacantes y promoviendo las esperas),
-- cancela sus propias esperas y marca la matrícula como ANULADA.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_anular_matricula$$
CREATE PROCEDURE sp_anular_matricula(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_admin     INT UNSIGNED,
    IN  p_motivo       VARCHAR(200),
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_anular: BEGIN
    DECLARE v_estado     VARCHAR(20);
    DECLARE v_id_seccion INT UNSIGNED;
    DECLARE v_hay        TINYINT DEFAULT 1;
    DECLARE v_resp       VARCHAR(25);
    DECLARE v_msg_aux    VARCHAR(255);
    DECLARE v_errno      INT;
    DECLARE v_msg        TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_id_admin IS NULL THEN
        SET p_codigo_resp = 'ADMIN_REQUERIDO';
        SET p_mensaje     = 'Solo un administrador puede anular una matrícula.';
        LEAVE proc_anular;
    END IF;

    START TRANSACTION;

    SELECT estado INTO v_estado
    FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_estado IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'No se encontró la matrícula indicada.';
        LEAVE proc_anular;
    END IF;

    IF v_estado = 'ANULADA' THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_ANULADA';
        SET p_mensaje     = 'La matrícula ya se encuentra anulada.';
        LEAVE proc_anular;
    END IF;

    -- Primero se cancelan sus propias esperas para que no se auto-promueva
    UPDATE lista_espera_seccion
    SET estado = 'CANCELADO', fecha_atencion = NOW()
    WHERE id_matricula = p_id_matricula AND estado = 'EN_ESPERA';

    WHILE v_hay = 1 DO
        SET v_id_seccion = NULL;

        SELECT id_seccion INTO v_id_seccion
        FROM detalle_matricula
        WHERE id_matricula = p_id_matricula AND estado = 'INSCRITO'
        LIMIT 1;

        IF v_id_seccion IS NULL THEN
            SET v_hay = 0;
        ELSE
            CALL sp_i_retirar_curso(p_id_matricula, v_id_seccion, v_resp, v_msg_aux);
            IF v_resp <> 'EXITO' THEN
                ROLLBACK;
                SET p_codigo_resp = v_resp;
                SET p_mensaje     = v_msg_aux;
                LEAVE proc_anular;
            END IF;
        END IF;
    END WHILE;

    UPDATE matriculas SET estado = 'ANULADA', id_admin = p_id_admin
    WHERE id_matricula = p_id_matricula;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'MATRICULA', 'ANULAR_MATRICULA', 'matriculas', p_id_matricula,
            CONCAT('Motivo: ', COALESCE(p_motivo, 'No indicado')));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Matrícula anulada; vacantes liberadas y listas de espera procesadas.';
END proc_anular$$

-- -----------------------------------------------------------------------------
-- sp_aplicar_excepcion (TX-06, administrador)
-- Registra una excepción con número de resolución y aplica su efecto.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_aplicar_excepcion$$
CREATE PROCEDURE sp_aplicar_excepcion(
    IN  p_id_matricula   INT UNSIGNED,
    IN  p_id_admin       INT UNSIGNED,
    IN  p_tipo_excepcion VARCHAR(20),
    IN  p_numero_rd      VARCHAR(80),
    IN  p_descripcion    TEXT,
    IN  p_creditos_extra TINYINT UNSIGNED,
    OUT p_codigo_resp    VARCHAR(25),
    OUT p_mensaje        VARCHAR(255)
)
proc_exc: BEGIN
    DECLARE v_estado VARCHAR(20);
    DECLARE v_errno  INT;
    DECLARE v_msg    TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_id_admin IS NULL OR p_numero_rd IS NULL OR CHAR_LENGTH(TRIM(p_numero_rd)) = 0 THEN
        SET p_codigo_resp = 'REQUIERE_RESOLUCION';
        SET p_mensaje     = 'Debe indicar el administrador y el número de resolución.';
        LEAVE proc_exc;
    END IF;

    IF p_tipo_excepcion NOT IN ('CREDITOS_EXTRAS','CRUCE_HORARIO','CURSO_DIRIGIDO','REQUISITO_MEDICO','DEUDA_PENDIENTE') THEN
        SET p_codigo_resp = 'TIPO_INVALIDO';
        SET p_mensaje     = 'Tipo de excepción no válido.';
        LEAVE proc_exc;
    END IF;

    IF p_tipo_excepcion = 'CREDITOS_EXTRAS' AND (p_creditos_extra IS NULL OR p_creditos_extra NOT BETWEEN 1 AND 8) THEN
        SET p_codigo_resp = 'CREDITOS_INVALIDOS';
        SET p_mensaje     = 'Para CREDITOS_EXTRAS indique entre 1 y 8 créditos.';
        LEAVE proc_exc;
    END IF;

    START TRANSACTION;

    SELECT estado INTO v_estado FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_estado IS NULL OR v_estado = 'ANULADA' THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_VALIDA';
        SET p_mensaje     = 'La matrícula no existe o está anulada.';
        LEAVE proc_exc;
    END IF;

    INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
    VALUES (p_id_matricula, p_tipo_excepcion, p_numero_rd, COALESCE(p_descripcion, 'Sin descripción'), p_id_admin);

    IF p_tipo_excepcion = 'CREDITOS_EXTRAS' THEN
        UPDATE matriculas
        SET creditos_adicionales = creditos_adicionales + p_creditos_extra,
            resolucion_excep = p_numero_rd, id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'CRUCE_HORARIO' THEN
        UPDATE matriculas SET bypass_cruces = 1, resolucion_excep = p_numero_rd, id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'REQUISITO_MEDICO' THEN
        UPDATE matriculas SET bypass_medico = 1, resolucion_excep = p_numero_rd, id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    ELSEIF p_tipo_excepcion = 'DEUDA_PENDIENTE' THEN
        UPDATE matriculas SET bypass_deuda = 1, resolucion_excep = p_numero_rd, id_admin = p_id_admin
        WHERE id_matricula = p_id_matricula;
    END IF;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'MATRICULA', 'APLICAR_EXCEPCION', 'matriculas', p_id_matricula,
            CONCAT(p_tipo_excepcion, ' | RD: ', p_numero_rd));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Excepción registrada y aplicada a la matrícula.';
END proc_exc$$


-- =============================================================================
-- PARTE 7: COLA 2 — LISTA DE ESPERA POR SECCIÓN (TX-06)
-- La promoción automática ocurre en sp_i_retirar_curso (retiro, anulación o
-- rectificación aprobada). sp_espera_promover permite reprocesarla manualmente.
-- =============================================================================

DROP PROCEDURE IF EXISTS sp_espera_inscribir$$
CREATE PROCEDURE sp_espera_inscribir(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_posicion     SMALLINT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_esp: BEGIN
    DECLARE v_id_curso   SMALLINT UNSIGNED;
    DECLARE v_per_sec    SMALLINT UNSIGNED;
    DECLARE v_id_est     INT UNSIGNED;
    DECLARE v_per_mat    SMALLINT UNSIGNED;
    DECLARE v_estado_mat VARCHAR(20);
    DECLARE v_ini        DATETIME;
    DECLARE v_fin        DATETIME;
    DECLARE v_estado_per VARCHAR(20);
    DECLARE v_id_espera  INT UNSIGNED;
    DECLARE v_estado_esp VARCHAR(20);
    DECLARE v_pos_act    SMALLINT UNSIGNED;
    DECLARE v_nueva      SMALLINT UNSIGNED;
    DECLARE v_errno      INT;
    DECLARE v_msg        TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_posicion    = NULL;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_posicion = NULL;
    START TRANSACTION;

    -- La fila de la sección actúa como mutex de su lista de espera
    SELECT id_curso, id_periodo INTO v_id_curso, v_per_sec
    FROM secciones WHERE id_seccion = p_id_seccion FOR UPDATE;

    IF v_id_curso IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe.';
        LEAVE proc_esp;
    END IF;

    SELECT id_estudiante, id_periodo, estado INTO v_id_est, v_per_mat, v_estado_mat
    FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_id_est IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'No se encontró la matrícula indicada.';
        LEAVE proc_esp;
    END IF;

    IF v_estado_mat NOT IN ('PENDIENTE', 'CONFIRMADA') THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula está en estado "', v_estado_mat, '" y no admite lista de espera.');
        LEAVE proc_esp;
    END IF;

    IF v_per_mat <> v_per_sec THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_OTRO_PERIODO';
        SET p_mensaje     = 'La sección pertenece a otro período académico.';
        LEAVE proc_esp;
    END IF;

    SELECT inicio_matricula, fin_rectificacion, estado INTO v_ini, v_fin, v_estado_per
    FROM periodos_academicos WHERE id_periodo = v_per_mat;

    IF v_estado_per <> 'ACTIVO' OR NOW() < v_ini OR NOW() > v_fin THEN
        ROLLBACK;
        SET p_codigo_resp = 'FUERA_DE_PERIODO';
        SET p_mensaje     = 'La lista de espera no está habilitada en este momento.';
        LEAVE proc_esp;
    END IF;

    IF COALESCE(fn_vacantes_disponibles(p_id_seccion), 0) > 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'HAY_VACANTES';
        SET p_mensaje     = 'La sección aún tiene vacantes; inscríbase directamente.';
        LEAVE proc_esp;
    END IF;

    IF EXISTS (
        SELECT 1 FROM detalle_matricula dm
        JOIN secciones s ON s.id_seccion = dm.id_seccion
        WHERE dm.id_matricula = p_id_matricula AND s.id_curso = v_id_curso AND dm.estado = 'INSCRITO'
    ) THEN
        ROLLBACK;
        SET p_codigo_resp = 'CURSO_DUPLICADO';
        SET p_mensaje     = 'El estudiante ya tiene este curso inscrito en otra sección.';
        LEAVE proc_esp;
    END IF;

    IF fn_validar_curso_en_plan(v_id_est, v_id_curso) = 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'CURSO_NO_EN_PLAN';
        SET p_mensaje     = 'La asignatura no pertenece al plan de estudios del estudiante.';
        LEAVE proc_esp;
    END IF;

    IF fn_validar_prerrequisitos(v_id_est, v_id_curso) = 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'FALTAN_PRERREQUISITOS';
        SET p_mensaje     = 'El estudiante no cumple los prerrequisitos del curso.';
        LEAVE proc_esp;
    END IF;

    SELECT id_espera, estado, posicion INTO v_id_espera, v_estado_esp, v_pos_act
    FROM lista_espera_seccion
    WHERE id_seccion = p_id_seccion AND id_matricula = p_id_matricula;

    IF v_estado_esp = 'EN_ESPERA' THEN
        ROLLBACK;
        SET p_posicion    = v_pos_act;
        SET p_codigo_resp = 'YA_EN_ESPERA';
        SET p_mensaje     = 'Ya se encuentra en la lista de espera de esta sección.';
        LEAVE proc_esp;
    END IF;

    SELECT COALESCE(MAX(posicion), 0) + 1 INTO v_nueva
    FROM lista_espera_seccion WHERE id_seccion = p_id_seccion;

    IF v_id_espera IS NULL THEN
        INSERT INTO lista_espera_seccion (id_seccion, id_matricula, posicion)
        VALUES (p_id_seccion, p_id_matricula, v_nueva);
    ELSE
        UPDATE lista_espera_seccion
        SET posicion = v_nueva, estado = 'EN_ESPERA', fecha_registro = NOW(), fecha_atencion = NULL
        WHERE id_espera = v_id_espera;
    END IF;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES ((SELECT id_usuario FROM estudiantes WHERE id_estudiante = v_id_est),
            'COLA', 'INGRESO_ESPERA', 'lista_espera_seccion', p_id_seccion,
            CONCAT('Matrícula ', p_id_matricula, ' en espera de la sección ', p_id_seccion, ' (posición ', v_nueva, ')'));

    COMMIT;
    SET p_posicion    = v_nueva;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Registrado en la lista de espera, posición ', v_nueva, '.');
END proc_esp$$

DROP PROCEDURE IF EXISTS sp_espera_cancelar$$
CREATE PROCEDURE sp_espera_cancelar(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    UPDATE lista_espera_seccion
    SET estado = 'CANCELADO', fecha_atencion = NOW()
    WHERE id_seccion = p_id_seccion AND id_matricula = p_id_matricula AND estado = 'EN_ESPERA';

    IF ROW_COUNT() = 0 THEN
        SET p_codigo_resp = 'NO_EN_ESPERA';
        SET p_mensaje     = 'No tiene una espera activa en esa sección.';
    ELSE
        SET p_codigo_resp = 'EXITO';
        SET p_mensaje     = 'Espera cancelada correctamente.';
    END IF;
END$$

DROP PROCEDURE IF EXISTS sp_espera_consultar$$
CREATE PROCEDURE sp_espera_consultar(IN p_id_matricula INT UNSIGNED)
BEGIN
    SELECT
        le.id_espera, le.id_seccion, c.codigo_curso, c.nombre AS curso, s.codigo_seccion,
        le.estado, le.posicion,
        (SELECT COUNT(*) FROM lista_espera_seccion x
          WHERE x.id_seccion = le.id_seccion AND x.estado = 'EN_ESPERA'
            AND x.posicion < le.posicion) AS personas_delante,
        le.fecha_registro, le.fecha_atencion
    FROM lista_espera_seccion le
    JOIN secciones s ON s.id_seccion = le.id_seccion
    JOIN cursos c    ON c.id_curso = s.id_curso
    WHERE le.id_matricula = p_id_matricula
    ORDER BY le.fecha_registro DESC;
END$$

-- Administrador: reprocesar la lista (p. ej. tras aumentar vacantes_extra)
DROP PROCEDURE IF EXISTS sp_espera_promover$$
CREATE PROCEDURE sp_espera_promover(
    IN  p_id_seccion  INT UNSIGNED,
    OUT p_procesados  INT,
    OUT p_codigo_resp VARCHAR(25),
    OUT p_mensaje     VARCHAR(255)
)
proc_prom: BEGIN
    DECLARE v_antes   INT DEFAULT 0;
    DECLARE v_despues INT DEFAULT 0;
    DECLARE v_dummy   INT UNSIGNED;
    DECLARE v_errno   INT;
    DECLARE v_msg     TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_procesados  = 0;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_procesados = 0;
    START TRANSACTION;

    SELECT id_seccion INTO v_dummy FROM secciones WHERE id_seccion = p_id_seccion FOR UPDATE;

    IF v_dummy IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe.';
        LEAVE proc_prom;
    END IF;

    SELECT COUNT(*) INTO v_antes FROM lista_espera_seccion
    WHERE id_seccion = p_id_seccion AND estado = 'EN_ESPERA';

    CALL sp_i_espera_promover(p_id_seccion);

    SELECT COUNT(*) INTO v_despues FROM lista_espera_seccion
    WHERE id_seccion = p_id_seccion AND estado = 'EN_ESPERA';

    COMMIT;
    SET p_procesados  = v_antes - v_despues;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Solicitudes procesadas de la lista de espera: ', p_procesados);
END proc_prom$$


-- =============================================================================
-- PARTE 8: COLA 3 — SOLICITUDES DE RECTIFICACIÓN (TX-02)
-- Flujo: el estudiante SOLICITA (queda en cola FIFO: v_rectificaciones_pendientes)
--        -> el administrador APRUEBA (ejecuta el movimiento) o RECHAZA.
-- Reemplaza al antiguo sp_rectificar_matricula (que aprobaba todo al instante).
-- La ventana de rectificación se valida al SOLICITAR.
-- =============================================================================

DROP PROCEDURE IF EXISTS sp_rectificar_matricula$$   -- obsoleto en v4

DROP PROCEDURE IF EXISTS sp_solicitar_rectificacion$$
CREATE PROCEDURE sp_solicitar_rectificacion(
    IN  p_id_matricula       INT UNSIGNED,
    IN  p_tipo_movimiento    VARCHAR(20),
    IN  p_id_seccion_origen  INT UNSIGNED,
    IN  p_id_seccion_destino INT UNSIGNED,
    OUT p_id_rectificacion   INT UNSIGNED,
    OUT p_codigo_resp        VARCHAR(25),
    OUT p_mensaje            VARCHAR(255)
)
proc_sol: BEGIN
    DECLARE v_id_periodo  SMALLINT UNSIGNED;
    DECLARE v_estado_mat  VARCHAR(20);
    DECLARE v_estado_per  VARCHAR(20);
    DECLARE v_ini         DATETIME;
    DECLARE v_fin         DATETIME;
    DECLARE v_curso_orig  SMALLINT UNSIGNED;
    DECLARE v_curso_dest  SMALLINT UNSIGNED;
    DECLARE v_errno       INT;
    DECLARE v_msg         TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_id_rectificacion = NULL;
        SET p_codigo_resp      = 'ERROR_SQL';
        SET p_mensaje          = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_rectificacion = NULL;

    IF p_tipo_movimiento NOT IN ('RETIRO', 'AGREGAR', 'CAMBIO_SECCION') THEN
        SET p_codigo_resp = 'MOVIMIENTO_INVALIDO';
        SET p_mensaje     = 'Tipo de movimiento no válido. Debe ser RETIRO, AGREGAR o CAMBIO_SECCION.';
        LEAVE proc_sol;
    END IF;

    IF (p_tipo_movimiento IN ('RETIRO', 'CAMBIO_SECCION') AND p_id_seccion_origen IS NULL)
       OR (p_tipo_movimiento IN ('AGREGAR', 'CAMBIO_SECCION') AND p_id_seccion_destino IS NULL) THEN
        SET p_codigo_resp = 'PARAMETRO_FALTANTE';
        SET p_mensaje     = 'Falta la sección de origen o de destino para el movimiento indicado.';
        LEAVE proc_sol;
    END IF;

    IF p_tipo_movimiento = 'CAMBIO_SECCION' AND p_id_seccion_origen = p_id_seccion_destino THEN
        SET p_codigo_resp = 'SECCIONES_IGUALES';
        SET p_mensaje     = 'La sección de origen y destino no pueden ser la misma.';
        LEAVE proc_sol;
    END IF;

    START TRANSACTION;

    SELECT id_periodo, estado INTO v_id_periodo, v_estado_mat
    FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_id_periodo IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_INEXISTENTE';
        SET p_mensaje     = 'No se encontró la matrícula para solicitar la rectificación.';
        LEAVE proc_sol;
    END IF;

    IF v_estado_mat NOT IN ('PENDIENTE', 'CONFIRMADA') THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula está en estado "', v_estado_mat, '" y no admite rectificaciones.');
        LEAVE proc_sol;
    END IF;

    SELECT inicio_rectificacion, fin_rectificacion, estado INTO v_ini, v_fin, v_estado_per
    FROM periodos_academicos WHERE id_periodo = v_id_periodo;

    IF v_estado_per <> 'ACTIVO' OR NOW() < v_ini OR NOW() > v_fin THEN
        ROLLBACK;
        SET p_codigo_resp = 'FUERA_DE_FECHA';
        SET p_mensaje     = 'El proceso de rectificación no se encuentra activo para este período académico.';
        LEAVE proc_sol;
    END IF;

    IF p_tipo_movimiento IN ('RETIRO', 'CAMBIO_SECCION') AND NOT EXISTS (
        SELECT 1 FROM detalle_matricula
        WHERE id_matricula = p_id_matricula AND id_seccion = p_id_seccion_origen AND estado = 'INSCRITO'
    ) THEN
        ROLLBACK;
        SET p_codigo_resp = 'NO_INSCRITO';
        SET p_mensaje     = 'El estudiante no está inscrito en la sección de origen.';
        LEAVE proc_sol;
    END IF;

    IF p_tipo_movimiento IN ('AGREGAR', 'CAMBIO_SECCION') AND NOT EXISTS (
        SELECT 1 FROM secciones WHERE id_seccion = p_id_seccion_destino AND id_periodo = v_id_periodo
    ) THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_NO_VALIDA';
        SET p_mensaje     = 'La sección de destino no existe o es de otro período.';
        LEAVE proc_sol;
    END IF;

    IF p_tipo_movimiento = 'CAMBIO_SECCION' THEN
        SELECT id_curso INTO v_curso_orig FROM secciones WHERE id_seccion = p_id_seccion_origen;
        SELECT id_curso INTO v_curso_dest FROM secciones WHERE id_seccion = p_id_seccion_destino;
        IF v_curso_orig <> v_curso_dest THEN
            ROLLBACK;
            SET p_codigo_resp = 'CURSOS_DISTINTOS';
            SET p_mensaje     = 'El cambio de sección debe ser dentro del mismo curso.';
            LEAVE proc_sol;
        END IF;
    END IF;

    -- Acta provisional única; luego se reemplaza por el formato definitivo basado en el id
    INSERT INTO rectificaciones (id_matricula, id_admin, nro_acta, estado, observaciones)
    VALUES (p_id_matricula, NULL, SUBSTRING(REPLACE(UUID(), '-', ''), 1, 32), 'SOLICITADO',
            CONCAT('Solicitud de ', p_tipo_movimiento));

    SET p_id_rectificacion = LAST_INSERT_ID();

    UPDATE rectificaciones
    SET nro_acta = CONCAT('ACTA-REC-', LPAD(p_id_rectificacion, 7, '0'))
    WHERE id_rectificacion = p_id_rectificacion;

    INSERT INTO detalle_rectificacion (id_rectificacion, id_seccion, tipo_movimiento, seccion_destino)
    VALUES (p_id_rectificacion, COALESCE(p_id_seccion_origen, p_id_seccion_destino),
            p_tipo_movimiento, p_id_seccion_destino);

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (NULL, 'RECTIFICACION', 'SOLICITAR', 'rectificaciones', p_id_rectificacion,
            CONCAT('Matrícula ', p_id_matricula, ' | ', p_tipo_movimiento));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Solicitud de rectificación registrada y en espera de atención.';
END proc_sol$$

DROP PROCEDURE IF EXISTS sp_aprobar_rectificacion$$
CREATE PROCEDURE sp_aprobar_rectificacion(
    IN  p_id_rectificacion INT UNSIGNED,
    IN  p_id_admin         INT UNSIGNED,
    OUT p_codigo_resp      VARCHAR(25),
    OUT p_mensaje          VARCHAR(255)
)
proc_aprob: BEGIN
    DECLARE v_id_matricula INT UNSIGNED;
    DECLARE v_estado       VARCHAR(20);
    DECLARE v_acta         VARCHAR(35);
    DECLARE v_tipo         VARCHAR(20);
    DECLARE v_origen       INT UNSIGNED;
    DECLARE v_destino      INT UNSIGNED;
    DECLARE v_dummy        INT UNSIGNED;
    DECLARE v_resp         VARCHAR(25) DEFAULT 'EXITO';
    DECLARE v_msg_aux      VARCHAR(255) DEFAULT '';
    DECLARE v_errno        INT;
    DECLARE v_msg          TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_id_admin IS NULL THEN
        SET p_codigo_resp = 'ADMIN_REQUERIDO';
        SET p_mensaje     = 'Solo un administrador puede aprobar rectificaciones.';
        LEAVE proc_aprob;
    END IF;

    START TRANSACTION;

    SELECT id_matricula, estado, nro_acta INTO v_id_matricula, v_estado, v_acta
    FROM rectificaciones WHERE id_rectificacion = p_id_rectificacion FOR UPDATE;

    IF v_id_matricula IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'NO_ENCONTRADA';
        SET p_mensaje     = 'La solicitud de rectificación no existe.';
        LEAVE proc_aprob;
    END IF;

    IF v_estado <> 'SOLICITADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_RESUELTA';
        SET p_mensaje     = CONCAT('La solicitud ya fue atendida (estado ', v_estado, ').');
        LEAVE proc_aprob;
    END IF;

    SELECT tipo_movimiento, id_seccion, seccion_destino INTO v_tipo, v_origen, v_destino
    FROM detalle_rectificacion WHERE id_rectificacion = p_id_rectificacion LIMIT 1;

    -- Ejecución en modo INTERNO: la ventana ya se validó al solicitar
    IF v_tipo = 'RETIRO' THEN
        CALL sp_i_retirar_curso(v_id_matricula, v_origen, v_resp, v_msg_aux);

    ELSEIF v_tipo = 'AGREGAR' THEN
        CALL sp_i_inscribir_curso(v_id_matricula, v_destino, 'INTERNO', v_resp, v_msg_aux);

    ELSE -- CAMBIO_SECCION
        -- Pre-bloqueo de ambas secciones en orden ascendente (previene deadlocks cruzados)
        SELECT id_seccion INTO v_dummy FROM secciones WHERE id_seccion = LEAST(v_origen, v_destino) FOR UPDATE;
        SELECT id_seccion INTO v_dummy FROM secciones WHERE id_seccion = GREATEST(v_origen, v_destino) FOR UPDATE;

        CALL sp_i_retirar_curso(v_id_matricula, v_origen, v_resp, v_msg_aux);
        IF v_resp = 'EXITO' THEN
            CALL sp_i_inscribir_curso(v_id_matricula, v_destino, 'INTERNO', v_resp, v_msg_aux);
        END IF;
    END IF;

    IF v_resp <> 'EXITO' THEN
        ROLLBACK;  -- deshace también el retiro de origen de un cambio de sección
        SET p_codigo_resp = v_resp;
        SET p_mensaje     = CONCAT('No se pudo aprobar (nada fue modificado): ', v_msg_aux);
        LEAVE proc_aprob;
    END IF;

    UPDATE rectificaciones
    SET estado = 'APROBADO', id_admin = p_id_admin, fecha_confirmacion = NOW()
    WHERE id_rectificacion = p_id_rectificacion;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'RECTIFICACION', 'APROBAR', 'rectificaciones', p_id_rectificacion,
            CONCAT(v_acta, ' | ', v_tipo));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Rectificación aprobada y ejecutada. Acta: ', v_acta);
END proc_aprob$$

DROP PROCEDURE IF EXISTS sp_rechazar_rectificacion$$
CREATE PROCEDURE sp_rechazar_rectificacion(
    IN  p_id_rectificacion INT UNSIGNED,
    IN  p_id_admin         INT UNSIGNED,
    IN  p_observaciones    VARCHAR(500),
    OUT p_codigo_resp      VARCHAR(25),
    OUT p_mensaje          VARCHAR(255)
)
proc_rech: BEGIN
    DECLARE v_id_matricula INT UNSIGNED;
    DECLARE v_estado       VARCHAR(20);
    DECLARE v_acta         VARCHAR(35);
    DECLARE v_errno        INT;
    DECLARE v_msg          TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_id_admin IS NULL THEN
        SET p_codigo_resp = 'ADMIN_REQUERIDO';
        SET p_mensaje     = 'Solo un administrador puede rechazar rectificaciones.';
        LEAVE proc_rech;
    END IF;

    START TRANSACTION;

    SELECT id_matricula, estado, nro_acta INTO v_id_matricula, v_estado, v_acta
    FROM rectificaciones WHERE id_rectificacion = p_id_rectificacion FOR UPDATE;

    IF v_id_matricula IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'NO_ENCONTRADA';
        SET p_mensaje     = 'La solicitud de rectificación no existe.';
        LEAVE proc_rech;
    END IF;

    IF v_estado <> 'SOLICITADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'YA_RESUELTA';
        SET p_mensaje     = CONCAT('La solicitud ya fue atendida (estado ', v_estado, ').');
        LEAVE proc_rech;
    END IF;

    UPDATE rectificaciones
    SET estado = 'RECHAZADO', id_admin = p_id_admin, fecha_confirmacion = NOW(),
        observaciones = COALESCE(p_observaciones, 'Rechazada por la administración')
    WHERE id_rectificacion = p_id_rectificacion;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'RECTIFICACION', 'RECHAZAR', 'rectificaciones', p_id_rectificacion, v_acta);

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Solicitud rechazada. Acta: ', v_acta);
END proc_rech$$


-- =============================================================================
-- PARTE 9: PAGOS (TX-03) Y CIERRE DE SEMESTRE (TX-05)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- sp_registrar_pago (TX-03)
-- FOR UPDATE sobre la deuda serializa los pagos de una misma deuda. El correlativo
-- de la serie B001 se calcula con FOR UPDATE dentro de la transacción; el UNIQUE
-- uq_serie_correlativo es la red de seguridad: ante colisión se hace ROLLBACK y
-- se devuelve ERROR_SQL para que el backend reintente.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_registrar_pago$$
CREATE PROCEDURE sp_registrar_pago(
    IN  p_id_deuda        INT UNSIGNED,
    IN  p_id_admin        INT UNSIGNED,
    IN  p_monto           DECIMAL(8,2),
    IN  p_metodo          VARCHAR(20),
    IN  p_nro_operacion   VARCHAR(60),
    OUT p_codigo_resp     VARCHAR(25),
    OUT p_mensaje         VARCHAR(255)
)
proc_pago: BEGIN
    DECLARE v_id_estudiante   INT UNSIGNED;
    DECLARE v_estado_deuda    VARCHAR(20);
    DECLARE v_saldo_actual    DECIMAL(8,2);
    DECLARE v_nuevo_saldo     DECIMAL(8,2);
    DECLARE v_id_pago         INT UNSIGNED;
    DECLARE v_correlativo     INT UNSIGNED;
    DECLARE v_nuevo_estado    VARCHAR(20);
    DECLARE v_errno           INT;
    DECLARE v_msg             TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    IF p_monto IS NULL OR p_monto <= 0 THEN
        SET p_codigo_resp = 'MONTO_INVALIDO';
        SET p_mensaje     = 'El monto del pago debe ser estrictamente mayor a 0.';
        LEAVE proc_pago;
    END IF;

    IF p_metodo NOT IN ('VENTANILLA', 'BANCO', 'TARJETA', 'TRANSFERENCIA') THEN
        SET p_codigo_resp = 'METODO_INVALIDO';
        SET p_mensaje     = 'El método de pago no es válido. Debe ser VENTANILLA, BANCO, TARJETA o TRANSFERENCIA.';
        LEAVE proc_pago;
    END IF;

    IF EXISTS (SELECT 1 FROM pagos WHERE nro_operacion = p_nro_operacion) THEN
        SET p_codigo_resp = 'OPERACION_EXISTE';
        SET p_mensaje     = 'El número de operación ya fue registrado previamente en el sistema.';
        LEAVE proc_pago;
    END IF;

    START TRANSACTION;

    SELECT id_estudiante, estado
    INTO   v_id_estudiante, v_estado_deuda
    FROM   deudas_estudiante
    WHERE  id_deuda = p_id_deuda
    FOR UPDATE;

    IF v_id_estudiante IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'DEUDA_NO_EXISTE';
        SET p_mensaje     = 'La deuda indicada no existe en el sistema.';
        LEAVE proc_pago;
    END IF;

    IF v_estado_deuda = 'PAGADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'DEUDA_YA_PAGADA';
        SET p_mensaje     = 'La deuda ya se encuentra cancelada en su totalidad.';
        LEAVE proc_pago;
    END IF;

    IF v_estado_deuda = 'ANULADO' THEN
        ROLLBACK;
        SET p_codigo_resp = 'DEUDA_ANULADA';
        SET p_mensaje     = 'La deuda se encuentra anulada y no admite pagos.';
        LEAVE proc_pago;
    END IF;

    SET v_saldo_actual = fn_calcular_saldo_deuda(p_id_deuda);

    IF p_monto > v_saldo_actual THEN
        ROLLBACK;
        SET p_codigo_resp = 'MONTO_EXCEDE_SALDO';
        SET p_mensaje     = CONCAT('El monto ingresado (S/ ', p_monto, ') excede el saldo pendiente (S/ ', v_saldo_actual, ').');
        LEAVE proc_pago;
    END IF;

    INSERT INTO pagos (id_deuda, id_estudiante, id_admin, monto_pagado, metodo_pago, nro_operacion, fecha_pago)
    VALUES (p_id_deuda, v_id_estudiante, p_id_admin, p_monto, p_metodo, p_nro_operacion, NOW());

    SET v_id_pago = LAST_INSERT_ID();

    SET v_nuevo_saldo = fn_calcular_saldo_deuda(p_id_deuda);

    IF v_nuevo_saldo <= 0.00 THEN
        SET v_nuevo_estado = 'PAGADO';
    ELSE
        SET v_nuevo_estado = 'FRACCIONADO';
    END IF;

    UPDATE deudas_estudiante SET estado = v_nuevo_estado WHERE id_deuda = p_id_deuda;

    SELECT COALESCE(MAX(correlativo), 100) + 1
    INTO   v_correlativo
    FROM   comprobantes
    WHERE  serie = 'B001'
    FOR UPDATE;

    INSERT INTO comprobantes (id_pago, serie, correlativo, tipo_comprobante, monto_total, fecha_emision)
    VALUES (v_id_pago, 'B001', v_correlativo, 'BOLETA', p_monto, NOW());

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (
        COALESCE(p_id_admin, (SELECT id_usuario FROM estudiantes WHERE id_estudiante = v_id_estudiante)),
        'FINANZAS', 'REGISTRAR_PAGO', 'pagos', v_id_pago,
        CONCAT('Pago S/ ', p_monto, ' | Op: ', p_nro_operacion, ' | Comprobante B001-', LPAD(v_correlativo, 6, '0'), ' | Estado deuda: ', v_nuevo_estado)
    );

    COMMIT;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Pago registrado correctamente. Comprobante: B001-', LPAD(v_correlativo, 6, '0'), ' (Saldo restante: S/ ', v_nuevo_saldo, ')');
END proc_pago$$

-- -----------------------------------------------------------------------------
-- sp_cerrar_semestre_academico (TX-05)
-- Promedio del semestre, créditos aprobados y promedio ponderado acumulado; cierra
-- las colas del período (turnos y listas de espera) y marca el período CERRADO.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_cerrar_semestre_academico$$
CREATE PROCEDURE sp_cerrar_semestre_academico(
    IN  p_id_periodo         SMALLINT UNSIGNED,
    OUT p_total_actualizados INT,
    OUT p_mensaje            VARCHAR(255)
)
proc_cierre: BEGIN
    DECLARE v_estado_periodo VARCHAR(20);
    DECLARE v_errno          INT;
    DECLARE v_msg            TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_total_actualizados = 0;
        SET p_mensaje = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_total_actualizados = 0;
    START TRANSACTION;

    SELECT estado INTO v_estado_periodo
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo
    FOR UPDATE;

    IF v_estado_periodo IS NULL THEN
        ROLLBACK;
        SET p_mensaje = 'El período académico indicado no existe.';
        LEAVE proc_cierre;
    END IF;

    IF v_estado_periodo = 'CERRADO' THEN
        ROLLBACK;
        SET p_mensaje = 'El período académico ya se encuentra cerrado.';
        LEAVE proc_cierre;
    END IF;

    UPDATE estudiantes e
    JOIN (
        SELECT 
            m.id_estudiante,
            ROUND(SUM(dm.nota_final * c.creditos) / NULLIF(SUM(c.creditos), 0), 2) AS pps_calculado,
            SUM(IF(dm.nota_final >= fn_nota_minima(), c.creditos, 0)) AS creditos_aprobados_ciclo
        FROM matriculas m
        JOIN detalle_matricula dm ON dm.id_matricula = m.id_matricula
        JOIN secciones s ON s.id_seccion = dm.id_seccion
        JOIN cursos c ON c.id_curso = s.id_curso
        WHERE m.id_periodo = p_id_periodo
          AND dm.estado = 'INSCRITO'
          AND dm.nota_final IS NOT NULL
          AND m.estado != 'ANULADA'
        GROUP BY m.id_estudiante
    ) res ON e.id_estudiante = res.id_estudiante
    SET 
        e.promedio_semestre_anterior = res.pps_calculado,
        e.creditos_aprobados = e.creditos_aprobados + res.creditos_aprobados_ciclo;

    SET p_total_actualizados = ROW_COUNT();

    -- Promedio ponderado acumulado (todo el historial) de los matriculados del período
    UPDATE estudiantes e
    JOIN (
        SELECT 
            m.id_estudiante,
            ROUND(SUM(dm.nota_final * c.creditos) / NULLIF(SUM(c.creditos), 0), 2) AS pp
        FROM matriculas m
        JOIN detalle_matricula dm ON dm.id_matricula = m.id_matricula
        JOIN secciones s ON s.id_seccion = dm.id_seccion
        JOIN cursos c ON c.id_curso = s.id_curso
        WHERE dm.estado = 'INSCRITO'
          AND dm.nota_final IS NOT NULL
          AND m.estado != 'ANULADA'
          AND m.id_estudiante IN (SELECT id_estudiante FROM matriculas WHERE id_periodo = p_id_periodo)
        GROUP BY m.id_estudiante
    ) h ON h.id_estudiante = e.id_estudiante
    SET e.promedio_ponderado = h.pp
    WHERE h.pp IS NOT NULL;

    -- Cierre de las colas del período
    UPDATE cola_matricula
    SET estado_cola = 'ABANDONADO', hora_fin = NOW()
    WHERE id_periodo = p_id_periodo AND estado_cola IN ('EN_ESPERA', 'EN_TURNO');

    UPDATE lista_espera_seccion le
    JOIN secciones s ON s.id_seccion = le.id_seccion
    SET le.estado = 'EXPIRADO', le.fecha_atencion = NOW()
    WHERE s.id_periodo = p_id_periodo AND le.estado = 'EN_ESPERA';

    UPDATE periodos_academicos SET estado = 'CERRADO' WHERE id_periodo = p_id_periodo;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (NULL, 'ACADEMICO', 'CIERRE_SEMESTRE', 'periodos_academicos', p_id_periodo,
            CONCAT('Cierre formal del período id ', p_id_periodo, '. Estudiantes actualizados: ', p_total_actualizados));

    COMMIT;

    SET p_mensaje = CONCAT('Semestre académico cerrado exitosamente. Estudiantes actualizados: ', p_total_actualizados);
END proc_cierre$$


-- =============================================================================
-- PARTE 10: PROCEDIMIENTOS DE CONSULTA (solo lectura)
-- =============================================================================

DROP PROCEDURE IF EXISTS sp_consultar_matricula$$
CREATE PROCEDURE sp_consultar_matricula(
    IN p_id_estudiante INT UNSIGNED,
    IN p_id_periodo    SMALLINT UNSIGNED
)
BEGIN
    SELECT 
        m.id_matricula, m.nro_constancia, m.estado AS estado_matricula,
        m.total_creditos, m.creditos_adicionales, m.tipo_matricula, m.fecha_matricula,
        p.codigo_periodo,
        CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
        e.codigo_universitario, e.estado_academico, e.promedio_semestre_anterior
    FROM matriculas m
    JOIN periodos_academicos p ON p.id_periodo = m.id_periodo
    JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
    JOIN usuarios u ON u.id_usuario = e.id_usuario
    WHERE m.id_estudiante = p_id_estudiante
      AND m.id_periodo = p_id_periodo;

    SELECT 
        dm.id_detalle, s.id_seccion, c.codigo_curso, c.nombre AS nombre_curso, c.creditos,
        s.codigo_seccion, s.turno, a.codigo_aula,
        dm.estado AS estado_curso, dm.origen, dm.nota_final
    FROM matriculas m
    JOIN detalle_matricula dm ON dm.id_matricula = m.id_matricula
    JOIN secciones s ON s.id_seccion = dm.id_seccion
    JOIN cursos c ON c.id_curso = s.id_curso
    JOIN aulas a ON a.id_aula = s.id_aula
    WHERE m.id_estudiante = p_id_estudiante
      AND m.id_periodo = p_id_periodo
    ORDER BY c.codigo_curso;
END$$

DROP PROCEDURE IF EXISTS sp_consultar_vacantes$$
CREATE PROCEDURE sp_consultar_vacantes(
    IN p_id_periodo SMALLINT UNSIGNED
)
BEGIN
    SELECT 
        s.id_seccion, p.codigo_periodo, c.codigo_curso, c.nombre AS curso, c.creditos,
        s.codigo_seccion, s.turno, a.codigo_aula, a.nombre AS aula,
        CONCAT(ud.apellidos, ', ', ud.nombres) AS docente,
        s.vacantes_total, s.vacantes_extra, s.vacantes_ocupadas,
        fn_vacantes_disponibles(s.id_seccion) AS vacantes_libres,
        CASE 
            WHEN fn_vacantes_disponibles(s.id_seccion) IS NULL THEN 'NO_EXISTE'
            WHEN fn_vacantes_disponibles(s.id_seccion) = 0 THEN 'LLENA'
            ELSE 'DISPONIBLE'
        END AS disponibilidad,
        (SELECT COUNT(*) FROM lista_espera_seccion le
          WHERE le.id_seccion = s.id_seccion AND le.estado = 'EN_ESPERA') AS personas_en_espera
    FROM secciones s
    JOIN periodos_academicos p ON p.id_periodo = s.id_periodo
    JOIN cursos c ON c.id_curso = s.id_curso
    JOIN aulas a ON a.id_aula = s.id_aula
    JOIN usuarios ud ON ud.id_usuario = s.id_docente
    WHERE s.id_periodo = p_id_periodo
    ORDER BY c.codigo_curso, s.codigo_seccion;
END$$

DELIMITER ;

-- (Opcional) Evento que libera turnos vencidos cada minuto.
-- Requiere: SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS ev_expirar_turnos
-- ON SCHEDULE EVERY 1 MINUTE
-- DO CALL sp_expirar_turnos_cola(2, 15, @total);


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


-- =============================================================================
-- PARTE 12: CONSULTAS DE VERIFICACIÓN DE CONSISTENCIA
-- =============================================================================

-- Verificación 1: total_creditos de la matrícula vs suma real (fn_calcular_creditos_matricula)
SELECT 
    m.id_matricula,
    m.nro_constancia,
    m.total_creditos AS creditos_en_matricula,
    fn_calcular_creditos_matricula(m.id_matricula) AS creditos_segun_detalle,
    IF(m.total_creditos = fn_calcular_creditos_matricula(m.id_matricula), 'CONSISTENTE', 'DISCREPANCIA') AS estado_creditos
FROM matriculas m
ORDER BY m.id_matricula;

-- Verificación 2: vacantes ocupadas en secciones vs total real en detalle_matricula
SELECT 
    s.id_seccion,
    s.id_periodo, 
    s.codigo_seccion,
    c.nombre AS curso,
    s.vacantes_total,
    s.vacantes_ocupadas AS ocupadas_en_seccion,
    COUNT(dm.id_detalle) AS ocupadas_segun_detalle,
    fn_vacantes_disponibles(s.id_seccion) AS vacantes_disponibles,
    CASE 
        WHEN s.id_seccion = 2 AND s.vacantes_ocupadas = s.vacantes_total THEN 'CONSISTENTE (SECCIÓN LLENA DE PRUEBA)'
        WHEN s.vacantes_ocupadas = COUNT(dm.id_detalle) THEN 'CONSISTENTE'
        ELSE 'DISCREPANCIA'
    END AS estado_vacantes
FROM secciones s
JOIN cursos c ON c.id_curso = s.id_curso
LEFT JOIN detalle_matricula dm ON dm.id_seccion = s.id_seccion AND dm.estado = 'INSCRITO'
GROUP BY s.id_seccion, s.id_periodo, s.codigo_seccion, c.nombre, s.vacantes_total, s.vacantes_ocupadas
ORDER BY s.id_periodo, s.id_seccion;

-- =============================================================================
-- FIN DEL SCRIPT COMPLETO v4.0
-- =============================================================================