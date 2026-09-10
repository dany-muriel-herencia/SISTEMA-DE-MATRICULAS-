<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Facultad;

interface FacultadRepositoryInterface
{
    public function guardar(Facultad $facultad): int;
    public function buscarPorId(int $id): ?Facultad;
    public function buscarPorCodigo(string $codigo): ?Facultad;
    public function listar(): array;
}
