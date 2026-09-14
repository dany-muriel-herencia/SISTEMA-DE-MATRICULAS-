<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Reporte;

use DomainException;

/**
 * CU-49: Generar reportes de estudiantes matriculados.
 * Actor: Administrador. Secundario: Sistema.
 */
class GenerarReporteMatriculados
{
    private ReporteConsultaServicio $reporteServicio;

    public function __construct(ReporteConsultaServicio $reporteServicio)
    {
        $this->reporteServicio = $reporteServicio;
    }

    public function ejecutar(int $idPeriodo): array
    {
        if ($idPeriodo <= 0) {
            throw new DomainException('El ID del periodo debe ser mayor que cero.');
        }

        $filas = $this->reporteServicio->estudiantesMatriculadosPorPeriodo($idPeriodo);

        return [
            'id_periodo'          => $idPeriodo,
            'total_matriculados'  => count($filas),
            'estudiantes'         => $filas,
            'generado_en'         => date('Y-m-d H:i:s'),
        ];
    }
}
