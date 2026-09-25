<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Aula
{
    private int $idAula;
    private string $nombre;
    private ?string $ubicacion;
    private int $capacidad;
    private ?string $tipo;
    private bool $disponible;
    private bool $estado;

    public function __construct(
        int $idAula,
        string $nombre,
        ?string $ubicacion,
        int $capacidad,
        ?string $tipo,
        bool $disponible,
        bool $estado
    ) {
        if ($idAula <= 0) {
            throw new InvalidArgumentException(
                'El ID del aula debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre del aula es obligatorio'
            );
        }

        if ($capacidad <= 0) {
            throw new InvalidArgumentException(
                'La capacidad debe ser mayor que cero'
            );
        }

        if ($tipo !== null && empty(trim($tipo))) {
            throw new InvalidArgumentException(
                'El tipo de aula es obligatorio'
            );
        }

        $this->idAula = $idAula;
        $this->nombre = $nombre;
        $this->ubicacion = $ubicacion;
        $this->capacidad = $capacidad;
        $this->tipo = $tipo;
        $this->disponible = $disponible;
        $this->estado = $estado;
    }

    public function getIdAula(): int
    {
        return $this->idAula;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getUbicacion(): ?string
    {
        return $this->ubicacion;
    }

    public function getCapacidad(): int
    {
        return $this->capacidad;
    }

    public function getTipo(): ?string
    {
        return $this->tipo;
    }

    public function getDisponible(): bool
    {
        return $this->disponible;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }
}