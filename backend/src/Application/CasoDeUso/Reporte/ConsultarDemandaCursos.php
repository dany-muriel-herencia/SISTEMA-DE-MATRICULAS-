<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Reporte;

use DomainException;

/**
 * CU-52: Consultar cursos con mayor y menor demanda.
 * Actor: Administrador. Secundario: Sistema.
 */
class ConsultarDemandaCursos
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

        $cursos = $this->reporteServicio->demandaCursos($idPeriodo);

        if (empty($cursos)) {
            return [
                'id_periodo'    => $idPeriodo,
                'cursos'        => [],
                'mayor_demanda' => null,
                'menor_demanda' => null,
                'generado_en'   => date('Y-m-d H:i:s'),
            ];
        }

        // Ya vienen ordenados DESC por total_matriculados
        return [
            'id_periodo'    => $idPeriodo,
            'total_cursos'  => count($cursos),
            'cursos'        => $cursos,
            'mayor_demanda' => $cursos[0],
            'menor_demanda' => $cursos[count($cursos) - 1],
            'generado_en'   => date('Y-m-d H:i:s'),
        ];
    }
}
