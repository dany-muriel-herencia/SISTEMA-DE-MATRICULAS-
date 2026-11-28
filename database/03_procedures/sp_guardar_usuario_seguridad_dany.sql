USE bd_matricula_universitaria;

-- =============================================================================
-- PROCEDIMIENTO 01: sp_guardar_usuario_seguridad (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-02 (M02: Seguridad, Roles y Auditoría) — CU-03, CU-04, CU-05, CU-06
-- Unifica 4 operaciones en 1 SP Upsert:
--   * Si p_id_usuario IS NULL o 0 -> Crea usuario validando DNI, código y correo.
--   * Si p_id_usuario > 0         -> Edita datos/rol, cambia estado en switch
--                                    (ACTIVO/INACTIVO/BLOQUEADO) o restablece clave.
-- =============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_guardar_usuario_seguridad$$
CREATE PROCEDURE sp_guardar_usuario_seguridad( 
    IN  p_id_usuario    INT UNSIGNED,
    IN  p_codigo        VARCHAR(20),
    IN  p_email         VARCHAR(100),
    IN  p_password_hash VARCHAR(255),
    IN  p_nombres       VARCHAR(100),
    IN  p_apellidos     VARCHAR(100),
    IN  p_dni           CHAR(8),
    IN  p_id_rol        TINYINT UNSIGNED,
    IN  p_estado        VARCHAR(15),
    IN  p_id_admin      INT UNSIGNED,
    OUT p_id_resultado  INT UNSIGNED,
    OUT p_codigo_resp   VARCHAR(25),
    OUT p_mensaje       VARCHAR(255)
)
proc_usr: BEGIN
    DECLARE v_id_existente INT UNSIGNED DEFAULT NULL;
    DECLARE v_rol_activo   TINYINT(1) DEFAULT NULL;
    DECLARE v_rol_ant      TINYINT UNSIGNED DEFAULT NULL;
    DECLARE v_estado_ant   VARCHAR(15) DEFAULT NULL;
    DECLARE v_rol_nuevo    TINYINT UNSIGNED DEFAULT NULL;
    DECLARE v_estado_nuevo VARCHAR(15) DEFAULT NULL;
    DECLARE v_estado_dest  VARCHAR(15);
    DECLARE v_accion       VARCHAR(40);
    DECLARE v_errno        INT;
    DECLARE v_msg          TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        SET @app_id_admin = NULL;
        ROLLBACK;
        SET p_id_resultado = NULL;
        SET p_codigo_resp  = 'ERROR_SQL';
        SET p_mensaje      = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_resultado = NULL;
    SET v_estado_dest  = COALESCE(NULLIF(TRIM(p_estado), ''), 'ACTIVO');

    IF v_estado_dest NOT IN ('ACTIVO', 'INACTIVO', 'BLOQUEADO') THEN
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = 'El estado debe ser ACTIVO, INACTIVO o BLOQUEADO.';
        LEAVE proc_usr;
    END IF;

    IF p_id_rol IS NOT NULL THEN
        SELECT activo INTO v_rol_activo FROM roles WHERE id_rol = p_id_rol;
        IF v_rol_activo IS NULL OR v_rol_activo = 0 THEN
            SET p_codigo_resp = 'ROL_NO_VALIDO';
            SET p_mensaje     = 'El rol indicado no existe o está inactivo.';
            LEAVE proc_usr;
        END IF;
    END IF;

    START TRANSACTION;

    IF COALESCE(p_id_usuario, 0) = 0 THEN
      
        IF CHAR_LENGTH(TRIM(COALESCE(p_codigo, ''))) = 0
           OR CHAR_LENGTH(TRIM(COALESCE(p_email, ''))) = 0
           OR CHAR_LENGTH(TRIM(COALESCE(p_password_hash, ''))) = 0
           OR CHAR_LENGTH(TRIM(COALESCE(p_nombres, ''))) = 0
           OR CHAR_LENGTH(TRIM(COALESCE(p_apellidos, ''))) = 0
           OR CHAR_LENGTH(TRIM(COALESCE(p_dni, ''))) <> 8
           OR p_id_rol IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'DATOS_INCOMPLETOS';
            SET p_mensaje     = 'Código, correo, clave, nombres, apellidos, DNI (8 dígitos) y rol son obligatorios.';
            LEAVE proc_usr;
        END IF;

        IF EXISTS (SELECT 1 FROM usuarios WHERE codigo_usuario = p_codigo) THEN
            ROLLBACK;
            SET p_codigo_resp = 'CODIGO_DUPLICADO';
            SET p_mensaje     = 'El código institucional ya está registrado.';
            LEAVE proc_usr;
        END IF;

        IF EXISTS (SELECT 1 FROM usuarios WHERE email_institucional = p_email) THEN
            ROLLBACK;
            SET p_codigo_resp = 'EMAIL_DUPLICADO';
            SET p_mensaje     = 'El correo institucional ya está registrado.';
            LEAVE proc_usr;
        END IF;

        IF EXISTS (SELECT 1 FROM usuarios WHERE dni = p_dni) THEN
            ROLLBACK;
            SET p_codigo_resp = 'DNI_DUPLICADO';
            SET p_mensaje     = 'El DNI ya pertenece a otra cuenta.';
            LEAVE proc_usr;
        END IF;

        INSERT INTO usuarios (
            codigo_usuario, email_institucional, password_hash,
            nombres, apellidos, dni, id_rol, estado
        ) VALUES (
            TRIM(p_codigo), TRIM(p_email), p_password_hash,
            TRIM(p_nombres), TRIM(p_apellidos), TRIM(p_dni), p_id_rol, v_estado_dest
        );

        SET p_id_resultado = LAST_INSERT_ID();
        SET v_accion       = 'CREAR_USUARIO';

        INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
        VALUES (p_id_admin, 'SEGURIDAD', v_accion, 'usuarios', p_id_resultado,
                CONCAT('Alta usuario ', TRIM(p_codigo), ' | Rol ', p_id_rol));

        COMMIT;
        SET p_codigo_resp = 'EXITO';
        SET p_mensaje     = 'Usuario registrado exitosamente.';
    ELSE
        -- MODO ACTUALIZACIÓN / ROL / SWITCH ESTADO 
        SELECT id_usuario, id_rol, estado
        INTO v_id_existente, v_rol_ant, v_estado_ant
        FROM usuarios
        WHERE id_usuario = p_id_usuario
        FOR UPDATE;

        IF v_id_existente IS NULL THEN
            ROLLBACK;
            SET p_codigo_resp = 'USUARIO_NO_EXISTE';
            SET p_mensaje     = 'El usuario indicado no existe.';
            LEAVE proc_usr;
        END IF;

        IF NULLIF(TRIM(p_email), '') IS NOT NULL AND EXISTS (
            SELECT 1 FROM usuarios WHERE email_institucional = TRIM(p_email) AND id_usuario <> p_id_usuario
        ) THEN
            ROLLBACK;
            SET p_codigo_resp = 'EMAIL_DUPLICADO';
            SET p_mensaje     = 'El correo ya está asignado a otro usuario.';
            LEAVE proc_usr;
        END IF;

        SET v_rol_nuevo    = COALESCE(p_id_rol, v_rol_ant);
        SET v_estado_nuevo = COALESCE(NULLIF(TRIM(p_estado), ''), v_estado_ant);

        -- Pasa p_id_admin al trigger trg_usuarios_auditoria_upd para registrar el actor en auditoria
        SET @app_id_admin = p_id_admin;

        UPDATE usuarios
        SET
            email_institucional = COALESCE(NULLIF(TRIM(p_email), ''), email_institucional),
            nombres             = COALESCE(NULLIF(TRIM(p_nombres), ''), nombres),
            apellidos           = COALESCE(NULLIF(TRIM(p_apellidos), ''), apellidos),
            id_rol              = v_rol_nuevo,
            password_hash       = COALESCE(NULLIF(TRIM(p_password_hash), ''), password_hash),
            estado              = v_estado_nuevo,
            intentos_fallidos   = IF(
                NULLIF(TRIM(p_password_hash), '') IS NOT NULL OR NULLIF(TRIM(p_estado), '') = 'ACTIVO',
                0,
                intentos_fallidos
            )
        WHERE id_usuario = p_id_usuario;

        SET @app_id_admin = NULL;
        SET p_id_resultado = p_id_usuario;

        -- Si cambió id_rol o estado, trg_usuarios_auditoria_upd ya registró OLD -> NEW en auditoria.
        -- Aquí solo registramos restablecimiento de clave o edición de perfil sin cambio de rol/estado.
        IF NULLIF(TRIM(p_password_hash), '') IS NOT NULL THEN
            SET v_accion = 'RESTABLECER_CLAVE';
            INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
            VALUES (COALESCE(p_id_admin, p_id_usuario), 'SEGURIDAD', v_accion, 'usuarios', p_id_usuario,
                    'Restablecimiento de contraseña');
        ELSEIF v_rol_nuevo = v_rol_ant AND v_estado_nuevo = v_estado_ant THEN
            SET v_accion = 'ACTUALIZAR_PERFIL';
            INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
            VALUES (COALESCE(p_id_admin, p_id_usuario), 'SEGURIDAD', v_accion, 'usuarios', p_id_usuario,
                    'Actualización de correo o datos personales');
        END IF;

        COMMIT;
        SET p_codigo_resp = 'EXITO';
        SET p_mensaje     = 'Usuario actualizado correctamente.';
    END IF;
END proc_usr$$

DELIMITER ;

-- Prueba rápida (Seed v4.0):
-- CALL sp_guardar_usuario_seguridad(11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'ACTIVO', 1, @id_res, @cod, @msg);
-- SELECT @id_res, @cod, @msg;
