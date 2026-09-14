<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\CasoDeUso\Pago\ConsultarConceptosPago;
use App\Application\CasoDeUso\Pago\ConsultarHistorialFinanciero;
use App\Application\CasoDeUso\Pago\EmitirComprobante;
use App\Application\CasoDeUso\Pago\GenerarOrdenPago;
use App\Application\CasoDeUso\Pago\IdentificarPagosPendientes;
use App\Application\CasoDeUso\Pago\RegistrarConceptoPago;
use App\Application\CasoDeUso\Pago\RegistrarPago;
use App\Application\DTO\EmitirComprobanteDTO;
use App\Application\DTO\RegistrarConceptoPagoDTO;
use App\Application\DTO\RegistrarPagoDTO;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador del Módulo de Pagos.
 *
 * CU-43 → registrarConcepto(), listarConceptos()
 * CU-44 → generarOrden()
 * CU-45 → registrarPago()
 * CU-46 → pagosPendientes()
 * CU-47 → emitirComprobante()
 * CU-48 → historialFinanciero()
 */
class PagoController
{
    private RegistrarConceptoPago       $registrarConcepto;
    private ConsultarConceptosPago      $consultarConceptos;
    private GenerarOrdenPago            $generarOrden;
    private RegistrarPago               $registrarPago;
    private IdentificarPagosPendientes  $identificarPendientes;
    private EmitirComprobante           $emitirComprobante;
    private ConsultarHistorialFinanciero $historialFinanciero;

    public function __construct(
        RegistrarConceptoPago       $registrarConcepto,
        ConsultarConceptosPago      $consultarConceptos,
        GenerarOrdenPago            $generarOrden,
        RegistrarPago               $registrarPago,
        IdentificarPagosPendientes  $identificarPendientes,
        EmitirComprobante           $emitirComprobante,
        ConsultarHistorialFinanciero $historialFinanciero
    ) {
        $this->registrarConcepto     = $registrarConcepto;
        $this->consultarConceptos    = $consultarConceptos;
        $this->generarOrden          = $generarOrden;
        $this->registrarPago         = $registrarPago;
        $this->identificarPendientes = $identificarPendientes;
        $this->emitirComprobante     = $emitirComprobante;
        $this->historialFinanciero   = $historialFinanciero;
    }

    // ──────────────────────────────────────────────
    // CU-43: Registrar conceptos de pago
    // ──────────────────────────────────────────────

    /** POST /api/conceptos-pago */
    public function registrarConcepto(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto   = RegistrarConceptoPagoDTO::fromArray($input);
            $data  = $this->registrarConcepto->ejecutar($dto);
            ApiResponse::created($data, "Concepto de pago '{$dto->getNombre()}' registrado correctamente.");
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar concepto de pago: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/conceptos-pago */
    public function listarConceptos(): void
    {
        try {
            $data = $this->consultarConceptos->listar();
            ApiResponse::success($data, 'Conceptos de pago obtenidos correctamente.');
        } catch (Throwable $e) {
            ApiResponse::error('Error al listar conceptos: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/conceptos-pago/{id} */
    public function consultarConcepto(int $id): void
    {
        try {
            $data = $this->consultarConceptos->buscarPorId($id);
            ApiResponse::success($data, 'Concepto de pago obtenido correctamente.');
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al consultar concepto: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────────
    // CU-44: Generar órdenes / cuotas de pago
    // ──────────────────────────────────────────────

    /** POST /api/pagos/orden */
    public function generarOrden(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto   = RegistrarPagoDTO::fromArray($input);
            $data  = $this->generarOrden->ejecutar($dto);
            ApiResponse::created($data, 'Orden de pago generada correctamente.');
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al generar orden de pago: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────────
    // CU-45: Registrar pagos parciales o completos
    // ──────────────────────────────────────────────

    /** POST /api/pagos */
    public function registrarPago(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto   = RegistrarPagoDTO::fromArray($input);
            $data  = $this->registrarPago->ejecutar($dto);
            ApiResponse::created($data, 'Pago registrado correctamente.');
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar pago: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────────
    // CU-46: Identificar pagos pendientes/vencidos/anulados
    // ──────────────────────────────────────────────

    /** GET /api/pagos/pendientes/{idEstudiante} */
    public function pagosPendientes(int $idEstudiante): void
    {
        try {
            $data = $this->identificarPendientes->ejecutar($idEstudiante);
            ApiResponse::success($data, 'Estado de pagos del estudiante obtenido correctamente.');
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al identificar pagos pendientes: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────────
    // CU-47: Emitir comprobantes de pago
    // ──────────────────────────────────────────────

    /** POST /api/pagos/{id}/comprobante */
    public function emitirComprobante(int $idPago): void
    {
        try {
            $input          = json_decode(file_get_contents('php://input'), true) ?? [];
            $input['id_pago'] = $idPago; // el id viene de la URL
            $dto            = EmitirComprobanteDTO::fromArray($input);
            $data           = $this->emitirComprobante->ejecutar($dto);
            ApiResponse::created($data, 'Comprobante emitido correctamente.');
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al emitir comprobante: ' . $e->getMessage(), 500);
        }
    }

    // ──────────────────────────────────────────────
    // CU-48: Consultar historial financiero
    // ──────────────────────────────────────────────

    /** GET /api/pagos/historial/{idEstudiante} */
    public function historialFinanciero(int $idEstudiante): void
    {
        try {
            $data = $this->historialFinanciero->ejecutar($idEstudiante);
            ApiResponse::success($data, 'Historial financiero obtenido correctamente.');
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al consultar historial financiero: ' . $e->getMessage(), 500);
        }
    }
}
