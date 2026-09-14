<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\CasoDeUso\Reporte\ConsultarDemandaCursos;
use App\Application\CasoDeUso\Reporte\ExportarReporte;
use App\Application\CasoDeUso\Reporte\GenerarActaCalificaciones;
use App\Application\CasoDeUso\Reporte\GenerarListaEstudiantesPorSeccion;
use App\Application\CasoDeUso\Reporte\GenerarReporteMatriculados;
use App\Application\CasoDeUso\Reporte\GenerarReportePagos;
use App\Application\CasoDeUso\Reporte\ObtenerEstadisticasAprobacion;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador del Módulo de Reportes.
 *
 * CU-49 → matriculados()
 * CU-50 → estudiantesPorSeccion(), estudiantesPorCurso()
 * CU-51 → actaCalificaciones()
 * CU-52 → demandaCursos()
 * CU-53 → reportePagos()
 * CU-54 → estadisticasAprobacion()
 * CU-55 → exportar()   (acepta ?formato=csv|html|json)
 */
class ReporteController
{
    private GenerarReporteMatriculados       $reporteMatriculados;
    private GenerarListaEstudiantesPorSeccion $listaEstudiantes;
    private GenerarActaCalificaciones        $actaCalificaciones;
    private ConsultarDemandaCursos           $demandaCursos;
    private GenerarReportePagos              $reportePagos;
    private ObtenerEstadisticasAprobacion    $estadisticas;
    private ExportarReporte                  $exportarReporte;

    public function __construct(
        GenerarReporteMatriculados       $reporteMatriculados,
        GenerarListaEstudiantesPorSeccion $listaEstudiantes,
        GenerarActaCalificaciones        $actaCalificaciones,
        ConsultarDemandaCursos           $demandaCursos,
        GenerarReportePagos              $reportePagos,
        ObtenerEstadisticasAprobacion    $estadisticas,
        ExportarReporte                  $exportarReporte
    ) {
        $this->reporteMatriculados = $reporteMatriculados;
        $this->listaEstudiantes    = $listaEstudiantes;
        $this->actaCalificaciones  = $actaCalificaciones;
        $this->demandaCursos       = $demandaCursos;
        $this->reportePagos        = $reportePagos;
        $this->estadisticas        = $estadisticas;
        $this->exportarReporte     = $exportarReporte;
    }

    // ──────────────────────────────────────────
    // CU-49: Estudiantes matriculados por periodo
    // ──────────────────────────────────────────

    /** GET /api/reportes/matriculados/{idPeriodo} */
    public function matriculados(int $idPeriodo): void
    {
        try {
            $datos   = $this->reporteMatriculados->ejecutar($idPeriodo);
            $formato = $_GET['formato'] ?? 'json';
            $this->responder($datos, $formato, "Reporte de Matriculados - Periodo {$idPeriodo}", 'matriculados');
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al generar reporte: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────
    // CU-50: Lista de estudiantes por sección / curso
    // ──────────────────────────────────────────

    /** GET /api/reportes/seccion/{idSeccion}/estudiantes */
    public function estudiantesPorSeccion(int $idSeccion): void
    {
        try {
            $datos   = $this->listaEstudiantes->porSeccion($idSeccion);
            $formato = $_GET['formato'] ?? 'json';
            $this->responder($datos, $formato, "Lista de Estudiantes - Sección {$idSeccion}", 'estudiantes');
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al generar lista: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/reportes/curso/{idCurso}/estudiantes?periodo={idPeriodo} */
    public function estudiantesPorCurso(int $idCurso): void
    {
        try {
            $idPeriodo = (int) ($_GET['periodo'] ?? 0);
            if ($idPeriodo <= 0) {
                ApiResponse::unprocessable('Se requiere el parámetro ?periodo=N');
                return;
            }
            $datos   = $this->listaEstudiantes->porCurso($idCurso, $idPeriodo);
            $formato = $_GET['formato'] ?? 'json';
            $this->responder($datos, $formato, "Estudiantes Curso {$idCurso} - Periodo {$idPeriodo}", 'estudiantes');
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al generar lista: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────
    // CU-51: Acta de calificaciones
    // ──────────────────────────────────────────

    /** GET /api/reportes/seccion/{idSeccion}/acta */
    public function actaCalificaciones(int $idSeccion): void
    {
        try {
            $datos   = $this->actaCalificaciones->ejecutar($idSeccion);
            $formato = $_GET['formato'] ?? 'json';
            $this->responder($datos, $formato, "Acta de Calificaciones - Sección {$idSeccion}", 'estudiantes');
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al generar acta: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────
    // CU-52: Demanda de cursos
    // ──────────────────────────────────────────

    /** GET /api/reportes/demanda-cursos/{idPeriodo} */
    public function demandaCursos(int $idPeriodo): void
    {
        try {
            $datos   = $this->demandaCursos->ejecutar($idPeriodo);
            $formato = $_GET['formato'] ?? 'json';
            $this->responder($datos, $formato, "Demanda de Cursos - Periodo {$idPeriodo}", 'cursos');
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al generar reporte: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────
    // CU-53: Reporte de pagos y deudas
    // ──────────────────────────────────────────

    /** GET /api/reportes/pagos/{idPeriodo} */
    public function reportePagos(int $idPeriodo): void
    {
        try {
            $datos   = $this->reportePagos->ejecutar($idPeriodo);
            $formato = $_GET['formato'] ?? 'json';
            $this->responder($datos, $formato, "Reporte de Pagos y Deudas - Periodo {$idPeriodo}", 'detalle_pagos');
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al generar reporte de pagos: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────
    // CU-54: Estadísticas de aprobación
    // ──────────────────────────────────────────

    /** GET /api/reportes/estadisticas/{idSeccion} */
    public function estadisticasAprobacion(int $idSeccion): void
    {
        try {
            $datos   = $this->estadisticas->ejecutar($idSeccion);
            $formato = $_GET['formato'] ?? 'json';
            $this->responder($datos, $formato, "Estadísticas de Aprobación - Sección {$idSeccion}", null);
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al obtener estadísticas: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────
    // CU-55: Dispatch de exportación por formato
    // ──────────────────────────────────────────

    /** GET /api/reportes/exportar/{tipo}/{formato}?id=N */
    public function exportar(string $tipo, string $formato): void
    {
        $_GET['formato'] = $formato;
        $id = (int) ($_GET['id'] ?? $_GET['periodo'] ?? 1);

        switch (strtolower($tipo)) {
            case 'matriculados':
                $this->matriculados($id);
                break;
            case 'seccion-estudiantes':
            case 'seccion':
                $this->estudiantesPorSeccion($id);
                break;
            case 'acta':
                $this->actaCalificaciones($id);
                break;
            case 'demanda-cursos':
            case 'demanda':
                $this->demandaCursos($id);
                break;
            case 'pagos':
                $this->reportePagos($id);
                break;
            case 'estadisticas':
                $this->estadisticasAprobacion($id);
                break;
            default:
                ApiResponse::unprocessable("Tipo de reporte no soportado: {$tipo}");
        }
    }

    /**
     * Envía la respuesta en el formato solicitado.
     * Si formato = json  → ApiResponse::success()
     * Si formato = csv   → Content-Disposition: attachment + CSV
     * Si formato = html  → Content-Type: text/html imprimible
     */
    private function responder(
        array   $datos,
        string  $formato,
        string  $titulo,
        ?string $mensajeJson
    ): void {
        try {
            $resultado = $this->exportarReporte->exportar($datos, $formato, $titulo);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
            return;
        }

        switch (strtolower($formato)) {
            case 'csv':
                header('Content-Type: text/csv; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . urlencode($titulo) . '.csv"');
                echo $resultado;
                break;

            case 'html':
                header('Content-Type: text/html; charset=UTF-8');
                echo $resultado;
                break;

            default: // json
                $msg = $mensajeJson
                    ? "Reporte generado correctamente."
                    : "Estadísticas obtenidas correctamente.";
                ApiResponse::success($resultado, $msg);
        }
    }
}
