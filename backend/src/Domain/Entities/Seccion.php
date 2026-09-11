<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class Seccion
{
    private int $idSeccion;
    private string $codigo;
    private int $vacantes;
    private int $vacantesDisponibles;
    private string $periodo;

    public function __construct(
        int $idSeccion,
        string $codigo,
        int $vacantes,
        int $vacantesDisponibles,
        string $periodo
    ) {
        if ($idSeccion <= 0) {
            throw new InvalidArgumentException(
                'El ID de la sección debe ser mayor que cero'
            );
        }

        if (empty(trim($codigo))) {
            throw new InvalidArgumentException(
                'El código de la sección es obligatorio'
            );
        }

        if ($vacantes <= 0) {
            throw new InvalidArgumentException(
                'La cantidad de vacantes debe ser mayor que cero'
            );
        }

        if ($vacantesDisponibles < 0) {
            throw new InvalidArgumentException(
                'Las vacantes disponibles no pueden ser negativas'
            );
        }

        if ($vacantesDisponibles > $vacantes) {
            throw new InvalidArgumentException(
                'Las vacantes disponibles no pueden superar las vacantes totales'
            );
        }

        $this->idSeccion = $idSeccion;
        $this->codigo = $codigo;
        $this->vacantes = $vacantes;
        $this->vacantesDisponibles = $vacantesDisponibles;
        $this->periodo = $periodo;
    }

    public function getIdSeccion(): int
    {
        return $this->idSeccion;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getVacantes(): int
    {
        return $this->vacantes;
    }

    public function getVacantesDisponibles(): int
    {
        return $this->vacantesDisponibles;
    }

    public function getPeriodo(): string
    {
        return $this->periodo;
    }
}