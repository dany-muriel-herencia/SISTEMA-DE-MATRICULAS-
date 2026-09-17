<?php

declare(strict_types=1);

namespace App\Aplicacion\CasosDeUso\Auth;

use App\Dominio\Entidades\Sesion;
use App\Dominio\Repositorios\UsuarioRepositorio;
use App\Dominio\Repositorios\SesionRepositorio;
use DateTimeImmutable;
use RuntimeException;

class IniciarSesion
{
    private UsuarioRepositorio $usuarioRepositorio;
    private SesionRepositorio $sesionRepositorio;

    public function __construct(
        UsuarioRepositorio $usuarioRepositorio,
        SesionRepositorio $sesionRepositorio
    ) {
        $this->usuarioRepositorio = $usuarioRepositorio;
        $this->sesionRepositorio = $sesionRepositorio;
    }

    public function ejecutar(
        string $email,
        string $password,
        string $ip
    ): Sesion {


        $usuario = $this->usuarioRepositorio->buscarPorEmail($email);

        if ($usuario === null) {
            throw new RuntimeException(
                'Credenciales incorrectas'
            );
        }


        if (!$usuario->getEstado()) {
            throw new RuntimeException(
                'El usuario se encuentra desactivado'
            );
        }


        if (!password_verify($password, $usuario->getContrasenha())) {
            throw new RuntimeException(
                'Credenciales incorrectas'
            );
        }


        $token = bin2hex(random_bytes(32));


        $sesion = new Sesion(
            1,
            $usuario->getIdUsuario(),
            $token,
            new DateTimeImmutable(),
            null,
            $ip,
            true
        );


        $this->sesionRepositorio->guardar($sesion);

        return $sesion;
    }
}
