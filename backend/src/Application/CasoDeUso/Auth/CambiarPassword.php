<?php

declare(strict_types=1);

namespace App\Aplicacion\CasosDeUso\Auth;

use App\Dominio\Repositorios\UsuarioRepositorio;
use RuntimeException;

class CambiarPassword
{
    private UsuarioRepositorio $usuarioRepositorio ;
    public function __construct( UsuarioRepositorio $usuarioRepositorio ) {
        
        $this->usuarioRepositorio = $usuarioRepositorio;

    }


    public function ejecutar( int $idUsuario, string $passwordActual, string $passwordNueva ): void {

        $usuario = $this->usuarioRepositorio->buscarPorId($idUsuario);

        if ($usuario === null) {
            throw new RuntimeException(
                'Usuario no encontrado'
            );
        }

        
        if (!password_verify( $passwordActual, $usuario->getContrasenha() )) {
            throw new RuntimeException(
                'La contraseña actual es incorrecta'
            );
        }

        
        if (strlen($passwordNueva) < 8) {
            throw new RuntimeException(
                'La nueva contraseña debe tener al menos 8 caracteres'
            );
        }

        
        $hash = password_hash(
            $passwordNueva,
            PASSWORD_DEFAULT
        );

        
        $usuario->cambiarContrasenha($hash);

        $this->usuarioRepositorio->actualizar($usuario);
    }
}