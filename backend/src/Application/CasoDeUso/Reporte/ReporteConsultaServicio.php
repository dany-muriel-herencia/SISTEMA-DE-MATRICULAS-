<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Reporte;

/**
 * Interfaz de consulta para reportes del sistema.
 *
 * Vive en la capa Application (no Domain) porque es un contrato
 * de lectura/query que no pertenece al modelo de dominio sino al
 * módulo de reportes transversales.
 *
 * La implementación concreta (MySQLReporteRepositorio) vive en
 * Infrastructure y ejecuta JOINs y agregaciones SQL.
 */
interface ReporteConsultaServicio
{
    /**
     * CU-49: Estudiantes matriculados en un periodo.
     * @return array[]
     */
    public function estudiantesMatriculadosPorPeriodo(int $idPeriodo): array;

    /**
     * CU-50: Lista de estudiantes inscritos en una sección.
     * @return array[]
     */
    public function estudiantesPorSeccion(int $idSeccion): array;

    /**
     * CU-50: Lista de estudiantes inscritos en todas las secciones de un curso.
     * @return array[]
     */
    public function estudiantesPorCurso(int $idCurso, int $idPeriodo): array;

    /**
     * CU-51: Acta de calificaciones de una sección (estudiantes + nota si existe).
     * @return array[]
     */
    public function actaCalificacionesPorSeccion(int $idSeccion): array;

    /**
     * CU-52: Cursos ordenados por demanda (matriculados) en un periodo.
     * @return array[]
     */
    public function demandaCursos(int $idPeriodo): array;

    /**
     * CU-53: Reporte de pagos realizados y deudas pendientes en un periodo.
     * @return array
     */
    public function reportePagosYDeudas(int $idPeriodo): array;

    /**
     * CU-54: Estadísticas de aprobados y desaprobados por sección.
     * @return array
     */
    public function estadisticasAprobacion(int $idSeccion): array;
}
