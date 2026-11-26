-- =============================================================================
-- INSTALADOR MAESTRO MODULAR — SGAU / UNJBG (bd_matricula_universitaria v4.0)
-- =============================================================================
-- Ejecutar desde la carpeta `database/`:
--   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < install_all.sql
-- =============================================================================

-- 1. Estructura y Vistas
SOURCE 01_schema/01_tablas_ddl.sql;
SOURCE 01_schema/02_vistas.sql;

-- 2. Funciones Almacenadas de Negocio (fn_*)
SOURCE 02_functions/01_funciones_negocio.sql;

-- 3. Procedimientos Almacenados (sp_i_* y sp_*)
SOURCE 03_procedures/01_internos_y_base_matricula.sql;
SOURCE 03_procedures/02_cola_turnos_matricula.sql;
SOURCE 03_procedures/03_matricula_y_cursos.sql;
SOURCE 03_procedures/04_lista_espera_seccion.sql;
SOURCE 03_procedures/05_rectificaciones.sql;
SOURCE 03_procedures/06_pagos_y_cierre_semestre.sql;
SOURCE 03_procedures/07_consultas_lectura.sql;

-- 4. Datos Semilla (Seed Coherente)
SOURCE 04_seeds/01_datos_prueba.sql;

-- 5. Verificación Automática de Consistencia
SOURCE 05_verificacion/01_consistencia.sql;
