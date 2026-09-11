<?php

namespace App\Dominio\Entidades;

use DateTimeImmutable;
use InvalidArgumentException;

class ComprobantePago
{
    private int $idComprobante;
    private string $tipo;
    private string $numero;
    private string $serie;
    private DateTimeImmutable $fechaEmision;

    public function __construct(
        int $idComprobante,
        string $tipo,
        string $numero,
        string $serie,
        DateTimeImmutable $fechaEmision
    ) {
        if ($idComprobante <= 0) {
            throw new InvalidArgumentException(
                'El ID del comprobante debe ser mayor que cero'
            );
        }

        if (empty(trim($tipo))) {
            throw new InvalidArgumentException(
                'El tipo de comprobante es obligatorio'
            );
        }

        if (empty(trim($numero))) {
            throw new InvalidArgumentException(
                'El número del comprobante es obligatorio'
            );
        }

        if (empty(trim($serie))) {
            throw new InvalidArgumentException(
                'La serie del comprobante es obligatoria'
            );
        }

        $this->idComprobante = $idComprobante;
        $this->tipo = $tipo;
        $this->numero = $numero;
        $this->serie = $serie;
        $this->fechaEmision = $fechaEmision;
    }

    public function getIdComprobante(): int
    {
        return $this->idComprobante;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getSerie(): string
    {
        return $this->serie;
    }

    public function getFechaEmision(): DateTimeImmutable
    {
        return $this->fechaEmision;
    }
}