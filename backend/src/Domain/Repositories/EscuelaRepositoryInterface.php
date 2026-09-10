<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Escuela;

interface EscuelaRepositoryInterface
{
    public function guardar(Escuela $escuela): int;
    public function buscarPorId(int $id): ?Escuela;
    public function buscarPorCodigo(string $codigo): ?Escuela;
    public function listarPorFacultad(int $facultadId): array;
    public function listar(): array;
}
