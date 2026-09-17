<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\Pago;
use App\Dominio\Repositorios\PagoRepositorio;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class PagoControlador
{
    public function __construct(
        private readonly PagoRepositorio $pagoRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $idEstudiante = (int)($datos['id_estudiante'] ?? 0);
            $idConcepto   = (int)($datos['id_concepto'] ?? 0);
            $monto        = (float)($datos['monto'] ?? 0.0);
            $metodoPago   = trim((string)($datos['metodo_pago'] ?? 'TRANSFERENCIA'));
            $fechaPago    = isset($datos['fecha_pago']) ? new DateTimeImmutable((string)$datos['fecha_pago']) : new DateTimeImmutable();

            $pago = new Pago(
                1,
                $idEstudiante,
                $idConcepto,
                $fechaPago,
                $monto,
                $metodoPago
            );

            $this->pagoRepositorio->guardar($pago);

            return [
                'success' => true,
                'message' => 'Pago registrado correctamente.',
                'data'    => $this->mapPago($pago)
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
                'message' => 'Error al registrar pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idPago): array
    {
        try {
            $pago = $this->pagoRepositorio->buscarPorId($idPago);
            if ($pago === null) {
                return [
                    'success' => false,
                    'message' => "Pago con ID {$idPago} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Pago encontrado.',
                'data'    => $this->mapPago($pago)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listarPorEstudiante(int $idEstudiante): array
    {
        try {
            $pagos = $this->pagoRepositorio->buscarPorEstudiante($idEstudiante);
            $data = array_map(fn(Pago $p) => $this->mapPago($p), $pagos);

            return [
                'success' => true,
                'message' => 'Pagos del estudiante obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar pagos del estudiante: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idPago    = (int)($datos['id_pago'] ?? $datos['id'] ?? 0);
            $existente = $this->pagoRepositorio->buscarPorId($idPago);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Pago con ID {$idPago} no encontrado.",
                    'data'    => null
                ];
            }

            $idEstudiante = (int)($datos['id_estudiante'] ?? $existente->getIdEstudiante());
            $idConcepto   = (int)($datos['id_concepto'] ?? $existente->getIdConcepto());
            $monto        = isset($datos['monto']) ? (float)$datos['monto'] : $existente->getMonto();
            $metodoPago   = trim((string)($datos['metodo_pago'] ?? $existente->getMetodoPago()));
            $fechaPago    = isset($datos['fecha_pago']) ? new DateTimeImmutable((string)$datos['fecha_pago']) : $existente->getFechaPago();

            $actualizado = new Pago(
                $idPago,
                $idEstudiante,
                $idConcepto,
                $fechaPago,
                $monto,
                $metodoPago
            );

            $this->pagoRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Pago actualizado correctamente.',
                'data'    => $this->mapPago($actualizado)
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
                'message' => 'Error al actualizar pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapPago(Pago $p): array
    {
        return [
            'id_pago'       => $p->getIdPago(),
            'id_estudiante' => $p->getIdEstudiante(),
            'id_concepto'   => $p->getIdConcepto(),
            'fecha_pago'    => $p->getFechaPago()->format('Y-m-d H:i:s'),
            'monto'         => $p->getMonto(),
            'metodo_pago'   => $p->getMetodoPago()
        ];
    }
}
