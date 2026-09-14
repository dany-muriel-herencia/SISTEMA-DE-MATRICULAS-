<?php

declare(strict_types=1);

namespace App\Aplicacion\CasosDeUso\Seguridad;

use App\Dominio\Repositorios\UsuarioRepositorio;
use RuntimeException;

class AsignarRol
{
    public function __construct(
        private UsuarioRepositorio $usuarioRepositorio
    ) {}

    public function ejecutar(
        int $idUsuario,
        string $rol
    ): void {

        $usuario = $this->usuarioRepositorio
            ->buscarPorId($idUsuario);

        if ($usuario === null) {
            throw new RuntimeException(
                'Usuario no encontrado'
            );
        }

        $rolesPermitidos = [
            'ADMIN',
            'ESTUDIANTE',
            'DOCENTE'
        ];

        if (!in_array($rol, $rolesPermitidos, true)) {
            throw new RuntimeException(
                'Rol no válido'
            );
        }

        $usuario->cambiarRol($rol);

        $this->usuarioRepositorio->actualizar($usuario);
    }
}