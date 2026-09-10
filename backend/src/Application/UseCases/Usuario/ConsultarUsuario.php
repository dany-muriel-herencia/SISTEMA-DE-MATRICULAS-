<?php

declare(strict_types=1);

namespace App\Application\UseCases\Usuario;

use App\Domain\Entities\Usuario;
use App\Domain\Repositories\UsuarioRepositoryInterface;
use DomainException;

class ConsultarUsuario
{
    private UsuarioRepositoryInterface $usuarioRepo;

    public function __construct(UsuarioRepositoryInterface $usuarioRepo)
    {
        $this->usuarioRepo = $usuarioRepo;
    }

    public function ejecutarPorId(int $id): Usuario
    {
        $usuario = $this->usuarioRepo->buscarPorId($id);
        if (!$usuario) {
            throw new DomainException("Usuario con ID {$id} no encontrado.");
        }
        return $usuario;
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        return $this->usuarioRepo->listar($limit, $offset);
    }
}
