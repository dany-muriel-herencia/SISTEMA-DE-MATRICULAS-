-- Esquema de Rectificación de Matrícula (Módulo 10)
CREATE DATABASE IF NOT EXISTS sgau_enrollment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sgau_enrollment;

CREATE TABLE IF NOT EXISTS rectificaciones (
    id_rectificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT NOT NULL,
    id_admin INT NOT NULL,
    nro_acta VARCHAR(50) NOT NULL UNIQUE,
    estado ENUM('SOLICITADO', 'APROBADO', 'RECHAZADO') NOT NULL DEFAULT 'SOLICITADO',
    observaciones TEXT NULL,
    fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_confirmacion DATETIME NULL,
    CONSTRAINT fk_rect_matricula FOREIGN KEY (id_matricula) REFERENCES matriculas (id_matricula)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detalle_rectificacion (
    id_detalle_rect BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_rectificacion INT NOT NULL,
    id_seccion INT NOT NULL,
    tipo_movimiento ENUM('ALTA', 'BAJA', 'CAMBIO_SECCION') NOT NULL,
    creditos INT NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    CONSTRAINT fk_det_rect FOREIGN KEY (id_rectificacion) REFERENCES rectificaciones (id_rectificacion)
) ENGINE=InnoDB;

