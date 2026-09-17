<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\ComprobantePago;
use App\Dominio\Repositorios\ComprobantePagoRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class ComprobantePagoControlador
{
    public function __construct(
        private readonly ComprobantePagoRepositorio $comprobantePagoRepositorio
    ) {}

    public function emitir(array $datos): array
    {
        try {
            $idPago = (int)($datos['id_pago'] ?? 0);
            $tipo   = strtoupper(trim((string)($datos['tipo'] ?? 'BOLETA')));
            $numero = trim((string)($datos['numero'] ?? str_pad((string)random_int(1, 999999), 8, '0', STR_PAD_LEFT)));
            $serie  = strtoupper(trim((string)($datos['serie'] ?? 'B001')));
            $fecha  = isset($datos['fecha_emision']) ? new DateTimeImmutable((string)$datos['fecha_emision']) : new DateTimeImmutable();

            $comprobante = new ComprobantePago(
                1,
                $idPago,
                $tipo,
                $numero,
                $serie,
                $fecha
            );

            $this->comprobantePagoRepositorio->guardar($comprobante);

            return [
                'success' => true,
                'message' => 'Comprobante de pago emitido correctamente.',
                'data'    => $this->mapComprobante($comprobante)
            ];
        } catch (InvalidArgumentException | DomainException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al emitir comprobante de pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idComprobante): array
    {
        try {
            $comprobante = $this->comprobantePagoRepositorio->buscarPorId($idComprobante);
            if ($comprobante === null) {
                return [
                    'success' => false,
                    'message' => "Comprobante de pago con ID {$idComprobante} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Comprobante encontrado.',
                'data'    => $this->mapComprobante($comprobante)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar comprobante: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorPago(int $idPago): array
    {
        try {
            $comprobante = $this->comprobantePagoRepositorio->buscarPorPago($idPago);
            if ($comprobante === null) {
                return [
                    'success' => false,
                    'message' => "Comprobante para el pago ID {$idPago} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Comprobante encontrado.',
                'data'    => $this->mapComprobante($comprobante)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar comprobante por pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idComprobante = (int)($datos['id_comprobante'] ?? $datos['id'] ?? 0);
            $existente     = $this->comprobantePagoRepositorio->buscarPorId($idComprobante);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Comprobante con ID {$idComprobante} no encontrado.",
                    'data'    => null
                ];
            }

            $idPago = (int)($datos['id_pago'] ?? $existente->getIdPago());
            $tipo   = strtoupper(trim((string)($datos['tipo'] ?? $existente->getTipo())));
            $numero = trim((string)($datos['numero'] ?? $existente->getNumero()));
            $serie  = strtoupper(trim((string)($datos['serie'] ?? $existente->getSerie())));
            $fecha  = isset($datos['fecha_emision']) ? new DateTimeImmutable((string)$datos['fecha_emision']) : $existente->getFechaEmision();

            $actualizado = new ComprobantePago(
                $idComprobante,
                $idPago,
                $tipo,
                $numero,
                $serie,
                $fecha
            );

            $this->comprobantePagoRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Comprobante de pago actualizado correctamente.',
                'data'    => $this->mapComprobante($actualizado)
            ];
        } catch (InvalidArgumentException | DomainException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar comprobante de pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapComprobante(ComprobantePago $c): array
    {
        return [
            'id_comprobante' => $c->getIdComprobante(),
            'id_pago'        => $c->getIdPago(),
            'tipo'           => $c->getTipo(),
            'serie'          => $c->getSerie(),
            'numero'         => $c->getNumero(),
            'serie_numero'   => "{$c->getSerie()}-{$c->getNumero()}",
            'fecha_emision'  => $c->getFechaEmision()->format('Y-m-d H:i:s')
        ];
    }
}
