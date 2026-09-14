<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Reporte;

use DomainException;

/**
 * CU-50: Generar listas de estudiantes por curso y sección.
 * Actor: Administrador, Docente. Secundario: Sistema.
 */
class GenerarListaEstudiantesPorSeccion
{
    private ReporteConsultaServicio $reporteServicio;

    public function __construct(ReporteConsultaServicio $reporteServicio)
    {
        $this->reporteServicio = $reporteServicio;
    }

    /** Lista estudiantes de una sección específica. */
    public function porSeccion(int $idSeccion): array
    {
        if ($idSeccion <= 0) {
            throw new DomainException('El ID de la sección debe ser mayor que cero.');
        }

        $filas = $this->reporteServicio->estudiantesPorSeccion($idSeccion);

        return [
            'id_seccion'         => $idSeccion,
            'total_estudiantes'  => count($filas),
            'estudiantes'        => $filas,
            'generado_en'        => date('Y-m-d H:i:s'),
        ];
    }

    /** Lista estudiantes de todas las secciones de un curso en un periodo. */
    public function porCurso(int $idCurso, int $idPeriodo): array
    {
        if ($idCurso <= 0) {
            throw new DomainException('El ID del curso debe ser mayor que cero.');
        }
        if ($idPeriodo <= 0) {
            throw new DomainException('El ID del periodo debe ser mayor que cero.');
        }

        $filas = $this->reporteServicio->estudiantesPorCurso($idCurso, $idPeriodo);

        return [
            'id_curso'          => $idCurso,
            'id_periodo'        => $idPeriodo,
            'total_estudiantes' => count($filas),
            'estudiantes'       => $filas,
            'generado_en'       => date('Y-m-d H:i:s'),
        ];
    }
}
