<?php

namespace App\Dominio\Repositorios;

use App\Dominio\Entidades\PlanEstudio;

interface PlanEstudioRepositorio
{
    public function buscarPorId( int $idPlan ): ?PlanEstudio;

    public function listar(): array;

    public function guardar(  PlanEstudio $planEstudio ): void;

    public function actualizar( PlanEstudio $planEstudio ): void;
}