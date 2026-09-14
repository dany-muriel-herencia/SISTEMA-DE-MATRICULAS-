<?php

declare(strict_types=1);

namespace App\Application\Assembler;

use App\Dominio\Entidades\ComprobantePago;

/**
 * Convierte ComprobantePago → array para respuestas JSON.
 * Evita agregar toArray() al dominio.
 */
final class ComprobantePagoAssembler
{
    public static function toArray(ComprobantePago $comprobante): array
    {
        return [
            'id_comprobante' => $comprobante->getIdComprobante(),
            'id_pago'        => $comprobante->getIdPago(),
            'tipo'           => $comprobante->getTipo(),
            'serie'          => $comprobante->getSerie(),
            'numero'         => $comprobante->getNumero(),
            'fecha_emision'  => $comprobante->getFechaEmision()->format('Y-m-d H:i:s'),
        ];
    }
}
