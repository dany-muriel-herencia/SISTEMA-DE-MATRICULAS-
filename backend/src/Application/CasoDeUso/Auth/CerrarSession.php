<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Auth;

use App\Dominio\Repositorios\SesionRepositorio;
use DateTimeImmutable;
use RuntimeException;

class CerrarSesion
{

    private SesionRepositorio $sesionRepositorio;

    public function __construct(SesionRepositorio $sesionRepositorio)
    {

        $this->sesionRepositorio = $sesionRepositorio;
    }

    public function ejecutar(string $token): void
    {
        $sesion = $this->sesionRepositorio->buscarPorToken($token);

        if ($sesion === null) {
            throw new RuntimeException(
                'Sesión no encontrada'
            );
        }

        if (!$sesion->getActiva()) {
            throw new RuntimeException(
                'La sesión ya está cerrada'
            );
        }


        $sesion->cerrarSesion();

        $this->sesionRepositorio->actualizar($sesion);
    }
}
