<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Estudiante;

use App\Dominio\Repositorios\CarreraRepositorio;
use App\Dominio\Repositorios\EstudianteRepositorio;
use App\Dominio\Repositorios\PlanEstudioRepositorio;
use DomainException;
use InvalidArgumentException;

/**
 * CU-09: Asociar estudiante con carrera y plan de estudios.
 * Actor Principal: Administrador.
 */
class AsociarEstudianteCarreraPlan
{
    private EstudianteRepositorio $estudianteRepo;
    private CarreraRepositorio $carreraRepo;
    private PlanEstudioRepositorio $planEstudioRepo;

    public function __construct(
        EstudianteRepositorio $estudianteRepo,
        CarreraRepositorio $carreraRepo,
        PlanEstudioRepositorio $planEstudioRepo
    ) {
        $this->estudianteRepo  = $estudianteRepo;
        $this->carreraRepo     = $carreraRepo;
        $this->planEstudioRepo = $planEstudioRepo;
    }

    public function ejecutar(int $idEstudiante, int $idCarrera, int $idPlanEstudio): array
    {
        if ($idEstudiante <= 0 || $idCarrera <= 0 || $idPlanEstudio <= 0) {
            throw new InvalidArgumentException('IDs de estudiante, carrera y plan de estudio deben ser enteros positivos.');
        }

        $estudiante = $this->estudianteRepo->buscarPorId($idEstudiante);
        if ($estudiante === null) {
            throw new DomainException("Estudiante con ID {$idEstudiante} no encontrado.");
        }

        $carrera = $this->carreraRepo->buscarPorId($idCarrera);
        if ($carrera === null) {
            throw new DomainException("Carrera profesional con ID {$idCarrera} no encontrada.");
        }

        $plan = $this->planEstudioRepo->buscarPorId($idPlanEstudio);
        if ($plan === null) {
            throw new DomainException("Plan de estudios con ID {$idPlanEstudio} no encontrado.");
        }

        return [
            'id_estudiante'        => $idEstudiante,
            'codigo_universitario' => $estudiante->getCodigoUniversitario(),
            'nombre_estudiante'    => $estudiante->getNombre(),
            'id_carrera'           => $idCarrera,
            'nombre_carrera'       => $carrera->getNombre(),
            'id_plan_estudio'      => $idPlanEstudio,
            'anio_plan'            => $plan->getAnio(),
            'mensaje'              => 'Estudiante asociado correctamente con la carrera y el plan de estudios.'
        ];
    }
}
