<?php

declare(strict_types=1);

namespace App\Application\UseCases\PeriodoAcademico;

use App\Domain\Entities\PeriodoAcademico;
use App\Domain\Repositories\PeriodoAcademicoRepositoryInterface;
use DomainException;

class ConsultarPeriodoActivo
{
    private PeriodoAcademicoRepositoryInterface $periodoRepo;

    public function __construct(PeriodoAcademicoRepositoryInterface $periodoRepo)
    {
        $this->periodoRepo = $periodoRepo;
    }

    public function ejecutar(): PeriodoAcademico
    {
        $periodo = $this->periodoRepo->obtenerPeriodoActivo();
        if (!$periodo) {
            throw new DomainException("No hay ningún periodo académico activo en este momento.");
        }
        return $periodo;
    }
}
