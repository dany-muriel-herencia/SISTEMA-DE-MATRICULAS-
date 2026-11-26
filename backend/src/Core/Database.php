<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexión única centralizada a MySQL 8.0 mediante PDO Nativo (0% ORM).
 * Incluye soporte nativo para invocar Stored Procedures con parámetros OUT
 * y reintento automático ante Deadlocks (1213) o Lock Wait Timeouts (1205).
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $configFile = dirname(__DIR__, 2) . '/config/database.php';
            $config = file_exists($configFile) ? require $configFile : [];

            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=%s',
                $config['driver'] ?? 'mysql',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 3306,
                $config['database'] ?? 'bd_matricula_universitaria',
                $config['charset'] ?? 'utf8mb4'
            );

            $options = $config['options'] ?? [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'] ?? 'root',
                    $config['password'] ?? '',
                    $options
                );
            } catch (PDOException $e) {
                throw new RuntimeException('Error de conexión PDO a MySQL: ' . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return self::$instance;
    }

    /**
     * Ejecuta un Stored Procedure que devuelve variables de sesión OUT (@p_*)
     * con reintento automático ante Deadlock (1213) o Lock Wait Timeout (1205).
     *
     * @param string $callSql Ejemplo: "CALL sp_confirmar_matricula(?, ?, @p_codigo_resp, @p_mensaje)"
     * @param array $inParams Parámetros de entrada posicionales
     * @param array $outVars Ejemplo: ['codigo' => '@p_codigo_resp', 'mensaje' => '@p_mensaje']
     */
    public static function callStoredProcedure(
        string $callSql,
        array $inParams = [],
        array $outVars = [],
        int $maxRetries = 3
    ): array {
        $pdo = self::getConnection();
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                $stmt = $pdo->prepare($callSql);
                $stmt->execute(array_values($inParams));

                $resultSets = [];
                do {
                    if ($stmt->columnCount() > 0) {
                        $resultSets[] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                } while ($stmt->nextRowset());
                $stmt->closeCursor();

                $outResult = [];
                if (!empty($outVars)) {
                    $selectParts = [];
                    foreach ($outVars as $alias => $varName) {
                        $selectParts[] = "{$varName} AS `{$alias}`";
                    }
                    $outStmt = $pdo->query('SELECT ' . implode(', ', $selectParts));
                    $outResult = $outStmt ? ($outStmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
                    if ($outStmt) {
                        $outStmt->closeCursor();
                    }
                }

                // Si el SP capturó un deadlock interno y devolvió ERROR_SQL con 1213/1205
                $msg = (string)($outResult['mensaje'] ?? '');
                if (
                    ($outResult['codigo'] ?? '') === 'ERROR_SQL'
                    && $attempt < $maxRetries
                    && (str_contains($msg, '1213') || str_contains($msg, '1205') || stripos($msg, 'Deadlock') !== false)
                ) {
                    usleep(100000 * $attempt);
                    continue;
                }

                return [
                    'out' => $outResult,
                    'rows' => $resultSets[0] ?? [],
                    'result_sets' => $resultSets,
                ];
            } catch (PDOException $e) {
                $driverCode = $e->errorInfo[1] ?? 0;
                if (in_array($driverCode, [1213, 1205], true) && $attempt < $maxRetries) {
                    usleep(100000 * $attempt);
                    continue;
                }
                throw $e;
            }
        }
    }
}
