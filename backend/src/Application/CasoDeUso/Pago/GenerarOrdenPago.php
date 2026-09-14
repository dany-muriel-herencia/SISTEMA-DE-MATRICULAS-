<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Pago;

use App\Application\DTO\RegistrarPagoDTO;
use App\Dominio\Entidades\Pago;
use App\Dominio\Repositorios\ConceptoPagoRepositorio;
use App\Dominio\Repositorios\EstudianteRepositorio;
use App\Dominio\Repositorios\PagoRepositorio;
use DateTimeImmutable;
use DomainException;

/**
 * CU-44: Generar cuotas u órdenes de pago.
 * Actor principal: Tesorería, Administrador. Actor secundario: Sistema.
 *
 * Genera un registro de pago pendiente (sin comprobante emitido).
 * El "estado pendiente" se deriva de la ausencia de comprobante.
 * El método de pago para una orden es 'ORDEN' por convención.
 */
class GenerarOrdenPago
{
    private PagoRepositorio        $pagoRepo;
    private ConceptoPagoRepositorio $conceptoRepo;
    private EstudianteRepositorio  $estudianteRepo;

    public function __construct(
        PagoRepositorio        $pagoRepo,
        ConceptoPagoRepositorio $conceptoRepo,
        EstudianteRepositorio  $estudianteRepo
    ) {
        $this->pagoRepo       = $pagoRepo;
        $this->conceptoRepo   = $conceptoRepo;
        $this->estudianteRepo = $estudianteRepo;
    }

    /**
     * @return array Datos de la orden generada (estado: PENDIENTE).
     */
    public function ejecutar(RegistrarPagoDTO $dto): array
    {
        // 1. Validar estudiante
        $estudiante = $this->estudianteRepo->buscarPorId($dto->getIdEstudiante());
        if (!$estudiante) {
            throw new DomainException(
                "El estudiante con ID {$dto->getIdEstudiante()} no existe."
            );
        }

        // 2. Validar concepto de pago
        $concepto = $this->conceptoRepo->buscarPorId($dto->getIdConcepto());
        if (!$concepto) {
            throw new DomainException(
                "El concepto de pago con ID {$dto->getIdConcepto()} no existe."
            );
        }

        // 3. Construir entidad Pago (id=1 placeholder; BD asigna id real)
        $pago = new Pago(
            1,
            $dto->getIdEstudiante(),
            $dto->getIdConcepto(),
            new DateTimeImmutable('now'),
            $dto->getMonto(),
            'ORDEN'  // Convención: orden pendiente de pago
        );

        $this->pagoRepo->guardar($pago);

        return [
            'id_estudiante'  => $dto->getIdEstudiante(),
            'id_concepto'    => $dto->getIdConcepto(),
            'concepto'       => $concepto->getNombre(),
            'monto'          => $dto->getMonto(),
            'fecha_generada' => (new DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            'estado'         => 'PENDIENTE',
            'mensaje'        => 'Orden de pago generada. Proceda al pago para obtener su comprobante.',
        ];
    }
}
