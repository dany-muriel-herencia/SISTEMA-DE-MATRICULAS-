<?php

declare(strict_types=1);

namespace App\Application\UseCases\Docente;

use App\Domain\Entities\Docente;
use App\Domain\Repositories\DocenteRepositoryInterface;
use DomainException;

class ConsultarDocente
{
    private DocenteRepositoryInterface $docenteRepo;

    public function __construct(DocenteRepositoryInterface $docenteRepo)
    {
        $this->docenteRepo = $docenteRepo;
    }

    public function ejecutarPorId(int $id): Docente
    {
        $docente = $this->docenteRepo->buscarPorId($id);
        if (!$docente) {
            throw new DomainException("Docente con ID {$id} no encontrado.");
        }
        return $docente;
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        return $this->docenteRepo->listar($limit, $offset);
    }
}
