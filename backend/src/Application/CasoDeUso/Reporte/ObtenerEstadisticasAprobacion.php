<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Reporte;

use DomainException;

/**
 * CU-54: Obtener estadísticas de aprobados y desaprobados.
 * Actor: Administrador. Secundario: Sistema.
 */
class ObtenerEstadisticasAprobacion
{
    private ReporteConsultaServicio $reporteServicio;

    public function __construct(ReporteConsultaServicio $reporteServicio)
    {
        $this->reporteServicio = $reporteServicio;
    }

    public function ejecutar(int $idSeccion): array
    {
        if ($idSeccion <= 0) {
            throw new DomainException('El ID de la sección debe ser mayor que cero.');
        }

        $stats = $this->reporteServicio->estadisticasAprobacion($idSeccion);

        if (empty($stats)) {
            throw new DomainException(
                "No se encontraron datos para la sección con ID {$idSeccion}."
            );
        }

        $aprobados    = (int) ($stats['aprobados']    ?? 0);
        $desaprobados = (int) ($stats['desaprobados'] ?? 0);
        $enCurso      = (int) ($stats['en_curso']     ?? 0);
        $total        = (int) ($stats['total_estudiantes'] ?? 0);

        return [
            'id_seccion'          => $idSeccion,
            'codigo_seccion'      => $stats['codigo_seccion']        ?? '',
            'nombre_curso'        => $stats['nombre_curso']          ?? '',
            'docente'             => trim(($stats['nombre_docente'] ?? '') . ' ' . ($stats['apellido_docente'] ?? '')),
            'total_estudiantes'   => $total,
            'aprobados'           => $aprobados,
            'desaprobados'        => $desaprobados,
            'en_curso'            => $enCurso,
            'porcentaje_aprobacion' => (float) ($stats['porcentaje_aprobacion'] ?? 0),
            'porcentaje_desaprobacion' => $total > 0
                ? round(($desaprobados / $total) * 100, 1)
                : 0,
            'generado_en'         => date('Y-m-d H:i:s'),
        ];
    }
}
