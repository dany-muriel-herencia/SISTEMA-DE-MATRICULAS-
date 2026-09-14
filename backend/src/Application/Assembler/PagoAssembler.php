<?php

declare(strict_types=1);

namespace App\Application\Assembler;

use App\Dominio\Entidades\Pago;

/**
 * Convierte Pago → array para respuestas JSON.
 * Evita agregar toArray() al dominio.
 */
final class PagoAssembler
{
    public static function toArray(Pago $pago, ?string $estado = null): array
    {
        $data = [
            'id_pago'       => $pago->getIdPago(),
            'id_estudiante' => $pago->getIdEstudiante(),
            'id_concepto'   => $pago->getIdConcepto(),
            'fecha_pago'    => $pago->getFechaPago()->format('Y-m-d H:i:s'),
            'monto'         => $pago->getMonto(),
            'metodo_pago'   => $pago->getMetodoPago(),
        ];

        // El estado se enriquece desde la capa de aplicación
        // (no existe en la entidad: se deriva de si existe comprobante)
        if ($estado !== null) {
            $data['estado'] = $estado;
        }

        return $data;
    }

    /** @param Pago[] $pagos */
    public static function toArrayList(array $pagos): array
    {
        return array_map(
            fn(Pago $p): array => self::toArray($p),
            $pagos
        );
    }
}
