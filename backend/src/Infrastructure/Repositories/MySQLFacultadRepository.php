<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Facultad;
use App\Domain\Repositories\FacultadRepositoryInterface;
use PDO;

class MySQLFacultadRepository implements FacultadRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(Facultad $facultad): int
    {
        $sql = "INSERT INTO facultades (codigo, nombre, created_at) VALUES (:codigo, :nombre, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo' => $facultad->getCodigo(),
            ':nombre' => $facultad->getNombre(),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function buscarPorId(int $id): ?Facultad
    {
        $sql = "SELECT id, codigo, nombre, created_at FROM facultades WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorCodigo(string $codigo): ?Facultad
    {
        $sql = "SELECT id, codigo, nombre, created_at FROM facultades WHERE codigo = :codigo LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listar(): array
    {
        $sql = "SELECT id, codigo, nombre, created_at FROM facultades ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): Facultad
    {
        return new Facultad(
            (string)$row['codigo'],
            (string)$row['nombre'],
            (int)$row['id'],
            $row['created_at'] ?? null
        );
    }
}
