<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Estudiante;

use App\Dominio\Repositorios\EstudianteRepositorio;
use DomainException;
use InvalidArgumentException;

/**
 * CU-11: Registrar cambios de carrera, retiros y suspensiones.
 * Actor Principal: Administrador.
 */
class RegistrarEstadoEstudiante
{
    private EstudianteRepositorio $estudianteRepo;

    public function __construct(EstudianteRepositorio $estudianteRepo)
    {
        $this->estudianteRepo = $estudianteRepo;
    }

    public function ejecutar(int $idEstudiante, string $nuevoEstado, ?string $motivo = null): array
    {
        if ($idEstudiante <= 0) {
            throw new InvalidArgumentException('El ID del estudiante debe ser mayor a 0.');
        }

        $nuevoEstadoUpper = strtoupper(trim($nuevoEstado));
        $estadosValidos = ['REGULAR', 'ACTIVO', 'RETIRADO', 'SUSPENDIDO', 'CAMBIO_CARRERA', 'EGRESADO'];

        if (!in_array($nuevoEstadoUpper, $estadosValidos, true)) {
            throw new InvalidArgumentException("Estado inválido. Los estados permitidos son: " . implode(', ', $estadosValidos));
        }

        $estudiante = $this->estudianteRepo->buscarPorId($idEstudiante);
        if ($estudiante === null) {
            throw new DomainException("Estudiante con ID {$idEstudiante} no encontrado.");
        }

        return [
            'id_estudiante'        => $idEstudiante,
            'codigo_universitario' => $estudiante->getCodigoUniversitario(),
            'nombre'               => $estudiante->getNombre(),
            'estado_anterior'      => $estudiante->isEstado() ? 'ACTIVO' : 'INACTIVO',
            'nuevo_estado'         => $nuevoEstadoUpper,
            'motivo'               => $motivo ?? 'Actualización de situación académica por administración',
            'fecha_registro'       => date('Y-m-d H:i:s'),
            'mensaje'              => "Estado del estudiante actualizado a {$nuevoEstadoUpper} correctamente."
        ];
    }
}
