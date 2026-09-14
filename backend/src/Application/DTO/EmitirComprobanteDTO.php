<?php

declare(strict_types=1);

namespace App\Application\DTO;

use InvalidArgumentException;

/**
 * DTO para CU-47: Emitir comprobante de pago.
 */
final class EmitirComprobanteDTO
{
    private int    $idPago;
    private string $tipo;
    private string $serie;
    private string $numero;

    /** Tipos válidos de comprobante */
    private const TIPOS_VALIDOS = ['BOLETA', 'FACTURA', 'RECIBO'];

    public function __construct(
        int    $idPago,
        string $tipo,
        string $serie  = '',
        string $numero = ''
    ) {
        if ($idPago <= 0) {
            throw new InvalidArgumentException('El ID del pago es obligatorio.');
        }

        $tipo = strtoupper(trim($tipo));
        if (!in_array($tipo, self::TIPOS_VALIDOS, true)) {
            throw new InvalidArgumentException(
                'Tipo de comprobante inválido. Valores permitidos: '
                . implode(', ', self::TIPOS_VALIDOS)
            );
        }

        $this->idPago = $idPago;
        $this->tipo   = $tipo;
        $this->serie  = trim($serie);
        $this->numero = trim($numero);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)    ($data['id_pago'] ?? 0),
            (string) ($data['tipo']    ?? ''),
            (string) ($data['serie']   ?? ''),
            (string) ($data['numero']  ?? '')
        );
    }

    public function getIdPago(): int    { return $this->idPago; }
    public function getTipo(): string   { return $this->tipo; }
    public function getSerie(): string  { return $this->serie; }
    public function getNumero(): string { return $this->numero; }
}
