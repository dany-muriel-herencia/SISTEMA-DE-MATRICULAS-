<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Carrera
{
    private int $idCarrera;
    private string $nombre;
    private string $codigo;
    private int $duracion;
    private bool $estado;

    public function __construct(
        int $idCarrera,
        string $nombre,
        string $codigo,
        int $duracion,
        bool $estado
    ) {
        if ($idCarrera <= 0) {
            throw new InvalidArgumentException(
                'El ID de la carrera debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre de la carrera es obligatorio'
            );
        }

        if (empty(trim($codigo))) {
            throw new InvalidArgumentException(
                'El código de la carrera es obligatorio'
            );
        }

        if ($duracion <= 0) {
            throw new InvalidArgumentException(
                'La duración debe ser mayor que cero'
            );
        }

        $this->idCarrera = $idCarrera;
        $this->nombre = $nombre;
        $this->codigo = $codigo;
        $this->duracion = $duracion;
        $this->estado = $estado;
    }

    public function getIdCarrera(): int
    {
        return $this->idCarrera;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getDuracion(): int
    {
        return $this->duracion;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }
}