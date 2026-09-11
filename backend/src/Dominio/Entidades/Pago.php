<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Pago
{
    private int $idPago;
    private DateTimeImmutable $fechaPago;
    private float $monto;
    private string $metodoPago;

    public function __construct(
        int $idPago,
        DateTimeImmutable $fechaPago,
        float $monto,
        string $metodoPago
    ) {
        if ($idPago <= 0) {
            throw new InvalidArgumentException(
                'El ID del pago debe ser mayor que cero'
            );
        }

        if ($monto <= 0) {
            throw new InvalidArgumentException(
                'El monto del pago debe ser mayor que cero'
            );
        }

        if (empty(trim($metodoPago))) {
            throw new InvalidArgumentException(
                'El método de pago es obligatorio'
            );
        }

        $this->idPago = $idPago;
        $this->fechaPago = $fechaPago;
        $this->monto = $monto;
        $this->metodoPago = $metodoPago;
    }

    public function getIdPago(): int
    {
        return $this->idPago;
    }

    public function getFechaPago(): DateTimeImmutable
    {
        return $this->fechaPago;
    }

    public function getMonto(): float
    {
        return $this->monto;
    }

    public function getMetodoPago(): string
    {
        return $this->metodoPago;
    }
}