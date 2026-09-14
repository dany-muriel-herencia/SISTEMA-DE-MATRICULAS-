<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\CasoDeUso\Auditoria\ConsultarHistorialAuditoria;
use App\Application\CasoDeUso\Auditoria\RegistrarAuditoria;
use App\Application\DTO\RegistrarAuditoriaDTO;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador del Módulo de Auditoría.
 *
 * CU-56 / CU-57 → registrar()
 * CU-58         → listar(), listarPorUsuario(), buscar()
 */
class AuditoriaController
{
    private RegistrarAuditoria $registrarAuditoria;
    private ConsultarHistorialAuditoria $consultarHistorial;

    public function __construct(
        RegistrarAuditoria $registrarAuditoria,
        ConsultarHistorialAuditoria $consultarHistorial
    ) {
        $this->registrarAuditoria = $registrarAuditoria;
        $this->consultarHistorial = $consultarHistorial;
    }

    /**
     * POST /api/auditoria
     * CU-56 + CU-57: Registra una operación realizada por un usuario,
     * guardando fecha/hora, usuario, acción y datos afectados.
     */
    public function registrar(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];

            // Si no viene la IP en el body, se toma del servidor
            if (empty($input['ip'])) {
                $input['ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            }

            $dto       = RegistrarAuditoriaDTO::fromArray($input);
            $auditoria = $this->registrarAuditoria->ejecutar($dto);

            ApiResponse::created(
                $auditoria->toArray(),
                'Operación registrada en auditoría correctamente.'
            );
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar auditoría: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/auditoria
     * CU-58: Lista todo el historial de auditoría (solo Administrador).
     */
    public function listar(): void
    {
        try {
            $registros = $this->consultarHistorial->listarTodo();
            $data      = array_map(fn($a) => $a->toArray(), $registros);

            ApiResponse::success($data, 'Historial de auditoría obtenido correctamente.');
        } catch (Throwable $e) {
            ApiResponse::error('Error al obtener historial: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/auditoria/{id}
     * CU-58: Busca un registro de auditoría por ID.
     */
    public function buscar(int $id): void
    {
        try {
            $auditoria = $this->consultarHistorial->buscarPorId($id);
            ApiResponse::success($auditoria->toArray(), 'Registro de auditoría encontrado.');
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al buscar registro: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/auditoria/usuario/{id}
     * CU-58: Lista el historial de auditoría de un usuario específico.
     */
    public function listarPorUsuario(int $idUsuario): void
    {
        try {
            $registros = $this->consultarHistorial->listarPorUsuario($idUsuario);
            $data      = array_map(fn($a) => $a->toArray(), $registros);

            ApiResponse::success(
                $data,
                "Historial de auditoría del usuario {$idUsuario} obtenido correctamente."
            );
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al listar historial: ' . $e->getMessage(), 500);
        }
    }
}
