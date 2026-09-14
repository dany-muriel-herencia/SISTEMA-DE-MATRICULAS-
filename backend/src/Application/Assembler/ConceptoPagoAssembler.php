<?php

declare(strict_types=1);

namespace App\Application\Assembler;

use App\Dominio\Entidades\ConceptoPago;

/**
 * Convierte ConceptoPago → array para respuestas JSON.
 * Evita agregar toArray() al dominio.
 */
final class ConceptoPagoAssembler
{
    public static function toArray(ConceptoPago $concepto): array
    {
        return [
            'id_concepto'  => $concepto->getIdConcepto(),
            'nombre'       => $concepto->getNombre(),
            'descripcion'  => $concepto->getDescripcion(),
            'monto'        => $concepto->getMonto(),
            'obligatorio'  => $concepto->esObligatorio(),
        ];
    }

    /** @param ConceptoPago[] $conceptos */
    public static function toArrayList(array $conceptos): array
    {
        return array_map(
            fn(ConceptoPago $c): array => self::toArray($c),
            $conceptos
        );
    }
}
