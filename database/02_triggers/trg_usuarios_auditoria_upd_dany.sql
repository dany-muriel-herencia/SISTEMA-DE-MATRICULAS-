USE bd_matricula_universitaria;

-- =============================================================================
-- TRIGGER 01: trg_usuarios_auditoria_upd (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-02 (M02: Seguridad, Roles y Auditoría)
-- Se ejecuta dentro de la misma transacción del UPDATE sobre `usuarios`:
--   * Si el procedimiento hace ROLLBACK, la fila de `auditoria` también se revierte.
--   * Registra valores OLD -> NEW cuando cambia `id_rol` o `estado`.
--   * Usa `@app_id_admin` si proviene de sp_guardar_usuario_seguridad, o NEW.id_usuario
--     si el UPDATE se realiza directamente por SQL/phpMyAdmin.
-- =============================================================================

DELIMITER $$

DROP TRIGGER IF EXISTS trg_usuarios_auditoria_upd$$
CREATE TRIGGER trg_usuarios_auditoria_upd -- Dany
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    DECLARE v_accion  VARCHAR(40);
    DECLARE v_rol_old VARCHAR(50);
    DECLARE v_rol_new VARCHAR(50);
    DECLARE v_detalle VARCHAR(500);

    IF OLD.id_rol <> NEW.id_rol OR OLD.estado <> NEW.estado THEN
        IF OLD.id_rol <> NEW.id_rol AND OLD.estado <> NEW.estado THEN
            SET v_accion = 'CAMBIO_ROL_ESTADO';
        ELSEIF OLD.id_rol <> NEW.id_rol THEN
            SET v_accion = 'CAMBIO_ROL';
        ELSE
            SET v_accion = 'CAMBIO_ESTADO';
        END IF;

        SELECT nombre_rol INTO v_rol_old FROM roles WHERE id_rol = OLD.id_rol;
        SELECT nombre_rol INTO v_rol_new FROM roles WHERE id_rol = NEW.id_rol;

        SET v_detalle = LEFT(
            CONCAT(
                'Usuario ', NEW.codigo_usuario,
                IF(
                    OLD.id_rol <> NEW.id_rol,
                    CONCAT(
                        ' | Rol: ', COALESCE(v_rol_old, CAST(OLD.id_rol AS CHAR)), ' (', OLD.id_rol, ')',
                        ' -> ', COALESCE(v_rol_new, CAST(NEW.id_rol AS CHAR)), ' (', NEW.id_rol, ')'
                    ),
                    ''
                ),
                IF(
                    OLD.estado <> NEW.estado,
                    CONCAT(' | Estado: ', OLD.estado, ' -> ', NEW.estado),
                    ''
                )
            ),
            500
        );

        INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
        VALUES (
            COALESCE(@app_id_admin, NEW.id_usuario),
            'SEGURIDAD',
            v_accion,
            'usuarios',
            NEW.id_usuario,
            v_detalle
        );
    END IF;
END$$

DELIMITER ;

-- Prueba rápida (Seed v4.0):
-- CALL sp_guardar_usuario_seguridad(11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BLOQUEADO', 1, @id_res, @cod, @msg);
-- SELECT id_auditoria, id_usuario, modulo, accion, id_registro, detalle, fecha_hora
-- FROM auditoria WHERE tabla_afectada = 'usuarios' ORDER BY id_auditoria DESC LIMIT 5;
