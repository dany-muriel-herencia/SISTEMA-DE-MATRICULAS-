<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class DetalleMatricula
{
    private int $idDetalle;
    private string $estado;

    public function __construct(
        int $idDetalle,
        string $estado
    ) {
        if ($idDetalle <= 0) {
            throw new InvalidArgumentException(
                'El ID del detalle debe ser mayor que cero'
            );
        }

        if (empty(trim($estado))) {
            throw new InvalidArgumentException(
                'El estado del detalle es obligatorio'
            );
        }

        $this->idDetalle = $idDetalle;
        $this->estado = $estado;
    }

    public function getIdDetalle(): int
    {
        return $this->idDetalle;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }
}