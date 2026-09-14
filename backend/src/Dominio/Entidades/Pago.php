<?php

declare(strict_types=1);

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class Pago
{
    private int $idPago;
    private int $idEstudiante;
    private int $idConcepto;
    private DateTimeImmutable $fechaPago;
    private float $monto;
    private string $metodoPago;

    public function __construct(
        int $idPago,
        int $idEstudiante,
        int $idConcepto,
        DateTimeImmutable $fechaPago,
        float $monto,
        string $metodoPago
    ) {
        if ($idPago <= 0) {
            throw new InvalidArgumentException(
                'El ID del pago debe ser mayor que cero'
            );
        }

        if ($idEstudiante <= 0) {
            throw new InvalidArgumentException(
                'El ID del estudiante debe ser mayor que cero'
            );
        }

        if ($idConcepto <= 0) {
            throw new InvalidArgumentException(
                'El ID del concepto de pago debe ser mayor que cero'
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
        $this->idEstudiante = $idEstudiante;
        $this->idConcepto = $idConcepto;
        $this->fechaPago = $fechaPago;
        $this->monto = $monto;
        $this->metodoPago = $metodoPago;
    }

    public function getIdPago(): int
    {
        return $this->idPago;
    }

    public function getIdEstudiante(): int
    {
        return $this->idEstudiante;
    }

    public function getIdConcepto(): int
    {
        return $this->idConcepto;
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