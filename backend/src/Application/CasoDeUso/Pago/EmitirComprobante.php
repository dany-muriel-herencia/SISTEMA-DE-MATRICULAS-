<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Pago;

use App\Application\Assembler\ComprobantePagoAssembler;
use App\Application\DTO\EmitirComprobanteDTO;
use App\Dominio\Entidades\ComprobantePago;
use App\Dominio\Repositorios\ComprobantePagoRepositorio;
use App\Dominio\Repositorios\PagoRepositorio;
use DateTimeImmutable;
use DomainException;

/**
 * CU-47: Emitir comprobantes de pago.
 * Actor principal: Tesorería. Actor secundario: Sistema, Estudiante.
 *
 * Genera y persiste un ComprobantePago asociado a un Pago existente.
 * Si no se proveen serie/número, los genera automáticamente.
 */
class EmitirComprobante
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

    public function ejecutar(EmitirComprobanteDTO $dto): array
    {
        // 1. Verificar que el pago existe
        $pago = $this->pagoRepo->buscarPorId($dto->getIdPago());
        if (!$pago) {
            throw new DomainException(
                "No se encontró el pago con ID {$dto->getIdPago()}."
            );
        }

        // 2. Verificar que no tiene ya un comprobante emitido
        $existente = $this->comprobanteRepo->buscarPorPago($dto->getIdPago());
        if ($existente !== null) {
            throw new DomainException(
                "El pago con ID {$dto->getIdPago()} ya cuenta con el comprobante "
                . "{$existente->getTipo()} serie {$existente->getSerie()} N° {$existente->getNumero()}."
            );
        }

        // 3. Auto-generar serie y número si no se proporcionaron
        $serie  = $dto->getSerie()  !== '' ? $dto->getSerie()  : $this->generarSerie($dto->getTipo());
        $numero = $dto->getNumero() !== '' ? $dto->getNumero() : $this->generarNumero();

        // 4. Crear entidad ComprobantePago (id=1 placeholder; BD asigna id real)
        $comprobante = new ComprobantePago(
            1,
            $dto->getIdPago(),
            $dto->getTipo(),
            $numero,
            $serie,
            new DateTimeImmutable('now')
        );

        $this->comprobanteRepo->guardar($comprobante);

        return ComprobantePagoAssembler::toArray($comprobante);
    }

    /** Genera una serie basada en el tipo y el año actual. */
    private function generarSerie(string $tipo): string
    {
        $prefijo = match ($tipo) {
            'BOLETA'  => 'B',
            'FACTURA' => 'F',
            default   => 'R',
        };
        return $prefijo . date('Y');
    }

    /** Genera un número de comprobante basado en timestamp. */
    private function generarNumero(): string
    {
        return str_pad((string) (time() % 100000), 8, '0', STR_PAD_LEFT);
    }
}
