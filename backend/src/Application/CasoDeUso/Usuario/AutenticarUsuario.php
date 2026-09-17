<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Usuario;

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

    public function ejecutar(string $email, string $password): Usuario
    {
        $usuario = $this->usuarioRepo->buscarPorEmail($email);
        if (!$usuario) {
            throw new DomainException("Credenciales inválidas.");
        }

        if (!$usuario->getEstado()) {
            throw new DomainException("La cuenta de usuario se encuentra inactiva.");
        }

        if (!password_verify($password, $usuario->getContrasenha())) {
            throw new DomainException("Credenciales inválidas.");
        }

        return $usuario;
    }
}
