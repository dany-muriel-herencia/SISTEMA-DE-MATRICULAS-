USE bd_matricula_universitaria;

-- =============================================================================
-- PROCEDIMIENTO 02: sp_guardar_estructura_academica (Autor: Dany Ronaldo Muriel Herencia)
-- Issue: ISSUE-04 (M04: Gestión Curricular y Malla) — CU-12, CU-13, CU-14, CU-15
-- Unifica el mantenimiento curricular en 1 SP transaccional según p_entidad:
--   * 'FACULTAD'    : crea/edita facultades (p_nombre, p_codigo, p_extra_texto=decano, p_num1=activo)
--   * 'ESCUELA'     : crea/edita escuelas (p_id_padre=id_facultad, p_num1=duracion, p_extra_texto=director)
--   * 'PLAN'        : crea/edita planes_estudio (p_id_padre=id_escuela, p_num1=anio, p_num2=creditos_totales, p_extra_texto=estado)
--   * 'PERIODO'     : crea/edita periodos_academicos (p_num1=anio, p_num2=semestre, fechas y p_extra_texto=estado)
--   * 'CURSO_MALLA' : crea/edita curso + ubica en malla_curricular (p_id_padre=id_plan, p_num4=ciclo) + prerrequisito
-- =============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS sp_guardar_estructura_academica$$
CREATE PROCEDURE sp_guardar_estructura_academica( 
    IN  p_entidad      VARCHAR(20),
    IN  p_id_registro  INT UNSIGNED,
    IN  p_id_padre     INT UNSIGNED,
    IN  p_codigo       VARCHAR(25),
    IN  p_nombre       VARCHAR(150),
    IN  p_extra_texto  VARCHAR(150),
    IN  p_num1         SMALLINT,
    IN  p_num2         SMALLINT,
    IN  p_num3         SMALLINT,
    IN  p_num4         SMALLINT,
    IN  p_id_curso_req SMALLINT UNSIGNED,
    IN  p_fecha_ini    DATE,
    IN  p_fecha_fin    DATE,
    IN  p_ini_mat      DATETIME,
    IN  p_fin_mat      DATETIME,
    IN  p_ini_rec      DATETIME,
    IN  p_fin_rec      DATETIME,
    IN  p_id_admin     INT UNSIGNED,
    OUT p_id_resultado INT UNSIGNED,
    OUT p_codigo_resp  VARCHAR(25),
    OUT p_mensaje      VARCHAR(255)
)
proc_acad: BEGIN
    DECLARE v_tipo         VARCHAR(20);
    DECLARE v_ciclo_req    TINYINT UNSIGNED DEFAULT NULL;
    DECLARE v_errno        INT;
    DECLARE v_msg          TEXT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_errno = MYSQL_ERRNO, v_msg = MESSAGE_TEXT;
        ROLLBACK;
        SET p_id_resultado = NULL;
        SET p_codigo_resp  = 'ERROR_SQL';
        SET p_mensaje      = CONCAT('Error SQL ', v_errno, ': ', LEFT(v_msg, 200));
    END;

    SET p_id_resultado = NULL;
    SET v_tipo = UPPER(TRIM(COALESCE(p_entidad, '')));

    IF v_tipo NOT IN ('FACULTAD', 'ESCUELA', 'PLAN', 'PERIODO', 'CURSO_MALLA') THEN
        SET p_codigo_resp = 'ENTIDAD_INVALIDA';
        SET p_mensaje     = 'p_entidad debe ser FACULTAD, ESCUELA, PLAN, PERIODO o CURSO_MALLA.';
        LEAVE proc_acad;
    END IF;

    START TRANSACTION;

    IF v_tipo = 'FACULTAD' THEN
        IF COALESCE(p_id_registro, 0) = 0 THEN
            INSERT INTO facultades (codigo_facultad, nombre, decano, activo)
            VALUES (TRIM(p_codigo), TRIM(p_nombre), NULLIF(TRIM(p_extra_texto), ''), COALESCE(p_num1, 1));
            SET p_id_resultado = LAST_INSERT_ID();
        ELSE
            UPDATE facultades
            SET codigo_facultad = COALESCE(NULLIF(TRIM(p_codigo), ''), codigo_facultad),
                nombre          = COALESCE(NULLIF(TRIM(p_nombre), ''), nombre),
                decano          = COALESCE(NULLIF(TRIM(p_extra_texto), ''), decano),
                activo          = COALESCE(p_num1, activo)
            WHERE id_facultad = p_id_registro;
            SET p_id_resultado = p_id_registro;
        END IF;

    ELSEIF v_tipo = 'ESCUELA' THEN
        IF COALESCE(p_id_registro, 0) = 0 THEN
            INSERT INTO escuelas_profesionales (id_facultad, codigo_escuela, nombre, duracion_anios, director, activo)
            VALUES (p_id_padre, TRIM(p_codigo), TRIM(p_nombre), COALESCE(p_num1, 5), NULLIF(TRIM(p_extra_texto), ''), 1);
            SET p_id_resultado = LAST_INSERT_ID();
        ELSE
            UPDATE escuelas_profesionales
            SET id_facultad    = COALESCE(NULLIF(p_id_padre, 0), id_facultad),
                codigo_escuela = COALESCE(NULLIF(TRIM(p_codigo), ''), codigo_escuela),
                nombre         = COALESCE(NULLIF(TRIM(p_nombre), ''), nombre),
                duracion_anios = COALESCE(p_num1, duracion_anios),
                director       = COALESCE(NULLIF(TRIM(p_extra_texto), ''), director)
            WHERE id_escuela = p_id_registro;
            SET p_id_resultado = p_id_registro;
        END IF;

    ELSEIF v_tipo = 'PLAN' THEN
        IF COALESCE(p_id_registro, 0) = 0 THEN
            INSERT INTO planes_estudio (id_escuela, codigo_plan, nombre_plan, anio_vigencia, creditos_totales, estado)
            VALUES (p_id_padre, TRIM(p_codigo), TRIM(p_nombre), COALESCE(p_num1, YEAR(CURDATE())), COALESCE(p_num2, 210),
                    COALESCE(NULLIF(UPPER(TRIM(p_extra_texto)), ''), 'VIGENTE'));
            SET p_id_resultado = LAST_INSERT_ID();
        ELSE
            UPDATE planes_estudio
            SET codigo_plan      = COALESCE(NULLIF(TRIM(p_codigo), ''), codigo_plan),
                nombre_plan      = COALESCE(NULLIF(TRIM(p_nombre), ''), nombre_plan),
                anio_vigencia    = COALESCE(p_num1, anio_vigencia),
                creditos_totales = COALESCE(p_num2, creditos_totales),
                estado           = COALESCE(NULLIF(UPPER(TRIM(p_extra_texto)), ''), estado)
            WHERE id_plan = p_id_registro;
            SET p_id_resultado = p_id_registro;
        END IF;

    ELSEIF v_tipo = 'PERIODO' THEN
        IF p_fecha_ini IS NOT NULL AND p_fecha_fin IS NOT NULL AND p_fecha_fin <= p_fecha_ini THEN
            ROLLBACK;
            SET p_codigo_resp = 'FECHAS_INVALIDAS';
            SET p_mensaje     = 'La fecha fin del período debe ser posterior a la fecha inicio.';
            LEAVE proc_acad;
        END IF;

        IF UPPER(TRIM(COALESCE(p_extra_texto, ''))) = 'ACTIVO' AND EXISTS (
            SELECT 1 FROM periodos_academicos WHERE estado = 'ACTIVO' AND id_periodo <> COALESCE(p_id_registro, 0)
        ) THEN
            ROLLBACK;
            SET p_codigo_resp = 'PERIODO_ACTIVO_DUPLICADO';
            SET p_mensaje     = 'Ya existe otro período académico en estado ACTIVO.';
            LEAVE proc_acad;
        END IF;

        IF COALESCE(p_id_registro, 0) = 0 THEN
            INSERT INTO periodos_academicos (
                codigo_periodo, anio, semestre, fecha_inicio, fecha_fin,
                inicio_matricula, fin_matricula, inicio_rectificacion, fin_rectificacion, estado
            ) VALUES (
                TRIM(p_codigo), COALESCE(p_num1, YEAR(CURDATE())), COALESCE(p_num2, 1),
                p_fecha_ini, p_fecha_fin, p_ini_mat, p_fin_mat, p_ini_rec, p_fin_rec,
                COALESCE(NULLIF(UPPER(TRIM(p_extra_texto)), ''), 'PROGRAMADO')
            );
            SET p_id_resultado = LAST_INSERT_ID();
        ELSE
            UPDATE periodos_academicos
            SET codigo_periodo       = COALESCE(NULLIF(TRIM(p_codigo), ''), codigo_periodo),
                fecha_inicio         = COALESCE(p_fecha_ini, fecha_inicio),
                fecha_fin            = COALESCE(p_fecha_fin, fecha_fin),
                inicio_matricula     = COALESCE(p_ini_mat, inicio_matricula),
                fin_matricula        = COALESCE(p_fin_mat, fin_matricula),
                inicio_rectificacion = COALESCE(p_ini_rec, inicio_rectificacion),
                fin_rectificacion    = COALESCE(p_fin_rec, fin_rectificacion),
                estado               = COALESCE(NULLIF(UPPER(TRIM(p_extra_texto)), ''), estado)
            WHERE id_periodo = p_id_registro;
            SET p_id_resultado = p_id_registro;
        END IF;

    ELSE -- CURSO_MALLA
        -- p_num1 = creditos, p_num2 = horas_teoria, p_num3 = horas_practica, p_num4 = ciclo, p_extra_texto = tipo
        IF COALESCE(p_id_registro, 0) = 0 THEN
            INSERT INTO cursos (codigo_curso, nombre, creditos, horas_teoria, horas_practica, tipo, activo)
            VALUES (
                TRIM(p_codigo), TRIM(p_nombre), COALESCE(p_num1, 4), COALESCE(p_num2, 2), COALESCE(p_num3, 2),
                COALESCE(NULLIF(UPPER(TRIM(p_extra_texto)), ''), 'OBLIGATORIO'), 1
            );
            SET p_id_resultado = LAST_INSERT_ID();
        ELSE
            UPDATE cursos
            SET codigo_curso   = COALESCE(NULLIF(TRIM(p_codigo), ''), codigo_curso),
                nombre         = COALESCE(NULLIF(TRIM(p_nombre), ''), nombre),
                creditos       = COALESCE(p_num1, creditos),
                horas_teoria   = COALESCE(p_num2, horas_teoria),
                horas_practica = COALESCE(p_num3, horas_practica),
                tipo           = COALESCE(NULLIF(UPPER(TRIM(p_extra_texto)), ''), tipo)
            WHERE id_curso = p_id_registro;
            SET p_id_resultado = p_id_registro;
        END IF;

        -- Asignar o actualizar ciclo dentro de la malla curricular
        IF COALESCE(p_id_padre, 0) > 0 AND COALESCE(p_num4, 0) BETWEEN 1 AND 10 THEN
            IF EXISTS (SELECT 1 FROM malla_curricular WHERE id_plan = p_id_padre AND id_curso = p_id_resultado) THEN
                UPDATE malla_curricular
                SET ciclo = p_num4
                WHERE id_plan = p_id_padre AND id_curso = p_id_resultado;
            ELSE
                INSERT INTO malla_curricular (id_plan, id_curso, ciclo)
                VALUES (p_id_padre, p_id_resultado, p_num4);
            END IF;

            -- Registrar prerrequisito validando que pertenezca a un ciclo estrictamente menor (sin ciclos)
            IF COALESCE(p_id_curso_req, 0) > 0 THEN
                IF p_id_curso_req = p_id_resultado THEN
                    ROLLBACK;
                    SET p_id_resultado = NULL;
                    SET p_codigo_resp  = 'PREREQ_CIRCULAR';
                    SET p_mensaje      = 'Un curso no puede ser prerrequisito de sí mismo.';
                    LEAVE proc_acad;
                END IF;

                SELECT ciclo INTO v_ciclo_req
                FROM malla_curricular
                WHERE id_plan = p_id_padre AND id_curso = p_id_curso_req;

                IF v_ciclo_req IS NULL OR v_ciclo_req >= p_num4 THEN
                    ROLLBACK;
                    SET p_id_resultado = NULL;
                    SET p_codigo_resp  = 'CICLO_PREREQ_INVALIDO';
                    SET p_mensaje      = 'El prerrequisito debe pertenecer al mismo plan y a un ciclo estrictamente menor.';
                    LEAVE proc_acad;
                END IF;

                INSERT IGNORE INTO prerrequisitos (id_curso, id_curso_req)
                VALUES (p_id_resultado, p_id_curso_req);
            END IF;
        END IF;
    END IF;

    INSERT INTO auditoria (id_usuario, modulo, accion, tabla_afectada, id_registro, detalle)
    VALUES (p_id_admin, 'ACADEMICO', CONCAT('GUARDAR_', v_tipo), v_tipo, p_id_resultado,
            CONCAT(COALESCE(p_codigo, ''), ' | ', COALESCE(p_nombre, '')));

    COMMIT;
    SET p_codigo_resp = 'EXITO';
    SET p_mensaje     = CONCAT('Registro de ', v_tipo, ' guardado exitosamente.');
END proc_acad$$

DELIMITER ;

-- Prueba rápida:
-- CALL sp_guardar_estructura_academica('FACULTAD', 1, NULL, 'FAIN', 'Facultad de Ingeniería', 'Dr. Edwin Huarachi Huayta', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, @id_res, @cod, @msg);
-- SELECT @id_res, @cod, @msg;
