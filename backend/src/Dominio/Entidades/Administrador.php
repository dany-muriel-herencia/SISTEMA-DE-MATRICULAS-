<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Administrador extends Usuario
{
    private string $nivel;

    public function __construct(
        int $idUsuario,
        string $nombre,
        string $email,
        string $contrasenha,
        string $rol,
        bool $estado,
        DateTimeImmutable $fechaCreacion,
        string $nivel
    ) {
        parent::__construct(
            $idUsuario,
            $nombre,
            $email,
            $contrasenha,
            $rol,
            $estado,
            $fechaCreacion
        );

        if (empty(trim($nivel))) {
            throw new InvalidArgumentException(
                'El nivel del administrador es obligatorio'
            );
        }

        $this->nivel = $nivel;
    }

    public function getNivel(): string
    {
        return $this->nivel;
    }
}