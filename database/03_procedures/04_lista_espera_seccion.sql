USE bd_matricula_universitaria;

DELIMITER $$

-- =============================================================================
-- PARTE 7: COLA 2 — LISTA DE ESPERA POR SECCIÓN (TX-06)
-- La promoción automática ocurre en sp_i_retirar_curso (retiro, anulación o
-- rectificación aprobada). sp_espera_promover permite reprocesarla manualmente.
-- =============================================================================

DROP PROCEDURE IF EXISTS sp_espera_inscribir$$
CREATE PROCEDURE sp_espera_inscribir(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_posicion     SMALLINT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_esp: BEGIN
    DECLARE v_id_curso   SMALLINT UNSIGNED;
    DECLARE v_per_sec    SMALLINT UNSIGNED;
    DECLARE v_id_est     INT UNSIGNED;
    DECLARE v_per_mat    SMALLINT UNSIGNED;
    DECLARE v_estado_mat VARCHAR(20);
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
    START TRANSACTION;

    -- La fila de la sección actúa como mutex de su lista de espera
    SELECT id_curso, id_periodo INTO v_id_curso, v_per_sec
    FROM secciones WHERE id_seccion = p_id_seccion FOR UPDATE;

    IF v_id_curso IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe.';
        LEAVE proc_esp;
    END IF;

    SELECT id_estudiante, id_periodo, estado INTO v_id_est, v_per_mat, v_estado_mat
    FROM matriculas WHERE id_matricula = p_id_matricula FOR UPDATE;

    IF v_id_est IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'No se encontró la matrícula indicada.';
        LEAVE proc_esp;
    END IF;

    IF v_estado_mat NOT IN ('PENDIENTE', 'CONFIRMADA') THEN
        ROLLBACK;
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula está en estado "', v_estado_mat, '" y no admite lista de espera.');
        LEAVE proc_esp;
    END IF;

    IF v_per_mat <> v_per_sec THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_OTRO_PERIODO';
        SET p_mensaje     = 'La sección pertenece a otro período académico.';
        LEAVE proc_esp;
    END IF;

    SELECT inicio_matricula, fin_rectificacion, estado INTO v_ini, v_fin, v_estado_per
    FROM periodos_academicos WHERE id_periodo = v_per_mat;

    IF v_estado_per <> 'ACTIVO' OR NOW() < v_ini OR NOW() > v_fin THEN
        ROLLBACK;
        SET p_codigo_resp = 'FUERA_DE_PERIODO';
        SET p_mensaje     = 'La lista de espera no está habilitada en este momento.';
        LEAVE proc_esp;
    END IF;

    IF COALESCE(fn_vacantes_disponibles(p_id_seccion), 0) > 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'HAY_VACANTES';
        SET p_mensaje     = 'La sección aún tiene vacantes; inscríbase directamente.';
        LEAVE proc_esp;
    END IF;

    IF EXISTS (
        SELECT 1 FROM detalle_matricula dm
        JOIN secciones s ON s.id_seccion = dm.id_seccion
        WHERE dm.id_matricula = p_id_matricula AND s.id_curso = v_id_curso AND dm.estado = 'INSCRITO'
    ) THEN
        ROLLBACK;
        SET p_codigo_resp = 'CURSO_DUPLICADO';
        SET p_mensaje     = 'El estudiante ya tiene este curso inscrito en otra sección.';
        LEAVE proc_esp;
    END IF;

    IF fn_validar_curso_en_plan(v_id_est, v_id_curso) = 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'CURSO_NO_EN_PLAN';
        SET p_mensaje     = 'La asignatura no pertenece al plan de estudios del estudiante.';
        LEAVE proc_esp;
    END IF;

    IF fn_validar_prerrequisitos(v_id_est, v_id_curso) = 0 THEN
        ROLLBACK;
        SET p_codigo_resp = 'FALTAN_PRERREQUISITOS';
        SET p_mensaje     = 'El estudiante no cumple los prerrequisitos del curso.';
        LEAVE proc_esp;
    END IF;

    SELECT id_espera, estado, posicion INTO v_id_espera, v_estado_esp, v_pos_act
    FROM lista_espera_seccion
    WHERE id_seccion = p_id_seccion AND id_matricula = p_id_matricula;

    IF v_estado_esp = 'EN_ESPERA' THEN
        ROLLBACK;
        SET p_posicion    = v_pos_act;
        SET p_codigo_resp = 'YA_EN_ESPERA';
        SET p_mensaje     = 'Ya se encuentra en la lista de espera de esta sección.';
        LEAVE proc_esp;
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
            CONCAT('Matrícula ', p_id_matricula, ' en espera de la sección ', p_id_seccion, ' (posición ', v_nueva, ')'));

    COMMIT;
    SET p_posicion    = v_nueva;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Registrado en la lista de espera, posición ', v_nueva, '.');
END proc_esp$$

DROP PROCEDURE IF EXISTS sp_espera_cancelar$$
CREATE PROCEDURE sp_espera_cancelar(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
BEGIN
    UPDATE lista_espera_seccion
    SET estado = 'CANCELADO', fecha_atencion = NOW()
    WHERE id_seccion = p_id_seccion AND id_matricula = p_id_matricula AND estado = 'EN_ESPERA';

    IF ROW_COUNT() = 0 THEN
        SET p_codigo_resp = 'NO_EN_ESPERA';
        SET p_mensaje     = 'No tiene una espera activa en esa sección.';
    ELSE
        SET p_codigo_resp = 'EXITO';
        SET p_mensaje     = 'Espera cancelada correctamente.';
    END IF;
END$$

DROP PROCEDURE IF EXISTS sp_espera_consultar$$
CREATE PROCEDURE sp_espera_consultar(IN p_id_matricula INT UNSIGNED)
BEGIN
    SELECT
        le.id_espera, le.id_seccion, c.codigo_curso, c.nombre AS curso, s.codigo_seccion,
        le.estado, le.posicion,
        (SELECT COUNT(*) FROM lista_espera_seccion x
          WHERE x.id_seccion = le.id_seccion AND x.estado = 'EN_ESPERA'
            AND x.posicion < le.posicion) AS personas_delante,
        le.fecha_registro, le.fecha_atencion
    FROM lista_espera_seccion le
    JOIN secciones s ON s.id_seccion = le.id_seccion
    JOIN cursos c    ON c.id_curso = s.id_curso
    WHERE le.id_matricula = p_id_matricula
    ORDER BY le.fecha_registro DESC;
END$$

-- Administrador: reprocesar la lista (p. ej. tras aumentar vacantes_extra)
DROP PROCEDURE IF EXISTS sp_espera_promover$$
CREATE PROCEDURE sp_espera_promover(
    IN  p_id_seccion  INT UNSIGNED,
    OUT p_procesados  INT,
    OUT p_codigo_resp VARCHAR(25),
    OUT p_mensaje     VARCHAR(255)
)
proc_prom: BEGIN
    DECLARE v_antes   INT DEFAULT 0;
    DECLARE v_despues INT DEFAULT 0;
    DECLARE v_dummy   INT UNSIGNED;
    DECLARE v_errno   INT;
    DECLARE v_msg     TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_procesados  = 0;
        SET p_codigo_resp = 'ERROR_SQL';
        SET p_mensaje     = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_procesados = 0;
    START TRANSACTION;

    SELECT id_seccion INTO v_dummy FROM secciones WHERE id_seccion = p_id_seccion FOR UPDATE;

    IF v_dummy IS NULL THEN
        ROLLBACK;
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe.';
        LEAVE proc_prom;
    END IF;

    SELECT COUNT(*) INTO v_antes FROM lista_espera_seccion
    WHERE id_seccion = p_id_seccion AND estado = 'EN_ESPERA';

    CALL sp_i_espera_promover(p_id_seccion);

    SELECT COUNT(*) INTO v_despues FROM lista_espera_seccion
    WHERE id_seccion = p_id_seccion AND estado = 'EN_ESPERA';

    COMMIT;
    SET p_procesados  = v_antes - v_despues;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Solicitudes procesadas de la lista de espera: ', p_procesados);
END proc_prom$$

DELIMITER ;
