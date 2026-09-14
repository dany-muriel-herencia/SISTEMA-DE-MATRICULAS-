<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\GestionAcademica;

use App\Dominio\Entidades\PlanEstudio;
use App\Dominio\Repositorios\CarreraRepositorio;
use App\Dominio\Repositorios\PlanEstudioRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * CU-13: Crear y mantener planes de estudio.
 * Actor Principal: Administrador.
 */
class GestionarPlanEstudio
{
    private PlanEstudioRepositorio $planRepo;
    private CarreraRepositorio $carreraRepo;

    public function __construct(
        PlanEstudioRepositorio $planRepo,
        CarreraRepositorio $carreraRepo
    ) {
        $this->planRepo    = $planRepo;
        $this->carreraRepo = $carreraRepo;
    }

    public function crearPlanEstudio(int $idCarrera, string $nombre, string $fechaInicio, ?string $fechaFin = null): array
    {
        if ($idCarrera <= 0) {
            throw new InvalidArgumentException('El ID de la carrera debe ser un entero positivo.');
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre del plan de estudio es obligatorio.');
        }

        $carrera = $this->carreraRepo->buscarPorId($idCarrera);
        if ($carrera === null) {
            throw new DomainException("Carrera con ID {$idCarrera} no encontrada.");
        }

        $inicioObj = new DateTimeImmutable($fechaInicio);
        $finObj    = $fechaFin ? new DateTimeImmutable($fechaFin) : null;

        // Pasar 1 como idPlan ficticio para pasar validación de entidad $idPlan > 0 al guardar
        $plan = new PlanEstudio(1, $idCarrera, $nombre, $inicioObj, $finObj, true);
        $this->planRepo->guardar($plan);

        return [
            'id_carrera'   => $idCarrera,
            'nombre'       => $nombre,
            'fecha_inicio' => $inicioObj->format('Y-m-d'),
            'fecha_fin'    => $finObj?->format('Y-m-d'),
            'estado'       => true,
            'mensaje'      => 'Plan de estudios creado correctamente.'
        ];
    }

    public function listarPlanes(): array
    {
        $planes = $this->planRepo->listar();
        return array_map(fn($p) => [
            'id_plan'      => $p->getIdPlan(),
            'id_carrera'   => $p->getIdCarrera(),
            'nombre'       => $p->getNombre(),
            'fecha_inicio' => $p->getFechaInicio()->format('Y-m-d'),
            'fecha_fin'    => $p->getFechaFin()?->format('Y-m-d'),
            'estado'       => $p->getEstado()
        ], $planes);
    }
}
