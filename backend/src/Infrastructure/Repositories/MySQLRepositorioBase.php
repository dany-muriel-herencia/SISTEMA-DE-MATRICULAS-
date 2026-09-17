<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use PDO;
use RuntimeException;

abstract class MySQLRepositorioBase
{
    public function __construct(protected PDO $db) {}

    protected function one(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    protected function all(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function exec(string $sql, array $params = []): void
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    protected function generatedId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    protected function lastInsertId(): int
    {
        return $this->generatedId();
    }

    /**
     * Executes an operation inside a database transaction.
     *
     * @param callable(): mixed $callback
     * @return mixed
     * @throws \Throwable
     */
    protected function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    protected function unsupported(string $message): never
    {
        throw new RuntimeException($message);
    }
}
