<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Docente;

interface DocenteRepositoryInterface
{
    public function guardar(Docente $docente): int;
    public function buscarPorId(int $id): ?Docente;
    public function buscarPorUsuarioId(int $usuarioId): ?Docente;
    public function listar(int $limit = 50, int $offset = 0): array;
}
