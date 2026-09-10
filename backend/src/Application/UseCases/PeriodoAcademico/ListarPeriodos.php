<?php

declare(strict_types=1);

namespace App\Application\UseCases\PeriodoAcademico;

use App\Domain\Repositories\PeriodoAcademicoRepositoryInterface;

class ListarPeriodos
{
    private PeriodoAcademicoRepositoryInterface $periodoRepo;

    public function __construct(PeriodoAcademicoRepositoryInterface $periodoRepo)
    {
        $this->periodoRepo = $periodoRepo;
    }

    public function ejecutar(int $limit = 50, int $offset = 0): array
    {
        return $this->periodoRepo->listar($limit, $offset);
    }
}
