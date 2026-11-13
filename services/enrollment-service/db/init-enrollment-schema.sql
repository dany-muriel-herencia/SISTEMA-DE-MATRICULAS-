-- Esquema de Matrícula y Cola Virtual (Módulos 5 y 6)
CREATE DATABASE IF NOT EXISTS sgau_enrollment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sgau_enrollment;

CREATE TABLE IF NOT EXISTS cola_matricula (
    id_cola BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_periodo INT NOT NULL,
    posicion INT NOT NULL,
    estado_cola ENUM('EN_ESPERA', 'EN_TURNO', 'FINALIZADO', 'ABANDONADO') NOT NULL DEFAULT 'EN_ESPERA',
    priorizado BOOLEAN NOT NULL DEFAULT FALSE,
    hora_ingreso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cola_periodo_estado (id_periodo, estado_cola, posicion)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS matriculas (
    id_matricula INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante INT NOT NULL,
    id_periodo INT NOT NULL,
    tipo_matricula ENUM('REGULAR', 'EXTEMPORANEA', 'ESPECIAL') NOT NULL DEFAULT 'REGULAR',
    estado ENUM('PENDIENTE', 'CONFIRMADA', 'OBSERVADA', 'ANULADA') NOT NULL DEFAULT 'PENDIENTE',
    total_creditos INT NOT NULL DEFAULT 0,
    creditos_adicionales INT NOT NULL DEFAULT 0,
    bypass_cruces BOOLEAN NOT NULL DEFAULT FALSE,
    bypass_deuda BOOLEAN NOT NULL DEFAULT FALSE,
    bypass_medico BOOLEAN NOT NULL DEFAULT FALSE,
    nro_constancia VARCHAR(50) NULL,
    fecha_matricula DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_estudiante_periodo (id_estudiante, id_periodo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS detalle_matricula (
    id_detalle BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT NOT NULL,
    id_seccion INT NOT NULL,
    estado ENUM('INSCRITO', 'RETIRADO', 'APROBADO', 'DESAPROBADO') NOT NULL DEFAULT 'INSCRITO',
    nota_final DECIMAL(4,2) NULL,
    nota_teoria DECIMAL(4,2) NULL,
    nota_practica DECIMAL(4,2) NULL,
    origen ENUM('WEB', 'ADMINISTRATIVO', 'RECTIFICACION') NOT NULL DEFAULT 'WEB',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_detalle_matricula FOREIGN KEY (id_matricula) REFERENCES matriculas (id_matricula),
    UNIQUE KEY uk_matricula_seccion (id_matricula, id_seccion)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS excepciones_matricula (
    id_excepcion INT AUTO_INCREMENT PRIMARY KEY,
    id_matricula INT NOT NULL,
    tipo_excepcion ENUM('BYPASS_CRUCE', 'BYPASS_DEUDA', 'BYPASS_MEDICO', 'AMPLIACION_CREDITOS') NOT NULL,
    numero_rd VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,
    id_admin INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_excepcion_matricula FOREIGN KEY (id_matricula) REFERENCES matriculas (id_matricula)
) ENGINE=InnoDB;

