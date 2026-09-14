<?php

declare(strict_types=1);

namespace App\Application\DTO;

use InvalidArgumentException;

/**
 * DTO para CU-43: Registrar un concepto de pago.
 */
final class RegistrarConceptoPagoDTO
{
    private string $nombre;
    private string $descripcion;
    private float  $monto;
    private bool   $obligatorio;

    public function __construct(
        string $nombre,
        string $descripcion,
        float  $monto,
        bool   $obligatorio
    ) {
        if (empty(trim($nombre))) {
            throw new InvalidArgumentException('El nombre del concepto es obligatorio.');
        }
        if ($monto < 0) {
            throw new InvalidArgumentException('El monto no puede ser negativo.');
        }

        $this->nombre      = trim($nombre);
        $this->descripcion = trim($descripcion);
        $this->monto       = $monto;
        $this->obligatorio = $obligatorio;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['nombre']      ?? ''),
            (string) ($data['descripcion'] ?? ''),
            (float)  ($data['monto']       ?? 0),
            (bool)   ($data['obligatorio'] ?? false)
        );
    }

    public function getNombre(): string      { return $this->nombre; }
    public function getDescripcion(): string { return $this->descripcion; }
    public function getMonto(): float        { return $this->monto; }
    public function esObligatorio(): bool    { return $this->obligatorio; }
}
