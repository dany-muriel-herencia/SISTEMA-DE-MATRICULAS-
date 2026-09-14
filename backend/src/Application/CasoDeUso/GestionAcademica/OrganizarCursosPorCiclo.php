<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\GestionAcademica;

use App\Dominio\Entidades\Curriculum;
use App\Dominio\Repositorios\CurriculumRepositorio;
use App\Dominio\Repositorios\CursoRepositorio;
use App\Dominio\Repositorios\PlanEstudioRepositorio;
use DomainException;
use InvalidArgumentException;

/**
 * CU-17: Organizar los cursos por ciclo académico.
 * Actor Principal: Administrador.
 */
class OrganizarCursosPorCiclo
{
    private CurriculumRepositorio $curriculumRepo;
    private PlanEstudioRepositorio $planRepo;
    private CursoRepositorio $cursoRepo;

    public function __construct(
        CurriculumRepositorio $curriculumRepo,
        PlanEstudioRepositorio $planRepo,
        CursoRepositorio $cursoRepo
    ) {
        $this->curriculumRepo = $curriculumRepo;
        $this->planRepo       = $planRepo;
        $this->cursoRepo      = $cursoRepo;
    }

    public function asignarCursoACiclo(int $idPlan, int $idCurso, int $ciclo, bool $obligatorio = true): array
    {
        if ($idPlan <= 0 || $idCurso <= 0 || $ciclo <= 0) {
            throw new InvalidArgumentException('El ID de plan, ID de curso y número de ciclo deben ser enteros positivos.');
        }

        $plan = $this->planRepo->buscarPorId($idPlan);
        if ($plan === null) {
            throw new DomainException("Plan de estudios con ID {$idPlan} no encontrado.");
        }

        $curso = $this->cursoRepo->buscarPorId($idCurso);
        if ($curso === null) {
            throw new DomainException("Curso con ID {$idCurso} no encontrado.");
        }

        // Fictional ID 1 for instantiation before DB insert
        $curriculum = new Curriculum(1, $idPlan, $idCurso, $ciclo, $obligatorio);
        $this->curriculumRepo->guardar($curriculum);

        return [
            'id_plan'     => $idPlan,
            'nombre_plan' => $plan->getNombre(),
            'id_curso'    => $idCurso,
            'nombre_curso'=> $curso->getNombre(),
            'ciclo'       => $ciclo,
            'obligatorio' => $obligatorio,
            'mensaje'     => "Curso asignado exitosamente al ciclo {$ciclo} del plan de estudio."
        ];
    }

    public function obtenerMallaPorCiclos(int $idPlan): array
    {
        $curriculums = $this->curriculumRepo->listar();
        $filtrados   = array_filter($curriculums, fn($c) => $c->getIdPlan() === $idPlan);

        $mallaPorCiclos = [];
        foreach ($filtrados as $c) {
            $curso = $this->cursoRepo->buscarPorId($c->getIdCurso());
            $ciclo = $c->getCiclo();

            if (!isset($mallaPorCiclos[$ciclo])) {
                $mallaPorCiclos[$ciclo] = [];
            }

            $mallaPorCiclos[$ciclo][] = [
                'id_curriculum' => $c->getIdCurriculum(),
                'id_curso'      => $c->getIdCurso(),
                'nombre_curso'  => $curso ? $curso->getNombre() : "Curso {$c->getIdCurso()}",
                'codigo_curso'  => $curso ? $curso->getCodigo() : '',
                'creditos'      => $curso ? $curso->getCreditos() : 0,
                'obligatorio'   => $c->esObligatorio()
            ];
        }

        ksort($mallaPorCiclos);

        return [
            'id_plan' => $idPlan,
            'malla'   => $mallaPorCiclos
        ];
    }
}
