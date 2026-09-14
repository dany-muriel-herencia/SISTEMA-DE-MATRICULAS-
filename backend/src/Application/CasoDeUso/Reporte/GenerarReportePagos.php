<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Reporte;

use DomainException;

/**
 * CU-53: Generar reportes de pagos y deudas.
 * Actor: Tesorería, Administrador. Secundario: Sistema.
 */
class GenerarReportePagos
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

        $datos = $this->reporteServicio->reportePagosYDeudas($idPeriodo);

        // Calcular totales globales
        $totalPagado    = 0.0;
        $totalPendiente = 0.0;

        foreach ($datos['resumen'] as $fila) {
            $totalPagado    += (float) ($fila['total_pagado']     ?? 0);
            $totalPendiente += (float) ($fila['total_pendiente']  ?? 0);
        }

        return [
            'id_periodo'         => $idPeriodo,
            'total_pagado'       => round($totalPagado, 2),
            'total_pendiente'    => round($totalPendiente, 2),
            'total_estudiantes'  => count($datos['resumen']),
            'resumen_estudiantes'=> $datos['resumen'],
            'detalle_pagos'      => $datos['detalle'],
            'generado_en'        => date('Y-m-d H:i:s'),
        ];
    }
}
