<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\PeriodoAcademico;

interface PeriodoAcademicoRepositorio
{
    public function buscarPorId(int $idPeriodo): ?PeriodoAcademico;

    public function obtenerPeriodoActivo(): ?PeriodoAcademico;

    public function guardar(PeriodoAcademico $periodo): void;

    public function actualizar(PeriodoAcademico $periodo): void;
}