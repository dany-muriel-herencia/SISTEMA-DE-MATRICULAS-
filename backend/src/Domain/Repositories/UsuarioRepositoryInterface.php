<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entities\Usuario;
use App\Domain\ValueObjects\Dni;
use App\Domain\ValueObjects\Email;

interface UsuarioRepositoryInterface
{
    public function guardar(Usuario $usuario): int;
    public function actualizar(Usuario $usuario): bool;
    public function buscarPorId(int $id): ?Usuario;
    public function buscarPorEmail(Email $email): ?Usuario;
    public function buscarPorDni(Dni $dni): ?Usuario;
    public function listar(int $limit = 50, int $offset = 0): array;
    public function eliminar(int $id): bool;
}
