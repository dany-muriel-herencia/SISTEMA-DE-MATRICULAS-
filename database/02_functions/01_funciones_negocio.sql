USE bd_matricula_universitaria;

-- =============================================================================
-- PARTE 2: FUNCIONES ALMACENADAS
-- =============================================================================

DELIMITER $$

-- ---- Parámetros del reglamento en un solo lugar -----------------------------
DROP FUNCTION IF EXISTS fn_nota_minima$$
CREATE FUNCTION fn_nota_minima() RETURNS DECIMAL(4,2)
DETERMINISTIC NO SQL
RETURN 10.50$$

DROP FUNCTION IF EXISTS fn_limite_creditos_base$$
CREATE FUNCTION fn_limite_creditos_base() RETURNS TINYINT UNSIGNED
DETERMINISTIC NO SQL
RETURN 22$$

DROP FUNCTION IF EXISTS fn_promedio_merito$$
CREATE FUNCTION fn_promedio_merito() RETURNS DECIMAL(4,2)
DETERMINISTIC NO SQL
RETURN 14.00$$

-- ---- 1. fn_detectar_cruce_horario -------------------------------------------
-- Intervalo [A1,A2] solapa con [B1,B2] <=> A1 < B2 Y A2 > B1 en el mismo día.
-- Solo compara contra cursos INSCRITOS de la matrícula. Retorna 1 si hay cruce.
DROP FUNCTION IF EXISTS fn_detectar_cruce_horario$$
CREATE FUNCTION fn_detectar_cruce_horario(
    p_id_matricula INT UNSIGNED,
    p_id_seccion   INT UNSIGNED
) 
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_cruce INT DEFAULT 0;

    IF NOT EXISTS (SELECT 1 FROM matriculas WHERE id_matricula = p_id_matricula) THEN
        RETURN 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM secciones WHERE id_seccion = p_id_seccion) THEN
        RETURN 0;
    END IF;

    SELECT COUNT(*) INTO v_cruce
    FROM horarios_seccion h_nueva
    JOIN horarios_seccion h_existente 
        ON h_nueva.dia_semana = h_existente.dia_semana
       AND h_nueva.hora_inicio < h_existente.hora_fin
       AND h_nueva.hora_fin > h_existente.hora_inicio
    JOIN detalle_matricula dm 
        ON dm.id_seccion = h_existente.id_seccion
    WHERE h_nueva.id_seccion = p_id_seccion
      AND dm.id_matricula = p_id_matricula
      AND dm.estado = 'INSCRITO'
      AND h_existente.id_seccion != p_id_seccion;

    RETURN IF(v_cruce > 0, 1, 0);
END$$

-- ---- 2. fn_validar_prerrequisitos -------------------------------------------
-- Cumple un prerrequisito si el estudiante lo aprobó (nota >= mínima) en un
-- período CERRADO, o si el curso fue CONVALIDADO. Excluye matrículas anuladas
-- y retiros. Retorna 1 si cumple todos (o el curso no tiene prerrequisitos).
DROP FUNCTION IF EXISTS fn_validar_prerrequisitos$$
CREATE FUNCTION fn_validar_prerrequisitos(
    p_id_estudiante INT UNSIGNED,
    p_id_curso      SMALLINT UNSIGNED
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_prereq     INT DEFAULT 0;
    DECLARE v_prereq_cumplidos INT DEFAULT 0;

    IF NOT EXISTS (SELECT 1 FROM estudiantes WHERE id_estudiante = p_id_estudiante) THEN
        RETURN 0;
    END IF;

    SELECT COUNT(*) INTO v_total_prereq
    FROM prerrequisitos
    WHERE id_curso = p_id_curso;

    IF v_total_prereq = 0 THEN
        RETURN 1;
    END IF;

    SELECT COUNT(DISTINCT pr.id_curso_req) INTO v_prereq_cumplidos
    FROM prerrequisitos pr
    JOIN secciones s ON s.id_curso = pr.id_curso_req
    JOIN detalle_matricula dm ON dm.id_seccion = s.id_seccion
    JOIN matriculas m ON m.id_matricula = dm.id_matricula
    JOIN periodos_academicos pa ON pa.id_periodo = m.id_periodo
    WHERE pr.id_curso = p_id_curso
      AND m.id_estudiante = p_id_estudiante
      AND m.estado != 'ANULADA'
      AND (
            (dm.estado = 'INSCRITO'
             AND dm.nota_final IS NOT NULL
             AND dm.nota_final >= fn_nota_minima()
             AND pa.estado = 'CERRADO')
            OR dm.estado = 'CONVALIDADO'
          );

    RETURN IF(v_prereq_cumplidos >= v_total_prereq, 1, 0);
END$$

-- ---- 3. fn_vacantes_disponibles ---------------------------------------------
-- Retorna INT (NULL si la sección no existe; >= 0 si existe).
DROP FUNCTION IF EXISTS fn_vacantes_disponibles$$
CREATE FUNCTION fn_vacantes_disponibles(
    p_id_seccion INT UNSIGNED
)
RETURNS INT
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_disponibles INT DEFAULT NULL;

    SELECT (CAST(vacantes_total AS SIGNED) + CAST(vacantes_extra AS SIGNED)) - CAST(vacantes_ocupadas AS SIGNED)
    INTO v_disponibles
    FROM secciones
    WHERE id_seccion = p_id_seccion;

    IF v_disponibles IS NULL THEN
        RETURN NULL;
    END IF;

    RETURN GREATEST(v_disponibles, 0);
END$$

-- ---- 4. fn_calcular_creditos_matricula --------------------------------------
-- Suma real de créditos de los cursos INSCRITOS (sirve para auditar total_creditos).
DROP FUNCTION IF EXISTS fn_calcular_creditos_matricula$$
CREATE FUNCTION fn_calcular_creditos_matricula(
    p_id_matricula INT UNSIGNED
)
RETURNS SMALLINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_creditos SMALLINT UNSIGNED DEFAULT 0;

    SELECT COALESCE(SUM(c.creditos), 0)
    INTO v_total_creditos
    FROM detalle_matricula dm
    JOIN secciones s ON s.id_seccion = dm.id_seccion
    JOIN cursos c ON c.id_curso = s.id_curso
    WHERE dm.id_matricula = p_id_matricula
      AND dm.estado = 'INSCRITO';

    RETURN v_total_creditos;
END$$

-- ---- 5. fn_validar_limite_creditos ------------------------------------------
-- 1 si total_creditos + nuevos <= límite base + creditos_adicionales; 0 si excede
-- o si la matrícula no existe.
DROP FUNCTION IF EXISTS fn_validar_limite_creditos$$
CREATE FUNCTION fn_validar_limite_creditos(
    p_id_matricula    INT UNSIGNED,
    p_creditos_nuevos TINYINT UNSIGNED
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_total_creditos       TINYINT UNSIGNED DEFAULT NULL;
    DECLARE v_creditos_adicionales TINYINT UNSIGNED DEFAULT 0;

    SELECT total_creditos, creditos_adicionales
    INTO v_total_creditos, v_creditos_adicionales
    FROM matriculas
    WHERE id_matricula = p_id_matricula;

    IF v_total_creditos IS NULL THEN
        RETURN 0;
    END IF;

    IF (CAST(v_total_creditos AS UNSIGNED) + CAST(p_creditos_nuevos AS UNSIGNED))
       <= (CAST(fn_limite_creditos_base() AS UNSIGNED) + CAST(v_creditos_adicionales AS UNSIGNED)) THEN
        RETURN 1;
    END IF;

    RETURN 0;
END$$

-- ---- 6. fn_validar_curso_en_plan --------------------------------------------
DROP FUNCTION IF EXISTS fn_validar_curso_en_plan$$
CREATE FUNCTION fn_validar_curso_en_plan(
    p_id_estudiante INT UNSIGNED,
    p_id_curso      SMALLINT UNSIGNED
)
RETURNS TINYINT(1)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_id_plan   SMALLINT UNSIGNED DEFAULT NULL;
    DECLARE v_pertenece INT DEFAULT 0;

    SELECT id_plan INTO v_id_plan
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_id_plan IS NULL THEN
        RETURN 0;
    END IF;

    SELECT COUNT(*) INTO v_pertenece
    FROM malla_curricular
    WHERE id_plan = v_id_plan
      AND id_curso = p_id_curso;

    RETURN IF(v_pertenece > 0, 1, 0);
END$$

-- ---- 7. fn_calcular_saldo_deuda ---------------------------------------------
DROP FUNCTION IF EXISTS fn_calcular_saldo_deuda$$
CREATE FUNCTION fn_calcular_saldo_deuda(
    p_id_deuda INT UNSIGNED
)
RETURNS DECIMAL(8,2)
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_monto_deuda DECIMAL(8,2) DEFAULT NULL;
    DECLARE v_total_pagos DECIMAL(8,2) DEFAULT 0.00;

    SELECT monto INTO v_monto_deuda
    FROM deudas_estudiante
    WHERE id_deuda = p_id_deuda;

    IF v_monto_deuda IS NULL THEN
        RETURN NULL;
    END IF;

    SELECT COALESCE(SUM(monto_pagado), 0.00) INTO v_total_pagos
    FROM pagos
    WHERE id_deuda = p_id_deuda;

    RETURN GREATEST(v_monto_deuda - v_total_pagos, 0.00);
END$$

-- ---- 8. fn_creditos_adicionales (NUEVA: regla única de créditos extra) ------
-- +4 créditos si el promedio del semestre anterior es >= fn_promedio_merito(),
-- o si el estudiante tiene una resolución AMPLIACION_CREDITOS vigente: con
-- fecha_efecto ya cumplida y dentro del MISMO AÑO del período. (La tabla
-- resoluciones_estudiante no guarda cantidad de créditos; la ampliación es fija.)
DROP FUNCTION IF EXISTS fn_creditos_adicionales$$
CREATE FUNCTION fn_creditos_adicionales(
    p_id_estudiante INT UNSIGNED,
    p_id_periodo    SMALLINT UNSIGNED
)
RETURNS TINYINT UNSIGNED
NOT DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_promedio DECIMAL(4,2) DEFAULT NULL;
    DECLARE v_anio     SMALLINT DEFAULT NULL;

    SELECT promedio_semestre_anterior INTO v_promedio
    FROM estudiantes
    WHERE id_estudiante = p_id_estudiante;

    IF v_promedio IS NOT NULL AND v_promedio >= fn_promedio_merito() THEN
        RETURN 4;
    END IF;

    SELECT anio INTO v_anio
    FROM periodos_academicos
    WHERE id_periodo = p_id_periodo;

    IF v_anio IS NOT NULL AND EXISTS (
        SELECT 1 FROM resoluciones_estudiante
        WHERE id_estudiante = p_id_estudiante
          AND tipo_resolucion = 'AMPLIACION_CREDITOS'
          AND fecha_efecto <= CURDATE()
          AND YEAR(fecha_efecto) = v_anio
    ) THEN
        RETURN 4;
    END IF;

    RETURN 0;
END$$

DELIMITER ;
