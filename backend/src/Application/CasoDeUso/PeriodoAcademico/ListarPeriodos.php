<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\PeriodoAcademico;

use App\Dominio\Repositorios\PeriodoAcademicoRepositorio;

class ListarPeriodos
{
    private PeriodoAcademicoRepositorio $periodoRepo;

    public function __construct(PeriodoAcademicoRepositorio $periodoRepo)
    {
        $this->periodoRepo = $periodoRepo;
    }

    public function ejecutar(int $limit = 50, int $offset = 0): array
    {
        return $this->periodoRepo->listar($limit, $offset);
    }
}
