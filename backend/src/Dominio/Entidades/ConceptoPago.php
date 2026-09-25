<?php

namespace App\Dominio\Entidades;

use InvalidArgumentException;

class ConceptoPago
{
    private int $idConcepto;
    private string $nombre;
    private ?string $descripcion;
    private float $monto;
    private bool $obligatorio;

    public function __construct(
        int $idConcepto,
        string $nombre,
        ?string $descripcion,
        float $monto,
        bool $obligatorio
    ) {
        if ($idConcepto <= 0) {
            throw new InvalidArgumentException(
                'El ID del concepto debe ser mayor que cero'
            );
        }

        if (empty(trim($nombre))) {
            throw new InvalidArgumentException(
                'El nombre del concepto es obligatorio'
            );
        }

        if ($monto < 0) {
            throw new InvalidArgumentException(
                'El monto no puede ser negativo'
            );
        }

        $this->idConcepto = $idConcepto;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->monto = $monto;
        $this->obligatorio = $obligatorio;
    }

    public function getIdConcepto(): int
    {
        return $this->idConcepto;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getMonto(): float
    {
        return $this->monto;
    }

    public function esObligatorio(): bool
    {
        return $this->obligatorio;
    }
}