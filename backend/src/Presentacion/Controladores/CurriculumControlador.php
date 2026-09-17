<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Curriculum;
use App\Dominio\Repositorios\CurriculumRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class CurriculumControlador
{
    public function __construct(
        private readonly CurriculumRepositorio $curriculumRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idPlan      = (int)($datos['id_plan'] ?? 0);
            $idCurso     = (int)($datos['id_curso'] ?? 0);
            $ciclo       = (int)($datos['ciclo'] ?? 1);
            $obligatorio = isset($datos['obligatorio']) ? (bool)$datos['obligatorio'] : true;

            $curriculum = new Curriculum(
                1,
                $idPlan,
                $idCurso,
                $ciclo,
                $obligatorio
            );

            $this->curriculumRepositorio->guardar($curriculum);

            return [
                'success' => true,
                'message' => 'Curso asignado al plan de estudios correctamente.',
                'data'    => $this->mapCurriculum($curriculum)
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
                'message' => 'Error al registrar currículo: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idCurriculum): array
    {
        try {
            $curriculum = $this->curriculumRepositorio->buscarPorId($idCurriculum);
            if ($curriculum === null) {
                return [
                    'success' => false,
                    'message' => "Currículo con ID {$idCurriculum} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Currículo encontrado.',
                'data'    => $this->mapCurriculum($curriculum)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar currículo: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $curriculums = $this->curriculumRepositorio->listar();
            $data = array_map(fn(Curriculum $c) => $this->mapCurriculum($c), $curriculums);

            return [
                'success' => true,
                'message' => 'Currículos obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar currículos: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idCurriculum = (int)($datos['id_curriculum'] ?? $datos['id'] ?? 0);
            $existente    = $this->curriculumRepositorio->buscarPorId($idCurriculum);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Currículo con ID {$idCurriculum} no encontrado.",
                    'data'    => null
                ];
            }

            $idPlan      = (int)($datos['id_plan'] ?? $existente->getIdPlan());
            $idCurso     = (int)($datos['id_curso'] ?? $existente->getIdCurso());
            $ciclo       = (int)($datos['ciclo'] ?? $existente->getCiclo());
            $obligatorio = isset($datos['obligatorio']) ? (bool)$datos['obligatorio'] : $existente->esObligatorio();

            $actualizado = new Curriculum(
                $idCurriculum,
                $idPlan,
                $idCurso,
                $ciclo,
                $obligatorio
            );

            $this->curriculumRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Currículo actualizado correctamente.',
                'data'    => $this->mapCurriculum($actualizado)
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
                'message' => 'Error al actualizar currículo: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapCurriculum(Curriculum $c): array
    {
        return [
            'id_curriculum' => $c->getIdCurriculum(),
            'id_plan'       => $c->getIdPlan(),
            'id_curso'      => $c->getIdCurso(),
            'ciclo'         => $c->getCiclo(),
            'obligatorio'   => $c->esObligatorio()
        ];
    }
}
