<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Facultad
{
    private int $idFacultad;
    private string $nombre;
    private ?string $descripcion;
    private ?string $decano;

    public function __construct(
        int $idFacultad,
        string $nombre,
        ?string $descripcion,
        ?string $decano
    ) {
        if ($idFacultad <= 0) {
            throw new InvalidArgumentException(
                'El ID de la facultad debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre de la facultad es obligatorio'
            );
        }

        $this->idFacultad = $idFacultad;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->decano = $decano;
    }

    public function getIdFacultad(): int
    {
        return $this->idFacultad;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getDecano(): ?string
    {
        return $this->decano;
    }
}