<?php

declare(strict_types=1);

namespace App\Modules\Finanzas;

use App\Core\Database;
use PDO;

/**
 * M08: Repositorio de Pagos, Conceptos TUPA y Tesorería (ISSUE-09 Franklin).
 * Invoca sp_registrar_pago y fn_calcular_saldo_deuda.
 */
final class FinanceRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function listarConceptos(): array
    {
        return $this->pdo->query('SELECT * FROM conceptos_pago WHERE vigente = 1 ORDER BY codigo_tupa ASC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarDeudasEstudiante(int $idEstudiante): array
    {
        $sql = <<<'SQL'
            SELECT d.id_deuda, d.id_estudiante, d.id_periodo, p.codigo_periodo,
                   cp.codigo_tupa, cp.nombre AS concepto, cp.tipo,
                   d.monto, fn_calcular_saldo_deuda(d.id_deuda) AS saldo_pendiente,
                   d.estado, d.fecha_vencimiento
            FROM deudas_estudiante d
            INNER JOIN conceptos_pago cp ON cp.id_concepto = d.id_concepto
            INNER JOIN periodos_academicos p ON p.id_periodo = d.id_periodo
            WHERE d.id_estudiante = :id_estudiante
            ORDER BY d.fecha_vencimiento ASC
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_estudiante' => $idEstudiante]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarPago(
        int $idDeuda,
        ?int $idAdmin,
        float $monto,
        string $metodo,
        string $nroOperacion
    ): array {
        $res = Database::callStoredProcedure(
            'CALL sp_registrar_pago(?, ?, ?, ?, ?, @p_codigo_resp, @p_mensaje)',
            [$idDeuda, $idAdmin, $monto, $metodo, $nroOperacion],
            [
                'codigo' => '@p_codigo_resp',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }
}
