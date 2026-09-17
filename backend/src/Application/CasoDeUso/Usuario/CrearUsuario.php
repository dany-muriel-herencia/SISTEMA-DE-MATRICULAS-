<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Usuario;

use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DateTimeImmutable;
use DomainException;

class CrearUsuario
{
    private UsuarioRepositorio $usuarioRepo;

    public function __construct(UsuarioRepositorio $usuarioRepo)
    {
        $this->usuarioRepo = $usuarioRepo;
    }

    public function ejecutar(
        string $nombre,
        string $email,
        string $password,
        string $rol = 'ESTUDIANTE'
    ): Usuario {
        if ($this->usuarioRepo->buscarPorEmail($email) !== null) {
            throw new DomainException("El correo electrónico '{$email}' ya se encuentra registrado.");
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        $usuario = new Usuario(
            0,
            $nombre,
            $email,
            $passwordHash,
            $rol,
            true,
            new DateTimeImmutable()
        );

        $this->usuarioRepo->guardar($usuario);

        return $usuario;
    }
}
