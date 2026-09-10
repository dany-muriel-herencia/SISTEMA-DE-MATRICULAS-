<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\PeriodoAcademico;

interface PeriodoAcademicoRepositoryInterface
{
    public function guardar(PeriodoAcademico $periodo): int;
    public function buscarPorId(int $id): ?PeriodoAcademico;
    public function buscarPorCodigo(string $codigo): ?PeriodoAcademico;
    public function obtenerPeriodoActivo(): ?PeriodoAcademico;
    public function listar(int $limit = 50, int $offset = 0): array;
}
