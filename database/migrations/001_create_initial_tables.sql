-- =============================================================================
-- MIGRACIÓN 001: CREACIÓN DE TABLAS INICIALES
-- Fecha: 2026-09-09
-- =============================================================================

-- Incluye la creación de:
-- roles, usuarios, facultades, carreras, planes_estudio, estudiantes,
-- cursos, plan_cursos, prerrequisitos, periodos_academicos,
-- aulas, secciones, horarios, matriculas, matricula_detalles.

SOURCE ../schema/01_initial_schema.sql;
