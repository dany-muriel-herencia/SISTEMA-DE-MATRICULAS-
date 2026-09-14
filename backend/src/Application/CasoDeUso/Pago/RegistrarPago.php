<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Pago;

use App\Application\Assembler\PagoAssembler;
use App\Application\DTO\RegistrarPagoDTO;
use App\Dominio\Entidades\Pago;
use App\Dominio\Repositorios\ComprobantePagoRepositorio;
use App\Dominio\Repositorios\ConceptoPagoRepositorio;
use App\Dominio\Repositorios\PagoRepositorio;
use DateTimeImmutable;
use DomainException;

/**
 * CU-45: Registrar pagos parciales o completos.
 * Actor principal: Tesorería. Actor secundario: Estudiante, Sistema.
 */
class RegistrarPago
{
    private PagoRepositorio         $pagoRepo;
    private ConceptoPagoRepositorio $conceptoRepo;
    private ComprobantePagoRepositorio $comprobanteRepo;

    public function __construct(
        PagoRepositorio         $pagoRepo,
        ConceptoPagoRepositorio $conceptoRepo,
        ComprobantePagoRepositorio $comprobanteRepo
    ) {
        $this->pagoRepo        = $pagoRepo;
        $this->conceptoRepo    = $conceptoRepo;
        $this->comprobanteRepo = $comprobanteRepo;
    }

    public function ejecutar(RegistrarPagoDTO $dto): array
    {
        // 1. Validar que el concepto de pago existe
        $concepto = $this->conceptoRepo->buscarPorId($dto->getIdConcepto());
        if (!$concepto) {
            throw new DomainException(
                "El concepto de pago con ID {$dto->getIdConcepto()} no existe."
            );
        }

        // 2. Validar pago parcial: el monto no puede superar el monto del concepto
        if ($dto->getMonto() > $concepto->getMonto()) {
            throw new DomainException(
                "El monto pagado ({$dto->getMonto()}) supera el monto del concepto '{$concepto->getNombre()}' ({$concepto->getMonto()})."
            );
        }

        // 3. Determinar si es pago parcial o completo
        $esParcial = $dto->getMonto() < $concepto->getMonto();

        // 4. Crear entidad Pago (id=1 placeholder; BD asigna id real via auto-increment)
        $pago = new Pago(
            1,
            $dto->getIdEstudiante(),
            $dto->getIdConcepto(),
            new DateTimeImmutable('now'),
            $dto->getMonto(),
            $dto->getMetodoPago()
        );

        $this->pagoRepo->guardar($pago);

        return [
            'id_estudiante' => $dto->getIdEstudiante(),
            'id_concepto'   => $dto->getIdConcepto(),
            'concepto'      => $concepto->getNombre(),
            'monto_pagado'  => $dto->getMonto(),
            'monto_total'   => $concepto->getMonto(),
            'tipo_pago'     => $esParcial ? 'PARCIAL' : 'COMPLETO',
            'metodo_pago'   => $dto->getMetodoPago(),
            'fecha_pago'    => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            'estado'        => 'REGISTRADO',
            'mensaje'       => $esParcial
                ? 'Pago parcial registrado. Recuerde abonar el saldo restante.'
                : 'Pago completo registrado correctamente.',
        ];
    }
}
