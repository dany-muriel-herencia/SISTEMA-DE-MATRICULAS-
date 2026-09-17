<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\DetalleMatricula;
use App\Dominio\Repositorios\DetalleMatriculaRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class DetalleMatriculaControlador
{
    public function __construct(
        private readonly DetalleMatriculaRepositorio $detalleMatriculaRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idMatricula = (int)($datos['id_matricula'] ?? 0);
            $idSeccion   = (int)($datos['id_seccion'] ?? 0);
            $estado      = strtoupper(trim((string)($datos['estado'] ?? 'MATRICULADO')));

            $detalle = new DetalleMatricula(
                0,
                $idMatricula,
                $idSeccion,
                $estado
            );

            $this->detalleMatriculaRepositorio->guardar($detalle);

            return [
                'success' => true,
                'message' => 'Detalle de matrícula registrado correctamente.',
                'data'    => $detalle->toArray()
            ];
        } catch (InvalidArgumentException | DomainException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al registrar detalle de matrícula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idDetalle): array
    {
        try {
            $detalle = $this->detalleMatriculaRepositorio->buscarPorId($idDetalle);
            if ($detalle === null) {
                return [
                    'success' => false,
                    'message' => "Detalle de matrícula con ID {$idDetalle} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Detalle de matrícula encontrado.',
                'data'    => $detalle->toArray()
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar detalle de matrícula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listarPorMatricula(int $idMatricula): array
    {
        try {
            $detalles = $this->detalleMatriculaRepositorio->listarPorMatricula($idMatricula);
            $data = array_map(fn(DetalleMatricula $d) => $d->toArray(), $detalles);

            return [
                'success' => true,
                'message' => 'Detalles de la matrícula obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar detalles de matrícula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idDetalle = (int)($datos['id_detalle'] ?? $datos['id'] ?? 0);
            $existente = $this->detalleMatriculaRepositorio->buscarPorId($idDetalle);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Detalle de matrícula con ID {$idDetalle} no encontrado.",
                    'data'    => null
                ];
            }

            $idMatricula = (int)($datos['id_matricula'] ?? $existente->getIdMatricula());
            $idSeccion   = (int)($datos['id_seccion'] ?? $existente->getIdSeccion());
            $estado      = strtoupper(trim((string)($datos['estado'] ?? $existente->getEstado())));

            $actualizado = new DetalleMatricula(
                $idDetalle,
                $idMatricula,
                $idSeccion,
                $estado
            );

            $this->detalleMatriculaRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Detalle de matrícula actualizado correctamente.',
                'data'    => $actualizado->toArray()
            ];
        } catch (InvalidArgumentException | DomainException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar detalle de matrícula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function eliminar(int $idDetalle): array
    {
        try {
            $this->detalleMatriculaRepositorio->eliminar($idDetalle);

            return [
                'success' => true,
                'message' => "Detalle de matrícula con ID {$idDetalle} eliminado correctamente.",
                'data'    => ['id_detalle' => $idDetalle]
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar detalle de matrícula: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }
}
