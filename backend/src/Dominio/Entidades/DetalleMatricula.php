<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class DetalleMatricula
{
    private int $idDetalle;
    private int $idMatricula;
    private int $idSeccion;
    private string $estado;

    public function __construct(
        int $idDetalle,
        int $idMatricula,
        int $idSeccion,
        string $estado
    ) {
        if ($idDetalle < 0) {
            throw new InvalidArgumentException(
                'El ID del detalle no puede ser negativo'
            );
        }

        if ($idMatricula < 0) {
            throw new InvalidArgumentException(
                'El ID de la matrícula no puede ser negativo'
            );
        }

        if ($idSeccion <= 0) {
            throw new InvalidArgumentException(
                'El ID de la sección debe ser mayor que cero'
            );
        }

        if (empty(trim($estado))) {
            throw new InvalidArgumentException(
                'El estado del detalle es obligatorio'
            );
        }

        $this->idDetalle = $idDetalle;
        $this->idMatricula = $idMatricula;
        $this->idSeccion = $idSeccion;
        $this->estado = $estado;
    }

    public function getIdDetalle(): int
    {
        return $this->idDetalle;
    }

    public function setIdDetalle(int $idDetalle): void
    {
        if ($idDetalle <= 0) {
            throw new InvalidArgumentException(
                'El ID del detalle debe ser mayor que cero'
            );
        }

        $this->idDetalle = $idDetalle;
    }

    public function getIdMatricula(): int
    {
        return $this->idMatricula;
    }

    public function setIdMatricula(int $idMatricula): void
    {
        if ($idMatricula <= 0) {
            throw new InvalidArgumentException(
                'El ID de la matrícula debe ser mayor que cero'
            );
        }

        $this->idMatricula = $idMatricula;
    }

    public function getIdSeccion(): int
    {
        return $this->idSeccion;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function toArray(): array
    {
        return [
            'id_detalle' => $this->idDetalle,
            'id_matricula' => $this->idMatricula,
            'id_seccion' => $this->idSeccion,
            'estado' => $this->estado,
        ];
    }
}