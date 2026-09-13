<?php

declare(strict_types=1);

namespace App\Application\UseCases\Usuario;

use App\Application\DTO\LoginDTO;
use App\Domain\Entities\Usuario;
use App\Domain\Repositories\UsuarioRepositoryInterface;
use DomainException;

class AutenticarUsuario
{
    private UsuarioRepositoryInterface $usuarioRepo;

    public function __construct(UsuarioRepositoryInterface $usuarioRepo)
    {
        $this->usuarioRepo = $usuarioRepo;
    }

    public function ejecutar(LoginDTO $dto): Usuario
    {
        $usuario = $this->usuarioRepo->buscarPorEmail($dto->getEmail());
        if (!$usuario) {
            throw new DomainException("Credenciales inválidas.");
        }

        if (!$usuario->isActivo()) {
            throw new DomainException("La cuenta de usuario se encuentra inactiva.");
        }

        if (!password_verify($dto->getPassword(), $usuario->getPasswordHash())) {
            throw new DomainException("Credenciales inválidas.");
        }

        return $usuario;
    }
}
