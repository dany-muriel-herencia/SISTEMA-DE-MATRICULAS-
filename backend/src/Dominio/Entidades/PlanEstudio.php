<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class PlanEstudio
{
    private int $idPlan;
    private int $idCarrera;
    private string $nombre;
    private DateTimeImmutable $fechaInicio;
    private ?DateTimeImmutable $fechaFin;
    private bool $estado;

    public function __construct(
        int $idPlan,
        int $idCarrera,
        string $nombre,
        DateTimeImmutable $fechaInicio,
        ?DateTimeImmutable $fechaFin,
        bool $estado
    ) {
        if ($idPlan <= 0) {
            throw new InvalidArgumentException(
                'El ID del plan debe ser mayor que cero'
            );
        }

        if ($idCarrera <= 0) {
            throw new InvalidArgumentException(
                'El ID de la carrera debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre del plan de estudio es obligatorio'
            );
        }

        $this->idPlan = $idPlan;
        $this->idCarrera = $idCarrera;
        $this->nombre = $nombre;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->estado = $estado;
    }

    public function getIdPlan(): int
    {
        return $this->idPlan;
    }

    public function getIdCarrera(): int
    {
        return $this->idCarrera;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getFechaInicio(): DateTimeImmutable
    {
        return $this->fechaInicio;
    }

    public function getFechaFin(): ?DateTimeImmutable
    {
        return $this->fechaFin;
    }

    public function getEstado(): bool
    {
        return $this->estado;
    }
}