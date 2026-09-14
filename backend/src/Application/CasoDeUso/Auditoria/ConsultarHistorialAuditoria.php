<?php

declare(strict_types=1);

namespace App\Application\CasoDeUso\Auditoria;

use App\Dominio\Entidades\Auditoria;
use App\Dominio\Repositorios\AuditoriaRepositorio;
use DomainException;

/**
 * CU-58: Mantener historial de cambios importantes.
 *
 * El Administrador puede consultar el historial completo
 * o filtrarlo por usuario para auditar sus acciones.
 */
class ConsultarHistorialAuditoria
{
    private AuditoriaRepositorio $auditoriaRepo;

    public function __construct(AuditoriaRepositorio $auditoriaRepo)
    {
        $this->auditoriaRepo = $auditoriaRepo;
    }

    /**
     * Lista todo el historial de auditoría (ordenado DESC por fecha).
     *
     * @return Auditoria[]
     */
    public function listarTodo(): array
    {
        return $this->auditoriaRepo->listar();
    }

    /**
     * Lista el historial de auditoría de un usuario específico.
     *
     * @param int $idUsuario
     * @return Auditoria[]
     */
    public function listarPorUsuario(int $idUsuario): array
    {
        if ($idUsuario <= 0) {
            throw new DomainException('El ID de usuario debe ser mayor que cero.');
        }

        return $this->auditoriaRepo->listarPorUsuario($idUsuario);
    }

    /**
     * Busca un registro de auditoría por su ID.
     *
     * @param int $idAuditoria
     * @throws DomainException si no se encuentra el registro.
     */
    public function buscarPorId(int $idAuditoria): Auditoria
    {
        if ($idAuditoria <= 0) {
            throw new DomainException('El ID de auditoría debe ser mayor que cero.');
        }

        $auditoria = $this->auditoriaRepo->buscarPorId($idAuditoria);

        if (!$auditoria) {
            throw new DomainException(
                "No se encontró el registro de auditoría con ID {$idAuditoria}."
            );
        }

        return $auditoria;
    }
}
