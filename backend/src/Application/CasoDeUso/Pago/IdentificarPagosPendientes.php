<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Pago;

use App\Application\Assembler\PagoAssembler;
use App\Dominio\Repositorios\ComprobantePagoRepositorio;
use App\Dominio\Repositorios\PagoRepositorio;
use DomainException;

/**
 * CU-46: Identificar pagos pendientes, vencidos o anulados.
 * Actor principal: Tesorería. Actor secundario: Sistema.
 *
 * Estrategia sin campo estado en dominio:
 *   - PAGADO   → el pago tiene comprobante emitido
 *   - PENDIENTE → el pago no tiene comprobante y método ≠ 'ANULADO'
 *   - ANULADO  → método de pago = 'ANULADO' (convención de anulación)
 *   - ORDEN    → método de pago = 'ORDEN' sin comprobante (orden generada)
 */
class IdentificarPagosPendientes
{
    private PagoRepositorio            $pagoRepo;
    private ComprobantePagoRepositorio $comprobanteRepo;

    public function __construct(
        PagoRepositorio            $pagoRepo,
        ComprobantePagoRepositorio $comprobanteRepo
    ) {
        $this->pagoRepo        = $pagoRepo;
        $this->comprobanteRepo = $comprobanteRepo;
    }

    /**
     * Retorna todos los pagos del estudiante clasificados por estado.
     */
    public function ejecutar(int $idEstudiante): array
    {
        if ($idEstudiante <= 0) {
            throw new DomainException('El ID del estudiante debe ser mayor que cero.');
        }

        $pagos    = $this->pagoRepo->buscarPorEstudiante($idEstudiante);
        $resultado = [
            'pendientes' => [],
            'pagados'    => [],
            'anulados'   => [],
            'ordenes'    => [],
        ];

        foreach ($pagos as $pago) {
            $comprobante = $this->comprobanteRepo->buscarPorPago($pago->getIdPago());
            $data        = PagoAssembler::toArray($pago);

            if ($pago->getMetodoPago() === 'ANULADO') {
                $data['estado'] = 'ANULADO';
                $resultado['anulados'][] = $data;
            } elseif ($pago->getMetodoPago() === 'ORDEN') {
                $data['estado'] = 'PENDIENTE';
                $resultado['ordenes'][] = $data;
            } elseif ($comprobante !== null) {
                $data['estado'] = 'PAGADO';
                $resultado['pagados'][] = $data;
            } else {
                $data['estado'] = 'PENDIENTE';
                $resultado['pendientes'][] = $data;
            }
        }

        $resultado['total_pendientes'] = count($resultado['pendientes']) + count($resultado['ordenes']);
        $resultado['total_pagados']    = count($resultado['pagados']);
        $resultado['total_anulados']   = count($resultado['anulados']);

        return $resultado;
    }
}
