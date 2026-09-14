<?php

declare(strict_types=1);

namespace App\Application\DTO;

use InvalidArgumentException;

/**
 * DTO para CU-44 (GenerarOrdenPago) y CU-45 (RegistrarPago).
 * Encapsula los datos necesarios para registrar un pago.
 */
final class RegistrarPagoDTO
{
    private int    $idEstudiante;
    private int    $idConcepto;
    private float  $monto;
    private string $metodoPago;

    public function __construct(
        int    $idEstudiante,
        int    $idConcepto,
        float  $monto,
        string $metodoPago
    ) {
        if ($idEstudiante <= 0) {
            throw new InvalidArgumentException('El ID del estudiante es obligatorio.');
        }
        if ($idConcepto <= 0) {
            throw new InvalidArgumentException('El ID del concepto de pago es obligatorio.');
        }
        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor que cero.');
        }
        if (empty(trim($metodoPago))) {
            throw new InvalidArgumentException('El método de pago es obligatorio.');
        }

        $this->idEstudiante = $idEstudiante;
        $this->idConcepto   = $idConcepto;
        $this->monto        = $monto;
        $this->metodoPago   = trim(strtoupper($metodoPago));
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)    ($data['id_estudiante'] ?? 0),
            (int)    ($data['id_concepto']   ?? 0),
            (float)  ($data['monto']         ?? 0),
            (string) ($data['metodo_pago']   ?? '')
        );
    }

    public function getIdEstudiante(): int  { return $this->idEstudiante; }
    public function getIdConcepto(): int    { return $this->idConcepto; }
    public function getMonto(): float       { return $this->monto; }
    public function getMetodoPago(): string { return $this->metodoPago; }
}
