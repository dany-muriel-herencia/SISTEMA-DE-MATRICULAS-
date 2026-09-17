<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\PlanEstudio;
use App\Dominio\Repositorios\PlanEstudioRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class PlanEstudioControlador
{
    public function __construct(
        private readonly PlanEstudioRepositorio $planEstudioRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idCarrera   = (int)($datos['id_carrera'] ?? 0);
            $nombre      = trim((string)($datos['nombre'] ?? ''));
            $fechaInicio = new DateTimeImmutable((string)($datos['fecha_inicio'] ?? 'now'));
            $fechaFin    = isset($datos['fecha_fin']) && !empty($datos['fecha_fin']) ? new DateTimeImmutable((string)$datos['fecha_fin']) : null;
            $estado      = isset($datos['estado']) ? (bool)$datos['estado'] : true;

            $plan = new PlanEstudio(
                1,
                $idCarrera,
                $nombre,
                $fechaInicio,
                $fechaFin,
                $estado
            );

            $this->planEstudioRepositorio->guardar($plan);

            return [
                'success' => true,
                'message' => 'Plan de estudio registrado correctamente.',
                'data'    => $this->mapPlan($plan)
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
                'message' => 'Error al registrar plan de estudio: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idPlan): array
    {
        try {
            $plan = $this->planEstudioRepositorio->buscarPorId($idPlan);
            if ($plan === null) {
                return [
                    'success' => false,
                    'message' => "Plan de estudio con ID {$idPlan} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Plan de estudio encontrado.',
                'data'    => $this->mapPlan($plan)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar plan de estudio: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $planes = $this->planEstudioRepositorio->listar();
            $data = array_map(fn(PlanEstudio $p) => $this->mapPlan($p), $planes);

            return [
                'success' => true,
                'message' => 'Planes de estudio obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar planes de estudio: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idPlan = (int)($datos['id_plan'] ?? $datos['id'] ?? 0);
            $existente = $this->planEstudioRepositorio->buscarPorId($idPlan);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Plan de estudio con ID {$idPlan} no encontrado.",
                    'data'    => null
                ];
            }

            $idCarrera   = (int)($datos['id_carrera'] ?? $existente->getIdCarrera());
            $nombre      = trim((string)($datos['nombre'] ?? $existente->getNombre()));
            $fechaInicio = isset($datos['fecha_inicio']) ? new DateTimeImmutable((string)$datos['fecha_inicio']) : $existente->getFechaInicio();
            $fechaFin    = isset($datos['fecha_fin']) ? (!empty($datos['fecha_fin']) ? new DateTimeImmutable((string)$datos['fecha_fin']) : null) : $existente->getFechaFin();
            $estado      = isset($datos['estado']) ? (bool)$datos['estado'] : $existente->getEstado();

            $actualizado = new PlanEstudio(
                $idPlan,
                $idCarrera,
                $nombre,
                $fechaInicio,
                $fechaFin,
                $estado
            );

            $this->planEstudioRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Plan de estudio actualizado correctamente.',
                'data'    => $this->mapPlan($actualizado)
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
                'message' => 'Error al actualizar plan de estudio: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapPlan(PlanEstudio $p): array
    {
        return [
            'id_plan'      => $p->getIdPlan(),
            'id_carrera'   => $p->getIdCarrera(),
            'nombre'       => $p->getNombre(),
            'fecha_inicio' => $p->getFechaInicio()->format('Y-m-d'),
            'fecha_fin'    => $p->getFechaFin()?->format('Y-m-d'),
            'estado'       => $p->getEstado(),
            'anio'         => $p->getAnio()
        ];
    }
}
