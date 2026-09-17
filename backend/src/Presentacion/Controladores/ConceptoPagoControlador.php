<?php

declare(strict_types=1);

namespace App\Presentacion\Controladores;

use App\Dominio\Entidades\ConceptoPago;
use App\Dominio\Repositorios\ConceptoPagoRepositorio;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class ConceptoPagoControlador
{
    public function __construct(
        private readonly ConceptoPagoRepositorio $conceptoPagoRepositorio
    ) {}

    public function registrar(array $datos): array
    {
        try {
            $nombre      = trim((string)($datos['nombre'] ?? ''));
            $descripcion = trim((string)($datos['descripcion'] ?? ''));
            $monto       = (float)($datos['monto'] ?? 0.0);
            $obligatorio = isset($datos['obligatorio']) ? (bool)$datos['obligatorio'] : true;

            $concepto = new ConceptoPago(
                1,
                $nombre,
                $descripcion,
                $monto,
                $obligatorio
            );

            $this->conceptoPagoRepositorio->guardar($concepto);

            return [
                'success' => true,
                'message' => 'Concepto de pago registrado correctamente.',
                'data'    => $this->mapConcepto($concepto)
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
                'message' => 'Error al registrar concepto de pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function buscarPorId(int $idConcepto): array
    {
        try {
            $concepto = $this->conceptoPagoRepositorio->buscarPorId($idConcepto);
            if ($concepto === null) {
                return [
                    'success' => false,
                    'message' => "Concepto de pago con ID {$idConcepto} no encontrado.",
                    'data'    => null
                ];
            }

            return [
                'success' => true,
                'message' => 'Concepto de pago encontrado.',
                'data'    => $this->mapConcepto($concepto)
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al buscar concepto de pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function listar(): array
    {
        try {
            $conceptos = $this->conceptoPagoRepositorio->listar();
            $data = array_map(fn(ConceptoPago $c) => $this->mapConcepto($c), $conceptos);

            return [
                'success' => true,
                'message' => 'Conceptos de pago obtenidos correctamente.',
                'data'    => $data
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al listar conceptos de pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    public function actualizar(array $datos): array
    {
        try {
            $idConcepto = (int)($datos['id_concepto'] ?? $datos['id'] ?? 0);
            $existente  = $this->conceptoPagoRepositorio->buscarPorId($idConcepto);
            if ($existente === null) {
                return [
                    'success' => false,
                    'message' => "Concepto de pago con ID {$idConcepto} no encontrado.",
                    'data'    => null
                ];
            }

            $nombre      = trim((string)($datos['nombre'] ?? $existente->getNombre()));
            $descripcion = trim((string)($datos['descripcion'] ?? $existente->getDescripcion()));
            $monto       = isset($datos['monto']) ? (float)$datos['monto'] : $existente->getMonto();
            $obligatorio = isset($datos['obligatorio']) ? (bool)$datos['obligatorio'] : $existente->esObligatorio();

            $actualizado = new ConceptoPago(
                $idConcepto,
                $nombre,
                $descripcion,
                $monto,
                $obligatorio
            );

            $this->conceptoPagoRepositorio->actualizar($actualizado);

            return [
                'success' => true,
                'message' => 'Concepto de pago actualizado correctamente.',
                'data'    => $this->mapConcepto($actualizado)
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
                'message' => 'Error al actualizar concepto de pago: ' . $e->getMessage(),
                'data'    => null
            ];
        }
    }

    private function mapConcepto(ConceptoPago $c): array
    {
        return [
            'id_concepto' => $c->getIdConcepto(),
            'nombre'      => $c->getNombre(),
            'descripcion' => $c->getDescripcion(),
            'monto'       => $c->getMonto(),
            'obligatorio' => $c->esObligatorio()
        ];
    }
}
