<?php

declare(strict_types=1);

namespace App\Aplicacion\CasosDeUso\Seguridad;

use App\Dominio\Entidades\Usuario;
use App\Dominio\Repositorios\UsuarioRepositorio;
use DateTimeImmutable;
use RuntimeException;

class RegistrarUsuario
{
    public function __construct(
        private UsuarioRepositorio $usuarioRepositorio
    ) {}

    public function ejecutar(
        string $nombre,
        string $email,
        string $password,
        string $rol
    ): Usuario {
        $usuarioExistente = $this->usuarioRepositorio->buscarPorEmail($email);

        if ($usuarioExistente !== null) {
            throw new RuntimeException(
                'El correo electrónico ya está registrado'
            );
        }

        if (strlen($password) < 8) {
            throw new RuntimeException(
                'La contraseña debe tener al menos 8 caracteres'
            );
        }

        $hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $usuario = new Usuario(
            0,
            $nombre,
            $email,
            $hash,
            $rol,
            true,
            new DateTimeImmutable()
        );

        $this->usuarioRepositorio->guardar($usuario);

        return $usuario;
    }
}