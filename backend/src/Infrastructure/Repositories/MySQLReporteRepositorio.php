<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Application\CasoDeUso\Reporte\ReporteConsultaServicio;

/**
 * Implementación MySQL del servicio de consultas para reportes.
 *
 * Ejecuta JOINs y agregaciones SQL que no caben en los repositorios
 * de entidad individuales. Retorna arrays crudos (sin entidades de dominio)
 * para ser usados directamente por los casos de uso de reportes.
 */
final class MySQLReporteRepositorio
    extends MySQLRepositorioBase
    implements ReporteConsultaServicio
{
    // ─────────────────────────────────────────
    // CU-49: Estudiantes matriculados por periodo
    // ─────────────────────────────────────────

    public function estudiantesMatriculadosPorPeriodo(int $idPeriodo): array
    {
        $sql = "
            SELECT
                m.id_matricula,
                m.codigo_matricula,
                m.fecha_matricula,
                m.estado             AS estado_matricula,
                m.total_creditos,
                u.id_usuario,
                u.nombre,
                u.apellido,
                u.email,
                e.codigo_estudiante,
                e.estado_academico
            FROM matricula m
            INNER JOIN usuario   u ON u.id_usuario = m.id_estudiante
            INNER JOIN estudiante e ON e.id_usuario = m.id_estudiante
            WHERE m.id_periodo = :id_periodo
            ORDER BY u.apellido ASC, u.nombre ASC
        ";

        return $this->all($sql, [':id_periodo' => $idPeriodo]);
    }

    // ─────────────────────────────────────────
    // CU-50: Estudiantes por sección
    // ─────────────────────────────────────────

    public function estudiantesPorSeccion(int $idSeccion): array
    {
        $sql = "
            SELECT
                s.id_seccion,
                s.codigo            AS codigo_seccion,
                c.nombre            AS nombre_curso,
                c.codigo            AS codigo_curso,
                c.creditos,
                ud.nombre           AS nombre_docente,
                ud.apellido         AS apellido_docente,
                u.id_usuario,
                u.nombre,
                u.apellido,
                u.email,
                e.codigo_estudiante,
                dm.estado           AS estado_inscripcion
            FROM detalle_matricula dm
            INNER JOIN matricula   m  ON m.id_matricula  = dm.id_matricula
            INNER JOIN seccion     s  ON s.id_seccion    = dm.id_seccion
            INNER JOIN curso       c  ON c.id_curso      = s.id_curso
            INNER JOIN usuario     u  ON u.id_usuario    = m.id_estudiante
            INNER JOIN estudiante  e  ON e.id_usuario    = m.id_estudiante
            INNER JOIN usuario     ud ON ud.id_usuario   = s.id_docente
            WHERE dm.id_seccion = :id_seccion
            ORDER BY u.apellido ASC, u.nombre ASC
        ";

        return $this->all($sql, [':id_seccion' => $idSeccion]);
    }

    public function estudiantesPorCurso(int $idCurso, int $idPeriodo): array
    {
        $sql = "
            SELECT
                s.codigo            AS codigo_seccion,
                c.nombre            AS nombre_curso,
                c.codigo            AS codigo_curso,
                u.id_usuario,
                u.nombre,
                u.apellido,
                u.email,
                e.codigo_estudiante,
                dm.estado           AS estado_inscripcion
            FROM detalle_matricula dm
            INNER JOIN matricula  m ON m.id_matricula = dm.id_matricula
            INNER JOIN seccion    s ON s.id_seccion   = dm.id_seccion
            INNER JOIN curso      c ON c.id_curso     = s.id_curso
            INNER JOIN usuario    u ON u.id_usuario   = m.id_estudiante
            INNER JOIN estudiante e ON e.id_usuario   = m.id_estudiante
            WHERE s.id_curso = :id_curso
              AND s.id_periodo = :id_periodo
            ORDER BY s.codigo ASC, u.apellido ASC, u.nombre ASC
        ";

        return $this->all($sql, [
            ':id_curso'   => $idCurso,
            ':id_periodo' => $idPeriodo,
        ]);
    }

    // ─────────────────────────────────────────
    // CU-51: Acta de calificaciones por sección
    // ─────────────────────────────────────────

    public function actaCalificacionesPorSeccion(int $idSeccion): array
    {
        // La tabla detalle_matricula guarda el estado (MATRICULADO / APROBADO / DESAPROBADO)
        // Se usa como proxy de calificación hasta que exista tabla de notas.
        $sql = "
            SELECT
                s.codigo            AS codigo_seccion,
                c.nombre            AS nombre_curso,
                c.codigo            AS codigo_curso,
                c.creditos,
                ud.nombre           AS nombre_docente,
                ud.apellido         AS apellido_docente,
                m.codigo_matricula,
                u.nombre,
                u.apellido,
                e.codigo_estudiante,
                dm.estado           AS resultado
            FROM detalle_matricula dm
            INNER JOIN matricula   m  ON m.id_matricula = dm.id_matricula
            INNER JOIN seccion     s  ON s.id_seccion   = dm.id_seccion
            INNER JOIN curso       c  ON c.id_curso     = s.id_curso
            INNER JOIN usuario     u  ON u.id_usuario   = m.id_estudiante
            INNER JOIN estudiante  e  ON e.id_usuario   = m.id_estudiante
            INNER JOIN usuario     ud ON ud.id_usuario  = s.id_docente
            WHERE dm.id_seccion = :id_seccion
            ORDER BY u.apellido ASC, u.nombre ASC
        ";

        return $this->all($sql, [':id_seccion' => $idSeccion]);
    }

    // ─────────────────────────────────────────
    // CU-52: Demanda de cursos por periodo
    // ─────────────────────────────────────────

    public function demandaCursos(int $idPeriodo): array
    {
        $sql = "
            SELECT
                c.id_curso,
                c.codigo            AS codigo_curso,
                c.nombre            AS nombre_curso,
                c.creditos,
                COUNT(dm.id_detalle) AS total_matriculados,
                SUM(s.vacantes)      AS total_vacantes,
                ROUND(
                    (COUNT(dm.id_detalle) / NULLIF(SUM(s.vacantes), 0)) * 100, 1
                )                    AS porcentaje_ocupacion
            FROM curso c
            INNER JOIN seccion         s  ON s.id_curso    = c.id_curso
                                         AND s.id_periodo  = :id_periodo
            LEFT  JOIN detalle_matricula dm ON dm.id_seccion = s.id_seccion
            GROUP BY c.id_curso, c.codigo, c.nombre, c.creditos
            ORDER BY total_matriculados DESC
        ";

        return $this->all($sql, [':id_periodo' => $idPeriodo]);
    }

    // ─────────────────────────────────────────
    // CU-53: Reporte de pagos y deudas por periodo
    // ─────────────────────────────────────────

    public function reportePagosYDeudas(int $idPeriodo): array
    {
        // Pagos realizados en el periodo (vía fecha de matrícula como referencia)
        $pagosSQL = "
            SELECT
                u.id_usuario,
                u.nombre,
                u.apellido,
                e.codigo_estudiante,
                cp.nombre           AS concepto,
                p.monto,
                p.metodo_pago,
                p.fecha_pago,
                CASE WHEN cmp.id_comprobante IS NOT NULL THEN 'PAGADO' ELSE 'PENDIENTE' END AS estado,
                cmp.tipo            AS tipo_comprobante,
                cmp.numero          AS numero_comprobante
            FROM pago p
            INNER JOIN usuario             u   ON u.id_usuario       = p.id_estudiante
            INNER JOIN estudiante          e   ON e.id_usuario       = p.id_estudiante
            INNER JOIN concepto_pago       cp  ON cp.id_concepto     = p.id_concepto
            LEFT  JOIN comprobante_pago    cmp ON cmp.id_pago        = p.id_pago
            WHERE EXISTS (
                SELECT 1 FROM matricula m
                WHERE m.id_estudiante = p.id_estudiante
                  AND m.id_periodo    = :id_periodo
            )
            ORDER BY u.apellido ASC, p.fecha_pago DESC
        ";

        $pagos = $this->all($pagosSQL, [':id_periodo' => $idPeriodo]);

        // Resumen por estudiante
        $resumenSQL = "
            SELECT
                u.id_usuario,
                u.nombre,
                u.apellido,
                e.codigo_estudiante,
                SUM(p.monto)                                         AS total_pagado,
                SUM(CASE WHEN cmp.id_comprobante IS NULL THEN p.monto ELSE 0 END) AS total_pendiente,
                COUNT(p.id_pago)                                     AS num_pagos
            FROM pago p
            INNER JOIN usuario          u   ON u.id_usuario   = p.id_estudiante
            INNER JOIN estudiante       e   ON e.id_usuario   = p.id_estudiante
            LEFT  JOIN comprobante_pago cmp ON cmp.id_pago    = p.id_pago
            WHERE EXISTS (
                SELECT 1 FROM matricula m
                WHERE m.id_estudiante = p.id_estudiante
                  AND m.id_periodo    = :id_periodo
            )
            GROUP BY u.id_usuario, u.nombre, u.apellido, e.codigo_estudiante
            ORDER BY u.apellido ASC
        ";

        $resumen = $this->all($resumenSQL, [':id_periodo' => $idPeriodo]);

        return [
            'detalle' => $pagos,
            'resumen' => $resumen,
        ];
    }

    // ─────────────────────────────────────────
    // CU-54: Estadísticas de aprobación por sección
    // ─────────────────────────────────────────

    public function estadisticasAprobacion(int $idSeccion): array
    {
        $sql = "
            SELECT
                s.codigo                    AS codigo_seccion,
                c.nombre                    AS nombre_curso,
                ud.nombre                   AS nombre_docente,
                ud.apellido                 AS apellido_docente,
                COUNT(dm.id_detalle)        AS total_estudiantes,
                SUM(CASE WHEN dm.estado = 'APROBADO'    THEN 1 ELSE 0 END) AS aprobados,
                SUM(CASE WHEN dm.estado = 'DESAPROBADO' THEN 1 ELSE 0 END) AS desaprobados,
                SUM(CASE WHEN dm.estado = 'MATRICULADO' THEN 1 ELSE 0 END) AS en_curso,
                ROUND(
                    (SUM(CASE WHEN dm.estado = 'APROBADO' THEN 1 ELSE 0 END)
                        / NULLIF(COUNT(dm.id_detalle), 0)) * 100, 1
                )                           AS porcentaje_aprobacion
            FROM seccion s
            INNER JOIN detalle_matricula dm ON dm.id_seccion = s.id_seccion
            INNER JOIN curso             c  ON c.id_curso    = s.id_curso
            INNER JOIN usuario           ud ON ud.id_usuario = s.id_docente
            WHERE s.id_seccion = :id_seccion
            GROUP BY s.id_seccion, s.codigo, c.nombre, ud.nombre, ud.apellido
        ";

        $fila = $this->one($sql, [':id_seccion' => $idSeccion]);

        return $fila ?? [];
    }
}
