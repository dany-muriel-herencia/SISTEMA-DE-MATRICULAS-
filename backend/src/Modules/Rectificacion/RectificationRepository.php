<?php

declare(strict_types=1);

namespace App\Modules\Rectificacion;

use App\Core\Database;
use PDO;

/**
 * M10: Repositorio de Rectificación de Matrícula (ISSUE-11 Franklin).
 * Invoca sp_solicitar_rectificacion, sp_aprobar_rectificacion y sp_rechazar_rectificacion.
 */
final class RectificationRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function listarPendientes(): array
    {
        return $this->pdo->query('SELECT * FROM v_rectificaciones_pendientes ORDER BY orden_cola ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function solicitar(
        int $idMatricula,
        string $tipoMovimiento,
        int $idSeccionOrigen,
        ?int $idSeccionDestino
    ): array {
        $res = Database::callStoredProcedure(
            'CALL sp_solicitar_rectificacion(?, ?, ?, ?, @p_id_rectificacion, @p_codigo_resp, @p_mensaje)',
            [$idMatricula, $tipoMovimiento, $idSeccionOrigen, $idSeccionDestino],
            [
                'idRectificacion' => '@p_id_rectificacion',
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function aprobar(int $idRectificacion, int $idAdmin): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_aprobar_rectificacion(?, ?, @p_codigo_resp, @p_mensaje)',
            [$idRectificacion, $idAdmin],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }

    public function rechazar(int $idRectificacion, int $idAdmin, string $observaciones): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_rechazar_rectificacion(?, ?, ?, @p_codigo_resp, @p_mensaje)',
            [$idRectificacion, $idAdmin, $observaciones],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }
}
