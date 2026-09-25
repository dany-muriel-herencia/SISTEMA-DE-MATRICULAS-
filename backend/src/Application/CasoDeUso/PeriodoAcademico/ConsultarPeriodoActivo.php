<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\PeriodoAcademico;

use App\Dominio\Entidades\PeriodoAcademico;
use App\Dominio\Repositorios\PeriodoAcademicoRepositorio;
use DomainException;

class ConsultarPeriodoActivo
{
    private PeriodoAcademicoRepositorio $periodoRepo;

    public function __construct(PeriodoAcademicoRepositorio $periodoRepo)
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
