USE bd_matricula_universitaria;

-- =============================================================================
-- PROCEDIMIENTO 06: sp_espera_gestionar (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-05 (M05 / Cola 2: Lista de Espera por Sección Llena)
-- Unifica INSCRIBIR y CANCELAR en lista_espera_seccion según p_accion.
-- Respeta orden de bloqueo canónico: matriculas -> secciones (FOR UPDATE).
-- =============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_espera_gestionar$$
CREATE PROCEDURE sp_espera_gestionar( -- Dany
    IN  p_accion       VARCHAR(15),
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_posicion     SMALLINT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_esp_gest: BEGIN
    DECLARE v_act        VARCHAR(15);
    DECLARE v_id_est     INT UNSIGNED;
    DECLARE v_per_mat    SMALLINT UNSIGNED;
    DECLARE v_estado_mat VARCHAR(20);
    DECLARE v_id_curso   SMALLINT UNSIGNED;
    DECLARE v_per_sec    SMALLINT UNSIGNED;
    DECLARE v_vac_total  SMALLINT UNSIGNED;
    DECLARE v_vac_extra  SMALLINT UNSIGNED;
    DECLARE v_vac_ocup   SMALLINT UNSIGNED;
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
    SET v_act = UPPER(TRIM(COALESCE(p_accion, '')));

    IF v_act NOT IN ('INSCRIBIR', 'CANCELAR') THEN
        SET p_codigo_resp = 'ACCION_INVALIDA';
        SET p_mensaje     = 'p_accion debe ser INSCRIBIR o CANCELAR.';
        LEAVE proc_esp_gest;
    END IF;

    IF v_act = 'CANCELAR' THEN
        START TRANSACTION;
        UPDATE lista_espera_seccion
        SET estado = 'CANCELADO', fecha_atencion = NOW()
        WHERE id_seccion = p_id_seccion
          AND id_matricula = p_id_matricula
          AND estado = 'EN_ESPERA';

        IF ROW_COUNT() = 0 THEN
            ROLLBACK;
            SET p_codigo_resp = 'NO_EN_ESPERA';
            SET p_mensaje     = 'No tiene una espera activa en esa sección.';
            LEAVE proc_esp_gest;
        END IF;

        COMMIT;
        SET p_codigo_resp = 'EXITO';
        SET p_mensaje     = 'Espera cancelada correctamente.';
        LEAVE proc_esp_gest;
    END IF;

    -- ACCIÓN: INSCRIBIR (orden canónico: matriculas -> secciones)
    START TRANSACTION;

    SELECT id_estudiante, id_periodo, estado
    INTO   v_id_est, v_per_mat, v_estado_mat
    FROM   matriculas
    WHERE  id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_id_est IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'No se encontró la matrícula indicada.';
        LEAVE proc_esp_gest;
    END IF;

    IF v_estado_mat NOT IN ('PENDIENTE', 'CONFIRMADA') THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula está en estado "', v_estado_mat, '" y no admite lista de espera.');
        LEAVE proc_esp_gest;
    END IF;

    SELECT id_curso, id_periodo, vacantes_total, vacantes_extra, vacantes_ocupadas
    INTO   v_id_curso, v_per_sec, v_vac_total, v_vac_extra, v_vac_ocup
    FROM   secciones
    WHERE  id_seccion = p_id_seccion
    FOR UPDATE;

    IF v_id_curso IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe.';
        LEAVE proc_esp_gest;
    END IF;

    IF v_per_mat <> v_per_sec THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_OTRO_PERIODO';
        SET p_mensaje     = 'La sección pertenece a otro período académico.';
        LEAVE proc_esp_gest;
    END IF;

    SELECT inicio_matricula, fin_rectificacion, estado
    INTO   v_ini, v_fin, v_estado_per
    FROM   periodos_academicos
    WHERE  id_periodo = v_per_mat;

    IF v_estado_per <> 'ACTIVO' OR NOW() < v_ini OR NOW() > v_fin THEN
        ROLLBACK;
        SET p_codigo_resp = 'FUERA_DE_PERIODO';
        SET p_mensaje     = 'La lista de espera no está habilitada en este momento.';
        LEAVE proc_esp_gest;
    END IF;

    IF ((CAST(v_vac_total AS SIGNED) + CAST(v_vac_extra AS SIGNED)) - CAST(v_vac_ocup AS SIGNED)) > 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'HAY_VACANTES';
        SET p_mensaje     = 'La sección aún tiene vacantes; inscríbase directamente.';
        LEAVE proc_esp_gest;
    END IF;

    IF EXISTS (
        SELECT 1 FROM detalle_matricula dm
        JOIN secciones s ON s.id_seccion = dm.id_seccion
        WHERE dm.id_matricula = p_id_matricula AND s.id_curso = v_id_curso AND dm.estado = 'INSCRITO'
    ) THEN
        ROLLBACK;
        SET p_codigo_resp = 'CURSO_DUPLICADO';
        SET p_mensaje     = 'El estudiante ya tiene este curso inscrito en otra sección.';
        LEAVE proc_esp_gest;
    END IF;

    IF fn_validar_prerrequisitos(v_id_est, v_id_curso) = 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'FALTAN_PRERREQUISITOS';
        SET p_mensaje     = 'El estudiante no cumple el plan o los prerrequisitos del curso.';
        LEAVE proc_esp_gest;
    END IF;

    SELECT id_espera, estado, posicion
    INTO   v_id_espera, v_estado_esp, v_pos_act
    FROM   lista_espera_seccion
    WHERE  id_seccion = p_id_seccion AND id_matricula = p_id_matricula;

    IF v_estado_esp = 'EN_ESPERA' THEN
        ROLLBACK;
        SET p_posicion    = v_pos_act;
        SET p_codigo_resp = 'YA_EN_ESPERA';
        SET p_mensaje     = 'Ya se encuentra en la lista de espera de esta sección.';
        LEAVE proc_esp_gest;
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
            CONCAT('Matrícula ', p_id_matricula, ' en espera de sección ', p_id_seccion, ' (pos ', v_nueva, ')'));

    COMMIT;
    SET p_posicion    = v_nueva;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Registrado en lista de espera, posición ', v_nueva, '.');
END proc_esp_gest$$

DELIMITER ;

-- Prueba rápida (Seed v4.0 - Sección 2 está 30/30 llena):
-- CALL sp_espera_gestionar('INSCRIBIR', 101, 2, @pos, @cod, @msg);
-- SELECT @pos, @cod, @msg;
