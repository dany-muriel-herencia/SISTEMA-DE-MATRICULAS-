USE bd_matricula_universitaria;

DELIMITER $$

-- =============================================================================
-- PARTE 3: TRIGGERS
-- =============================================================================
-- v4 NO usa triggers. Toda la mutación (créditos, vacantes, estados de deuda,
-- créditos adicionales, auditoría) queda explícita en procedimientos
-- transaccionales, con orden de bloqueo controlado: matriculas -> secciones.


-- =============================================================================
-- PARTE 4: PROCEDIMIENTOS INTERNOS (sp_i_*) — SIN TRANSACCIÓN PROPIA
-- =============================================================================

-- -----------------------------------------------------------------------------
-- sp_i_inscribir_curso
-- p_modo = 'MATRICULA' : exige estar dentro de la ventana de matrícula.
-- p_modo = 'INTERNO'   : omite la ventana (ya la validó el llamador: rectificación
--                        aprobada o promoción desde lista de espera). El período
--                        debe seguir ACTIVO en ambos modos.
-- Orden de bloqueos: matriculas -> secciones. Si la sección ya se retiró antes
-- en la misma matrícula, se REACTIVA la fila (evita violar uq_detalle_mat_seccion).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_i_inscribir_curso$$
CREATE PROCEDURE sp_i_inscribir_curso(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    IN  p_modo         VARCHAR(10),
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_ins: BEGIN
    DECLARE v_id_estudiante     INT UNSIGNED;
    DECLARE v_id_periodo_mat    SMALLINT UNSIGNED;
    DECLARE v_estado_matricula  VARCHAR(20);
    DECLARE v_bypass_cruces     TINYINT(1) DEFAULT 0;
    DECLARE v_id_curso          SMALLINT UNSIGNED;
    DECLARE v_id_periodo_sec    SMALLINT UNSIGNED;
    DECLARE v_creditos_curso    TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_vac_total         SMALLINT UNSIGNED;
    DECLARE v_vac_ocup          SMALLINT UNSIGNED;
    DECLARE v_vac_extra         SMALLINT UNSIGNED;
    DECLARE v_inicio_matricula  DATETIME;
    DECLARE v_fin_matricula     DATETIME;
    DECLARE v_estado_periodo    VARCHAR(20);
    DECLARE v_veces_desaprobado INT DEFAULT 0;
    DECLARE v_origen_calc       VARCHAR(20);
    DECLARE v_id_detalle_prev   INT UNSIGNED;

    -- PASO 1: bloquear la MATRÍCULA
    SELECT m.id_estudiante, m.id_periodo, m.estado, m.bypass_cruces
    INTO   v_id_estudiante, v_id_periodo_mat, v_estado_matricula, v_bypass_cruces
    FROM   matriculas m
    WHERE  m.id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_id_estudiante IS NULL THEN
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'No se encontró la matrícula indicada.';
        LEAVE proc_ins;
    END IF;

    IF v_estado_matricula NOT IN ('PENDIENTE', 'CONFIRMADA') THEN
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula se encuentra en estado "', v_estado_matricula, '" y no admite inscripciones.');
        LEAVE proc_ins;
    END IF;

    -- PASO 2: período
    SELECT inicio_matricula, fin_matricula, estado
    INTO   v_inicio_matricula, v_fin_matricula, v_estado_periodo
    FROM   periodos_academicos
    WHERE  id_periodo = v_id_periodo_mat;

    IF v_estado_periodo IS NULL THEN
        SET p_codigo_resp = 'PERIODO_NO_EXISTE';
        SET p_mensaje     = 'El período académico de la matrícula no existe en el sistema.';
        LEAVE proc_ins;
    END IF;

    IF v_estado_periodo <> 'ACTIVO'
       OR (p_modo = 'MATRICULA' AND (NOW() < v_inicio_matricula OR NOW() > v_fin_matricula)) THEN
        SET p_codigo_resp = 'FUERA_DE_PERIODO';
        SET p_mensaje     = 'El período de inscripción no está activo o se encuentra fuera de fechas hábiles.';
        LEAVE proc_ins;
    END IF;

    -- PASO 3: bloquear la SECCIÓN
    SELECT s.id_curso, s.id_periodo, s.vacantes_total, s.vacantes_ocupadas, s.vacantes_extra, c.creditos
    INTO   v_id_curso, v_id_periodo_sec, v_vac_total, v_vac_ocup, v_vac_extra, v_creditos_curso
    FROM   secciones s
    JOIN   cursos c ON c.id_curso = s.id_curso
    WHERE  s.id_seccion = p_id_seccion
    FOR UPDATE;

    IF v_id_curso IS NULL THEN
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe en el sistema.';
        LEAVE proc_ins;
    END IF;

    IF v_id_periodo_sec <> v_id_periodo_mat THEN
        SET p_codigo_resp = 'SECCION_OTRO_PERIODO';
        SET p_mensaje     = 'La sección pertenece a un período académico distinto al de la matrícula.';
        LEAVE proc_ins;
    END IF;

    -- PASO 4: vacantes (tras el bloqueo pesimista)
    IF ((CAST(v_vac_total AS SIGNED) + CAST(v_vac_extra AS SIGNED)) - CAST(v_vac_ocup AS SIGNED)) <= 0 THEN
        SET p_codigo_resp = 'SIN_VACANTES';
        SET p_mensaje     = 'No quedan vacantes en la sección. Puede anotarse en la lista de espera.';
        LEAVE proc_ins;
    END IF;

    -- PASO 5: reglas de negocio
    IF EXISTS (
        SELECT 1 FROM detalle_matricula 
        WHERE id_matricula = p_id_matricula AND id_seccion = p_id_seccion AND estado = 'INSCRITO'
    ) THEN
        SET p_codigo_resp = 'YA_INSCRITO';
        SET p_mensaje     = 'El estudiante ya se encuentra registrado en esta sección.';
        LEAVE proc_ins;
    END IF;

    IF EXISTS (
        SELECT 1 FROM detalle_matricula dm
        JOIN secciones sec ON sec.id_seccion = dm.id_seccion
        WHERE dm.id_matricula = p_id_matricula 
          AND sec.id_curso = v_id_curso 
          AND dm.estado = 'INSCRITO'
    ) THEN
        SET p_codigo_resp = 'CURSO_DUPLICADO';
        SET p_mensaje     = 'El estudiante ya cuenta con otra sección inscrita para este mismo curso.';
        LEAVE proc_ins;
    END IF;

    IF fn_validar_curso_en_plan(v_id_estudiante, v_id_curso) = 0 THEN
        SET p_codigo_resp = 'CURSO_NO_EN_PLAN';
        SET p_mensaje     = 'La asignatura no pertenece al plan de estudios curricular del estudiante.';
        LEAVE proc_ins;
    END IF;

    IF fn_validar_prerrequisitos(v_id_estudiante, v_id_curso) = 0 THEN
        SET p_codigo_resp = 'FALTAN_PRERREQUISITOS';
        SET p_mensaje     = 'El estudiante no ha aprobado todos los prerrequisitos de esta asignatura en períodos cerrados.';
        LEAVE proc_ins;
    END IF;

    IF v_bypass_cruces = 0 AND fn_detectar_cruce_horario(p_id_matricula, p_id_seccion) = 1 THEN
        SET p_codigo_resp = 'CRUCE_HORARIO';
        SET p_mensaje     = 'La sección seleccionada presenta cruce de horario con otra asignatura ya inscrita.';
        LEAVE proc_ins;
    END IF;

    -- PASO 6: límite de créditos
    IF fn_validar_limite_creditos(p_id_matricula, v_creditos_curso) = 0 THEN
        SET p_codigo_resp = 'LIMITE_CREDITOS';
        SET p_mensaje     = CONCAT('Supera el límite de créditos permitidos. Curso solicitado: ',
                                   v_creditos_curso, ' créditos.');
        LEAVE proc_ins;
    END IF;

    -- PASO 7: origen según desaprobaciones previas
    SELECT COUNT(*) INTO v_veces_desaprobado
    FROM detalle_matricula dm
    JOIN secciones sec ON sec.id_seccion = dm.id_seccion
    JOIN matriculas mat ON mat.id_matricula = dm.id_matricula
    WHERE mat.id_estudiante = v_id_estudiante
      AND sec.id_curso = v_id_curso
      AND dm.nota_final IS NOT NULL
      AND dm.nota_final < fn_nota_minima();

    IF v_veces_desaprobado = 0 THEN
        SET v_origen_calc = 'PRIMERA_VEZ';
    ELSEIF v_veces_desaprobado = 1 THEN
        SET v_origen_calc = 'SEGUNDA_VEZ';
    ELSE
        SET v_origen_calc = 'TERCERA_VEZ';
    END IF;

    -- PASO 8: inscribir (reactivando la fila si había sido retirada)
    SELECT id_detalle INTO v_id_detalle_prev
    FROM detalle_matricula
    WHERE id_matricula = p_id_matricula AND id_seccion = p_id_seccion AND estado = 'RETIRADO'
    LIMIT 1;

    IF v_id_detalle_prev IS NOT NULL THEN
        UPDATE detalle_matricula
        SET estado = 'INSCRITO', origen = v_origen_calc
        WHERE id_detalle = v_id_detalle_prev;
    ELSE
        INSERT INTO detalle_matricula (id_matricula, id_seccion, estado, origen)
        VALUES (p_id_matricula, p_id_seccion, 'INSCRITO', v_origen_calc);
    END IF;

    UPDATE secciones 
    SET vacantes_ocupadas = vacantes_ocupadas + 1
    WHERE id_seccion = p_id_seccion;

    UPDATE matriculas 
    SET total_creditos = total_creditos + v_creditos_curso
    WHERE id_matricula = p_id_matricula;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Asignatura inscrita correctamente en la matrícula.';
END proc_ins$$

-- -----------------------------------------------------------------------------
-- sp_i_espera_promover
-- Mientras la sección tenga vacantes, asigna al primero de su lista de espera.
-- Si no cumple las reglas (prerrequisitos, cruce, créditos...), queda EXPIRADO y
-- se continúa con el siguiente. NOTA: bloquea la matrícula del promovido estando
-- ya bloqueada la sección (orden inverso al canónico); si hay deadlock, InnoDB
-- aborta una transacción y el backend reintenta (ver convenciones).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_i_espera_promover$$
CREATE PROCEDURE sp_i_espera_promover(IN p_id_seccion INT UNSIGNED)
BEGIN
    DECLARE v_seguir       TINYINT DEFAULT 1;
    DECLARE v_id_espera    INT UNSIGNED;
    DECLARE v_id_matricula INT UNSIGNED;
    DECLARE v_resp         VARCHAR(25);
    DECLARE v_msg          VARCHAR(255);

    WHILE v_seguir = 1 DO
        SET v_id_espera = NULL;

        IF COALESCE(fn_vacantes_disponibles(p_id_seccion), 0) <= 0 THEN
            SET v_seguir = 0;
        ELSE
            SELECT id_espera, id_matricula INTO v_id_espera, v_id_matricula
            FROM lista_espera_seccion
            WHERE id_seccion = p_id_seccion AND estado = 'EN_ESPERA'
            ORDER BY posicion ASC
            LIMIT 1
            FOR UPDATE;

            IF v_id_espera IS NULL THEN
                SET v_seguir = 0;
            ELSE
                CALL sp_i_inscribir_curso(v_id_matricula, p_id_seccion, 'INTERNO', v_resp, v_msg);

                IF v_resp = 'EXITO' THEN
                    UPDATE lista_espera_seccion
                    SET estado = 'ASIGNADO', fecha_atencion = NOW()
                    WHERE id_espera = v_id_espera;

                    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
                    VALUES (NULL, 'MATRICULA', 'ESPERA_ASIGNADA', 'lista_espera_seccion', v_id_espera,
                            CONCAT('Matrícula ', v_id_matricula, ' asignada a la sección ', p_id_seccion));
                ELSE
                    UPDATE lista_espera_seccion
                    SET estado = 'EXPIRADO', fecha_atencion = NOW()
                    WHERE id_espera = v_id_espera;

                    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
                    VALUES (NULL, 'MATRICULA', 'ESPERA_EXPIRADA', 'lista_espera_seccion', v_id_espera,
                            CONCAT('No se pudo asignar: ', v_resp));
                END IF;
            END IF;
        END IF;
    END WHILE;
END$$

-- -----------------------------------------------------------------------------
-- sp_i_retirar_curso
-- Retira el curso (UPDATE condicional atómico + ROW_COUNT), descuenta créditos,
-- libera la vacante y PROMUEVE al primero de la lista de espera de esa sección.
-- Orden de bloqueos: matriculas -> secciones.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_i_retirar_curso$$
CREATE PROCEDURE sp_i_retirar_curso(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_seccion   INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_ret: BEGIN
    DECLARE v_estado_mat VARCHAR(20);
    DECLARE v_creditos   TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_id_usuario INT UNSIGNED;
    DECLARE v_dummy_sec  INT UNSIGNED;

    SELECT m.estado, e.id_usuario
    INTO   v_estado_mat, v_id_usuario
    FROM   matriculas m
    JOIN   estudiantes e ON e.id_estudiante = m.id_estudiante
    WHERE  m.id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_estado_mat IS NULL THEN
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'La matrícula indicada no existe.';
        LEAVE proc_ret;
    END IF;

    IF v_estado_mat = 'ANULADA' THEN
        SET p_codigo_resp = 'MATRICULA_ANULADA';
        SET p_mensaje     = 'No se pueden retirar cursos de una matrícula anulada.';
        LEAVE proc_ret;
    END IF;

    SELECT s.id_seccion, c.creditos
    INTO   v_dummy_sec, v_creditos
    FROM   secciones s
    JOIN   cursos c ON c.id_curso = s.id_curso
    WHERE  s.id_seccion = p_id_seccion
    FOR UPDATE;

    IF v_dummy_sec IS NULL THEN
        SET p_codigo_resp = 'SECCION_NO_EXISTE';
        SET p_mensaje     = 'La sección indicada no existe en el sistema.';
        LEAVE proc_ret;
    END IF;

    UPDATE detalle_matricula 
    SET estado = 'RETIRADO'
    WHERE id_matricula = p_id_matricula 
      AND id_seccion = p_id_seccion 
      AND estado = 'INSCRITO';

    IF ROW_COUNT() = 0 THEN
        SET p_codigo_resp = 'NO_INSCRITO';
        SET p_mensaje     = 'El curso no se encuentra en estado INSCRITO o ya fue retirado previamente.';
        LEAVE proc_ret;
    END IF;

    UPDATE matriculas 
    SET total_creditos = GREATEST(CAST(total_creditos AS SIGNED) - CAST(v_creditos AS SIGNED), 0)
    WHERE id_matricula = p_id_matricula;

    UPDATE secciones 
    SET vacantes_ocupadas = GREATEST(CAST(vacantes_ocupadas AS SIGNED) - 1, 0)
    WHERE id_seccion = p_id_seccion;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (v_id_usuario, 'MATRICULA', 'RETIRO_CURSO', 'detalle_matricula', p_id_matricula,
            CONCAT('Retiro de sección ', p_id_seccion, ' (', v_creditos, ' créditos)'));

    -- La vacante liberada pasa al primero de la lista de espera
    CALL sp_i_espera_promover(p_id_seccion);

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Curso retirado exitosamente de la matrícula.';
END proc_ret$$

-- -----------------------------------------------------------------------------
-- sp_crear_matricula_pendiente (auxiliar de TX-01)
-- Valida período, estudiante, duplicidad, DEUDA VENCIDA y APTO MÉDICO. Para
-- saltarse deuda o ficha médica se requiere administrador Y número de resolución;
-- queda registrado en excepciones_matricula y en los flags bypass_*.
-- Créditos adicionales: fn_creditos_adicionales (regla única).
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_crear_matricula_pendiente$$
CREATE PROCEDURE sp_crear_matricula_pendiente(
    IN  p_id_estudiante  INT UNSIGNED,
    IN  p_id_periodo     SMALLINT UNSIGNED,
    IN  p_id_admin       INT UNSIGNED,
    IN  p_numero_rd      VARCHAR(80),
    OUT p_id_matricula   INT UNSIGNED,
    OUT p_nro_constancia VARCHAR(25),
    OUT p_codigo_resp    VARCHAR(25),
    OUT p_mensaje        VARCHAR(255)
)
proc_crear_mat: BEGIN
    DECLARE v_periodo_valido INT DEFAULT 0;
    DECLARE v_codigo_periodo VARCHAR(10);
    DECLARE v_estado_acad    VARCHAR(20);
    DECLARE v_cred_adic      TINYINT UNSIGNED DEFAULT 0;
    DECLARE v_constancia     VARCHAR(25);
    DECLARE v_deuda          INT DEFAULT 0;
    DECLARE v_apto           TINYINT(1) DEFAULT 0;
    DECLARE v_bypass_deuda   TINYINT(1) DEFAULT 0;
    DECLARE v_bypass_medico  TINYINT(1) DEFAULT 0;
    DECLARE v_rd             VARCHAR(80);

    SET p_id_matricula   = NULL;
    SET p_nro_constancia = NULL;
    SET v_rd = NULLIF(TRIM(p_numero_rd), '');

    SELECT COUNT(*), COALESCE(MAX(codigo_periodo), '')
    INTO v_periodo_valido, v_codigo_periodo
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo
      AND estado = 'ACTIVO'
      AND NOW() BETWEEN inicio_matricula AND fin_matricula;

    IF v_periodo_valido = 0 THEN
        SET p_codigo_resp = 'PERIODO_INACTIVO';
        SET p_mensaje     = 'El período académico no existe, no está activo o se encuentra fuera de fechas de matrícula.';
        LEAVE proc_crear_mat;
    END IF;

    SELECT estado_academico INTO v_estado_acad
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_estado_acad IS NULL THEN
        SET p_codigo_resp = 'ESTUDIANTE_NO_EXISTE';
        SET p_mensaje     = 'El estudiante indicado no existe en el sistema.';
        LEAVE proc_crear_mat;
    END IF;

    IF v_estado_acad IN ('EGRESADO', 'RETIRADO') THEN
        SET p_codigo_resp = 'ESTUDIANTE_NO_HABIL';
        SET p_mensaje     = CONCAT('El estudiante presenta estado académico "', v_estado_acad, '" y no puede matricularse.');
        LEAVE proc_crear_mat;
    END IF;

    IF EXISTS (
        SELECT 1 FROM matriculas 
        WHERE id_estudiante = p_id_estudiante AND id_periodo = p_id_periodo
    ) THEN
        SET p_codigo_resp = 'MATRICULA_DUPLICADA';
        SET p_mensaje     = 'El estudiante ya cuenta con un registro de matrícula en este período académico.';
        LEAVE proc_crear_mat;
    END IF;

    -- Deuda vencida y ficha médica
    SELECT COUNT(*) INTO v_deuda
    FROM deudas_estudiante
    WHERE id_estudiante = p_id_estudiante
      AND estado IN ('PENDIENTE', 'FRACCIONADO')
      AND fecha_vencimiento <= CURDATE();

    SELECT COALESCE(MAX(apto_medico), 0) INTO v_apto
    FROM ficha_medica
    WHERE id_estudiante = p_id_estudiante;

    IF v_deuda > 0 OR v_apto = 0 THEN
        IF p_id_admin IS NULL THEN
            IF v_deuda > 0 THEN
                SET p_codigo_resp = 'DEUDA_PENDIENTE';
                SET p_mensaje     = 'El estudiante registra deudas pendientes de pago.';
            ELSE
                SET p_codigo_resp = 'SIN_APTO_MEDICO';
                SET p_mensaje     = 'El estudiante no cuenta con la ficha médica aprobada.';
            END IF;
            LEAVE proc_crear_mat;
        END IF;

        IF v_rd IS NULL THEN
            SET p_codigo_resp = 'REQUIERE_RESOLUCION';
            SET p_mensaje     = 'Para matricular con deuda o sin apto médico debe indicar el número de resolución.';
            LEAVE proc_crear_mat;
        END IF;

        IF v_deuda > 0 THEN SET v_bypass_deuda  = 1; END IF;
        IF v_apto = 0  THEN SET v_bypass_medico = 1; END IF;
    END IF;

    SET v_cred_adic  = fn_creditos_adicionales(p_id_estudiante, p_id_periodo);
    SET v_constancia = CONCAT('MAT-', v_codigo_periodo, '-', LPAD(p_id_estudiante, 5, '0'));

    INSERT INTO matriculas (
        id_estudiante, id_periodo, tipo_matricula, estado,
        total_creditos, creditos_adicionales, id_admin,
        bypass_cruces, bypass_deuda, bypass_medico, resolucion_excep,
        fecha_matricula, nro_constancia
    ) VALUES (
        p_id_estudiante, p_id_periodo, 'REGULAR', 'PENDIENTE',
        0, v_cred_adic, p_id_admin,
        0, v_bypass_deuda, v_bypass_medico,
        IF(v_bypass_deuda = 1 OR v_bypass_medico = 1, v_rd, NULL),
        NOW(), v_constancia
    );

    SET p_id_matricula   = LAST_INSERT_ID();
    SET p_nro_constancia = v_constancia;

    IF v_bypass_deuda = 1 THEN
        INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
        VALUES (p_id_matricula, 'DEUDA_PENDIENTE', v_rd, 'Matrícula autorizada con deuda pendiente', p_id_admin);
    END IF;

    IF v_bypass_medico = 1 THEN
        INSERT INTO excepciones_matricula (id_matricula, tipo_excepcion, numero_rd, descripcion, id_admin)
        VALUES (p_id_matricula, 'REQUISITO_MEDICO', v_rd, 'Matrícula autorizada sin apto médico', p_id_admin);
    END IF;

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Matrícula aperturada en estado PENDIENTE exitosamente.';
END proc_crear_mat$$

-- -----------------------------------------------------------------------------
-- sp_confirmar_matricula (auxiliar de TX-01): PENDIENTE -> CONFIRMADA
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS sp_confirmar_matricula$$
CREATE PROCEDURE sp_confirmar_matricula(
    IN  p_id_matricula INT UNSIGNED,
    IN  p_id_admin     INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_conf_mat: BEGIN
    DECLARE v_estado_actual    VARCHAR(20);
    DECLARE v_cursos_inscritos INT DEFAULT 0;
    DECLARE v_id_usuario_est   INT UNSIGNED;
    DECLARE v_constancia       VARCHAR(25);
    DECLARE v_total_cred       TINYINT UNSIGNED;

    SELECT m.estado, m.nro_constancia, m.total_creditos, e.id_usuario
    INTO v_estado_actual, v_constancia, v_total_cred, v_id_usuario_est
    FROM matriculas m
    JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
    WHERE m.id_matricula = p_id_matricula
    FOR UPDATE;

    IF v_estado_actual IS NULL THEN
        SET p_codigo_resp = 'MATRICULA_NO_EXISTE';
        SET p_mensaje     = 'La matrícula indicada no existe.';
        LEAVE proc_conf_mat;
    END IF;

    IF v_estado_actual <> 'PENDIENTE' THEN
        SET p_codigo_resp = 'ESTADO_INVALIDO';
        SET p_mensaje     = CONCAT('La matrícula no se encuentra en estado PENDIENTE (estado actual: ', v_estado_actual, ').');
        LEAVE proc_conf_mat;
    END IF;

    SELECT COUNT(*) INTO v_cursos_inscritos
    FROM detalle_matricula
    WHERE id_matricula = p_id_matricula AND estado = 'INSCRITO';

    IF v_cursos_inscritos = 0 THEN
        SET p_codigo_resp = 'SIN_CURSOS_INSCRITOS';
        SET p_mensaje     = 'No se puede confirmar una matrícula sin al menos una asignatura en estado INSCRITO.';
        LEAVE proc_conf_mat;
    END IF;

    UPDATE matriculas SET estado = 'CONFIRMADA' WHERE id_matricula = p_id_matricula;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (COALESCE(p_id_admin, v_id_usuario_est), 'MATRICULA', 'CONFIRMAR_MATRICULA', 'matriculas',
            p_id_matricula,
            CONCAT('Matrícula confirmada. Constancia: ', v_constancia, ' | Asignaturas: ', v_cursos_inscritos, ' | Créditos: ', v_total_cred));

    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = 'Matrícula confirmada exitosamente.';
END proc_conf_mat$$

DELIMITER ;
