<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Escuela
{
    private int $idEscuela;
    private int $idFacultad;
    private string $nombre;
    private ?string $descripcion;
    private ?string $director;

    public function __construct(
        int $idEscuela,
        int $idFacultad,
        string $nombre,
        ?string $descripcion,
        ?string $director
    ) {
        if ($idEscuela <= 0) {
            throw new InvalidArgumentException(
                'El ID de la escuela debe ser mayor que cero'
            );
        }

        if ($idFacultad <= 0) {
            throw new InvalidArgumentException(
                'El ID de la facultad debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre de la escuela es obligatorio'
            );
        }

        $this->idEscuela = $idEscuela;
        $this->idFacultad = $idFacultad;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->director = $director;
    }

    public function getIdEscuela(): int
    {
        return $this->idEscuela;
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

    public function getDirector(): ?string
    {
        return $this->director;
    }
}