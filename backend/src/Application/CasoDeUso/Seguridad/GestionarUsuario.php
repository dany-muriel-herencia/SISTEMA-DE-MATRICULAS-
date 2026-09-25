<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Seguridad;

use App\Dominio\Repositorios\UsuarioRepositorio;
use RuntimeException;

class GestionarUsuario
{
    public function __construct(
        private UsuarioRepositorio $usuarioRepositorio
    ) {}

    public function modificar(
        int $idUsuario,
        string $nombre,
        string $email
    ): void {

        $usuario = $this->usuarioRepositorio
            ->buscarPorId($idUsuario);

        if ($usuario === null) {
            throw new RuntimeException(
                'Usuario no encontrado'
            );
        }

        $usuario->actualizarDatos(
            $nombre,
            $email
        );

        $this->usuarioRepositorio->actualizar($usuario);
    }

    public function desactivar(int $idUsuario): void
    {
        $usuario = $this->usuarioRepositorio
            ->buscarPorId($idUsuario);

        if ($usuario === null) {
            throw new RuntimeException(
                'Usuario no encontrado'
            );
        }

        $usuario->desactivar();

        $this->usuarioRepositorio->actualizar($usuario);
    }

    public function activar(int $idUsuario): void
    {
        $usuario = $this->usuarioRepositorio
            ->buscarPorId($idUsuario);

        if ($usuario === null) {
            throw new RuntimeException(
                'Usuario no encontrado'
            );
        }

        $usuario->activar();

        $this->usuarioRepositorio->actualizar($usuario);
    }
}