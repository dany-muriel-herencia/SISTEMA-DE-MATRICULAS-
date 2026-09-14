<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Pago;

use App\Application\Assembler\ComprobantePagoAssembler;
use App\Application\Assembler\PagoAssembler;
use App\Dominio\Repositorios\ComprobantePagoRepositorio;
use App\Dominio\Repositorios\PagoRepositorio;
use DomainException;

/**
 * CU-48: Consultar el historial financiero del estudiante.
 * Actor: Estudiante, Tesorería, Administrador.
 *
 * Retorna todos los pagos del estudiante enriquecidos con
 * su comprobante (si existe) y el estado derivado.
 */
class ConsultarHistorialFinanciero
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
     * Historial completo de pagos del estudiante con estado y comprobante.
     */
    public function ejecutar(int $idEstudiante): array
    {
        if ($idEstudiante <= 0) {
            throw new DomainException('El ID del estudiante debe ser mayor que cero.');
        }

        $pagos    = $this->pagoRepo->buscarPorEstudiante($idEstudiante);
        $historial = [];
        $totalPagado = 0.0;

        foreach ($pagos as $pago) {
            $comprobante = $this->comprobanteRepo->buscarPorPago($pago->getIdPago());

            // Derivar estado sin campo estado en dominio
            if ($pago->getMetodoPago() === 'ANULADO') {
                $estado = 'ANULADO';
            } elseif ($comprobante !== null) {
                $estado = 'PAGADO';
                $totalPagado += $pago->getMonto();
            } else {
                $estado = 'PENDIENTE';
            }

            $entrada = PagoAssembler::toArray($pago, $estado);
            $entrada['comprobante'] = $comprobante !== null
                ? ComprobantePagoAssembler::toArray($comprobante)
                : null;

            $historial[] = $entrada;
        }

        return [
            'id_estudiante' => $idEstudiante,
            'total_pagado'  => round($totalPagado, 2),
            'total_pagos'   => count($historial),
            'historial'     => $historial,
        ];
    }
}
