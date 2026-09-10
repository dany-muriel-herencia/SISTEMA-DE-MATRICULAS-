<?php

declare(strict_types=1);

namespace App\Application\UseCases\Matricula;

use App\Domain\Entities\Matricula;
use App\Domain\Repositories\MatriculaRepositoryInterface;
use DomainException;

class ConsultarMatricula
{
    private MatriculaRepositoryInterface $matriculaRepo;

    public function __construct(MatriculaRepositoryInterface $matriculaRepo)
    {
        $this->matriculaRepo = $matriculaRepo;
    }

    public function ejecutarPorId(int $id): Matricula
    {
        $matricula = $this->matriculaRepo->buscarPorId($id);
        if (!$matricula) {
            throw new DomainException("No se encontró la matrícula con ID {$id}.");
        }
        return $matricula;
    }

    public function ejecutarPorCodigo(string $codigo): Matricula
    {
        $matricula = $this->matriculaRepo->buscarPorCodigo($codigo);
        if (!$matricula) {
            throw new DomainException("No se encontró la matrícula con código {$codigo}.");
        }
        return $matricula;
    }

    public function listarPorEstudiante(int $estudianteId): array
    {
        return $this->matriculaRepo->listarPorEstudiante($estudianteId);
    }
}
