USE bd_matricula_universitaria;

-- =============================================================================
-- VISTA 01: v_usuarios_seguridad (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-02 (M02: Seguridad, Roles y Auditoría)
-- Devuelve el listado de usuarios sin exponer `password_hash`, junto con su rol,
-- estado y última operación registrada en `auditoria` (1 fila por usuario).
-- Alimenta la tabla principal y los 4 KPIs del módulo M02.
-- =============================================================================

CREATE OR REPLACE VIEW v_usuarios_seguridad AS -- Dany
SELECT
    u.id_usuario,
    u.codigo_usuario,
    u.email_institucional,
    u.nombres,
    u.apellidos,
    CONCAT(u.apellidos, ', ', u.nombres) AS nombre_completo,
    u.dni,
    u.id_rol,
    r.nombre_rol,
    r.descripcion AS descripcion_rol,
    u.estado,
    u.intentos_fallidos,
    u.ultimo_acceso,
    u.created_at,
    a.modulo AS ultimo_modulo,
    a.accion AS ultima_operacion,
    a.detalle AS ultimo_detalle_auditoria,
    a.fecha_hora AS fecha_ultima_operacion
FROM usuarios u
INNER JOIN roles r ON r.id_rol = u.id_rol
LEFT JOIN auditoria a ON a.id_auditoria = (
    SELECT MAX(a2.id_auditoria)
    FROM auditoria a2
    WHERE a2.id_usuario = u.id_usuario
       OR (a2.tabla_afectada = 'usuarios' AND a2.id_registro = u.id_usuario)
);

-- Pruebas rápidas (Seed v4.0):
-- SELECT * FROM v_usuarios_seguridad ORDER BY id_usuario;
-- SELECT COUNT(*) AS total_usuarios,
--        SUM(estado = 'ACTIVO') AS activos,
--        SUM(estado = 'BLOQUEADO') AS bloqueados,
--        SUM(id_rol = 1) AS administradores
-- FROM v_usuarios_seguridad;
