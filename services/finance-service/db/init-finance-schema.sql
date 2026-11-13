-- Esquema de Finanzas y Tesorería (Módulo 8)
CREATE DATABASE IF NOT EXISTS sgau_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sgau_finance;

CREATE TABLE IF NOT EXISTS conceptos_pago (
    id_concepto INT AUTO_INCREMENT PRIMARY KEY,
    codigo_tupa VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    tipo ENUM('MATRICULA_REGULAR', 'MATRICULA_EXTEMPORANEA', 'RECTIFICACION', 'CONSTANCIA', 'CERTIFICADO_ESTUDIOS') NOT NULL,
    vigente BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS deudas_estudiante (
    id_deuda BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_concepto INT NOT NULL,
    id_periodo INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    saldo_pendiente DECIMAL(10,2) NOT NULL,
    estado ENUM('PENDIENTE', 'FRACCIONADO', 'PAGADO', 'ANULADO') NOT NULL DEFAULT 'PENDIENTE',
    fecha_vencimiento DATE NOT NULL,
    CONSTRAINT fk_deuda_concepto FOREIGN KEY (id_concepto) REFERENCES conceptos_pago (id_concepto),
    INDEX idx_deuda_estudiante (id_estudiante, estado)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pagos (
    id_pago BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_deuda BIGINT NOT NULL,
    id_estudiante INT NOT NULL,
    id_admin INT NOT NULL,
    monto_pagado DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('VENTANILLA', 'BANCO_NACION', 'PASARELA_ONLINE') NOT NULL DEFAULT 'VENTANILLA',
    nro_operacion VARCHAR(50) DEFAULT '',
    fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pago_deuda FOREIGN KEY (id_deuda) REFERENCES deudas_estudiante (id_deuda)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comprobantes (
    id_comprobante BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_pago BIGINT NOT NULL UNIQUE,
    serie VARCHAR(4) NOT NULL DEFAULT 'B001',
    correlativo INT NOT NULL,
    tipo_comprobante ENUM('BOLETA', 'RECIBO_INGRESO', 'FACTURA') NOT NULL DEFAULT 'BOLETA',
    monto_total DECIMAL(10,2) NOT NULL,
    hash_qr TEXT NOT NULL,
    fecha_emision DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comprobante_pago FOREIGN KEY (id_pago) REFERENCES pagos (id_pago),
    UNIQUE KEY uk_serie_correlativo (serie, correlativo)
) ENGINE=InnoDB;

