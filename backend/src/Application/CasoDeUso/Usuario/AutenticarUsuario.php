<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Usuario;

use App\Application\DTO\LoginDTO;
use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DomainException;

class AutenticarUsuario
{
    private UsuarioRepositorio $usuarioRepo;

    public function __construct(UsuarioRepositorio $usuarioRepo)
    {
        $this->usuarioRepo = $usuarioRepo;
    }

    public function ejecutar(LoginDTO $dto): Usuario
    {
        $usuario = $this->usuarioRepo->buscarPorEmail($dto->getEmail()->getValue());
        if (!$usuario) {
            throw new DomainException("Credenciales inválidas.");
        }

        if (!$usuario->estaActivo()) {
            throw new DomainException("La cuenta de usuario se encuentra inactiva.");
        }

        if (!password_verify($dto->getPassword(), $usuario->getContrasenha())) {
            throw new DomainException("Credenciales inválidas.");
        }

        return $usuario;
    }
}
