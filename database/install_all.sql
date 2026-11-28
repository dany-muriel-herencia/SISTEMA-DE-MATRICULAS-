-- =============================================================================
-- INSTALADOR MAESTRO MODULAR — SGAU / UNJBG (bd_matricula_universitaria v4.0)
-- =============================================================================
-- Ejecutar desde la carpeta `database/`:
--   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < install_all.sql
-- =============================================================================

-- 1. Estructura y Vistas
SOURCE 01_schema/01_tablas_ddl.sql;
SOURCE 01_schema/02_vistas.sql;
SOURCE 01_schema/v_usuarios_seguridad_dany.sql;
SOURCE 01_schema/v_malla_curricular_detalle_dany.sql;

-- 2. Funciones Almacenadas de Negocio (fn_*)
SOURCE 02_functions/01_funciones_negocio.sql;
SOURCE 02_functions/fn_validar_prerrequisitos_dany.sql;
SOURCE 02_functions/fn_detectar_cruce_horario_dany.sql;
SOURCE 02_functions/fn_creditos_permitidos_dany.sql;
SOURCE 02_functions/fn_calcular_creditos_matricula_dany.sql;

-- 3. Procedimientos Almacenados (sp_i_* y sp_*)
SOURCE 03_procedures/01_internos_y_base_matricula.sql;
SOURCE 03_procedures/02_cola_turnos_matricula.sql;
SOURCE 03_procedures/03_matricula_y_cursos.sql;
SOURCE 03_procedures/04_lista_espera_seccion.sql;
SOURCE 03_procedures/05_rectificaciones.sql;
SOURCE 03_procedures/06_pagos_y_cierre_semestre.sql;
SOURCE 03_procedures/07_consultas_lectura.sql;

-- Procedimientos reducidos de Dany (ISSUE-02, ISSUE-04, ISSUE-05)
SOURCE 03_procedures/sp_guardar_usuario_seguridad_dany.sql;
SOURCE 03_procedures/sp_guardar_estructura_academica_dany.sql;
SOURCE 03_procedures/sp_registrar_matricula_completa_dany.sql;
SOURCE 03_procedures/sp_retirar_curso_o_anular_dany.sql;
SOURCE 03_procedures/sp_aplicar_excepcion_matricula_dany.sql;
SOURCE 03_procedures/sp_espera_gestionar_dany.sql;

-- 4. Triggers (trg_*)
SOURCE 02_triggers/trg_usuarios_auditoria_upd_dany.sql;

-- 5. Datos Semilla (Seed Coherente)
SOURCE 04_seeds/01_datos_prueba.sql;

-- 6. Verificación Automática de Consistencia
SOURCE 05_verificacion/01_consistencia.sql;
