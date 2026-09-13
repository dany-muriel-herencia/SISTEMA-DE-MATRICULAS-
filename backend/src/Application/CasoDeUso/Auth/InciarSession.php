<?php

declare(strict_types=1);

namespace App\Application\UseCases\Curso;

use App\Domain\Entities\Curso;
use App\Domain\Repositories\CursoRepositoryInterface;
use DomainException;

class ConsultarCursos
{
    private CursoRepositoryInterface $cursoRepo;

    public function __construct(CursoRepositoryInterface $cursoRepo)
    {
        $this->cursoRepo = $cursoRepo;
    }

    public function ejecutarPorId(int $id): Curso
    {
        $curso = $this->cursoRepo->buscarPorId($id);
        if (!$curso) {
            throw new DomainException("Curso con ID {$id} no encontrado.");
        }
        return $curso;
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        return $this->cursoRepo->listar($limit, $offset);
    }

    public function listarOfertaPorPeriodoYCarrera(int $periodoId, int $carreraId): array
    {
        return $this->cursoRepo->listarOfertaPorPeriodoYCarrera($periodoId, $carreraId);
    }
}
