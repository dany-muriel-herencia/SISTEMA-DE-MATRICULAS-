<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Reporte;

use DomainException;

/**
 * CU-51: Generar actas de calificaciones.
 * Actor: Docente, Administrador. Secundario: Sistema.
 *
 * El acta lista todos los estudiantes de la sección con su resultado
 * (APROBADO / DESAPROBADO / MATRICULADO) derivado del estado del
 * detalle_matricula, sin necesidad de una tabla de notas separada.
 */
class GenerarActaCalificaciones
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

        $filas = $this->reporteServicio->actaCalificacionesPorSeccion($idSeccion);

        if (empty($filas)) {
            return [
                'id_seccion'    => $idSeccion,
                'curso'         => null,
                'docente'       => null,
                'estudiantes'   => [],
                'generado_en'   => date('Y-m-d H:i:s'),
            ];
        }

        $primera = $filas[0];

        return [
            'id_seccion'  => $idSeccion,
            'codigo'      => $primera['codigo_seccion']   ?? '',
            'curso'       => [
                'nombre'  => $primera['nombre_curso']     ?? '',
                'codigo'  => $primera['codigo_curso']     ?? '',
                'creditos'=> $primera['creditos']         ?? 0,
            ],
            'docente'     => [
                'nombre'  => $primera['nombre_docente']   ?? '',
                'apellido'=> $primera['apellido_docente'] ?? '',
            ],
            'estudiantes' => array_map(fn(array $f): array => [
                'codigo_matricula'  => $f['codigo_matricula']  ?? '',
                'codigo_estudiante' => $f['codigo_estudiante'] ?? '',
                'nombre'            => $f['nombre']            ?? '',
                'apellido'          => $f['apellido']          ?? '',
                'resultado'         => $f['resultado']         ?? 'MATRICULADO',
            ], $filas),
            'total'       => count($filas),
            'generado_en' => date('Y-m-d H:i:s'),
        ];
    }
}
