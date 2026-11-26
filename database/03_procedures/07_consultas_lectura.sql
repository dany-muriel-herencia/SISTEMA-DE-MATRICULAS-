USE bd_matricula_universitaria;

DELIMITER $$

-- =============================================================================
-- PARTE 10: PROCEDIMIENTOS DE CONSULTA (solo lectura)
-- =============================================================================

DROP PROCEDURE IF EXISTS sp_consultar_matricula$$
CREATE PROCEDURE sp_consultar_matricula(
    IN p_id_estudiante INT UNSIGNED,
    IN p_id_periodo    SMALLINT UNSIGNED
)
BEGIN
    SELECT 
        m.id_matricula, m.nro_constancia, m.estado AS estado_matricula,
        m.total_creditos, m.creditos_adicionales, m.tipo_matricula, m.fecha_matricula,
        p.codigo_periodo,
        CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
        e.codigo_universitario, e.estado_academico, e.promedio_semestre_anterior
    FROM matriculas m
    JOIN periodos_academicos p ON p.id_periodo = m.id_periodo
    JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
    JOIN usuarios u ON u.id_usuario = e.id_usuario
    WHERE m.id_estudiante = p_id_estudiante
      AND m.id_periodo = p_id_periodo;

    SELECT 
        dm.id_detalle, s.id_seccion, c.codigo_curso, c.nombre AS nombre_curso, c.creditos,
        s.codigo_seccion, s.turno, a.codigo_aula,
        dm.estado AS estado_curso, dm.origen, dm.nota_final
    FROM matriculas m
    JOIN detalle_matricula dm ON dm.id_matricula = m.id_matricula
    JOIN secciones s ON s.id_seccion = dm.id_seccion
    JOIN cursos c ON c.id_curso = s.id_curso
    JOIN aulas a ON a.id_aula = s.id_aula
    WHERE m.id_estudiante = p_id_estudiante
      AND m.id_periodo = p_id_periodo
    ORDER BY c.codigo_curso;
END$$

DROP PROCEDURE IF EXISTS sp_consultar_vacantes$$
CREATE PROCEDURE sp_consultar_vacantes(
    IN p_id_periodo SMALLINT UNSIGNED
)
BEGIN
    SELECT 
        s.id_seccion, p.codigo_periodo, c.codigo_curso, c.nombre AS curso, c.creditos,
        s.codigo_seccion, s.turno, a.codigo_aula, a.nombre AS aula,
        CONCAT(ud.apellidos, ', ', ud.nombres) AS docente,
        s.vacantes_total, s.vacantes_extra, s.vacantes_ocupadas,
        fn_vacantes_disponibles(s.id_seccion) AS vacantes_libres,
        CASE 
            WHEN fn_vacantes_disponibles(s.id_seccion) IS NULL THEN 'NO_EXISTE'
            WHEN fn_vacantes_disponibles(s.id_seccion) = 0 THEN 'LLENA'
            ELSE 'DISPONIBLE'
        END AS disponibilidad,
        (SELECT COUNT(*) FROM lista_espera_seccion le
          WHERE le.id_seccion = s.id_seccion AND le.estado = 'EN_ESPERA') AS personas_en_espera
    FROM secciones s
    JOIN periodos_academicos p ON p.id_periodo = s.id_periodo
    JOIN cursos c ON c.id_curso = s.id_curso
    JOIN aulas a ON a.id_aula = s.id_aula
    JOIN usuarios ud ON ud.id_usuario = s.id_docente
    WHERE s.id_periodo = p_id_periodo
    ORDER BY c.codigo_curso, s.codigo_seccion;
END$$

DELIMITER ;

-- (Opcional) Evento que libera turnos vencidos cada minuto.
-- Requiere: SET GLOBAL event_scheduler = ON;
-- CREATE EVENT IF NOT EXISTS ev_expirar_turnos
-- ON SCHEDULE EVERY 1 MINUTE
-- DO CALL sp_expirar_turnos_cola(2, 15, @total);
