<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Escuela;
use App\Domain\Entities\Facultad;
use App\Domain\Repositories\EscuelaRepositoryInterface;
use PDO;

class MySQLEscuelaRepository implements EscuelaRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(Escuela $escuela): int
    {
        $sql = "INSERT INTO carreras (facultad_id, codigo, nombre, duracion_semestres, created_at)
                VALUES (:facultad_id, :codigo, :nombre, :duracion_semestres, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':facultad_id' => $escuela->getFacultadId(),
            ':codigo' => $escuela->getCodigo(),
            ':nombre' => $escuela->getNombre(),
            ':duracion_semestres' => $escuela->getDuracionSemestres(),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function buscarPorId(int $id): ?Escuela
    {
        $sql = "SELECT c.id, c.facultad_id, c.codigo, c.nombre, c.duracion_semestres, c.created_at,
                       f.codigo AS facultad_codigo, f.nombre AS facultad_nombre
                FROM carreras c
                INNER JOIN facultades f ON c.facultad_id = f.id
                WHERE c.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorCodigo(string $codigo): ?Escuela
    {
        $sql = "SELECT c.id, c.facultad_id, c.codigo, c.nombre, c.duracion_semestres, c.created_at,
                       f.codigo AS facultad_codigo, f.nombre AS facultad_nombre
                FROM carreras c
                INNER JOIN facultades f ON c.facultad_id = f.id
                WHERE c.codigo = :codigo LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listarPorFacultad(int $facultadId): array
    {
        $sql = "SELECT c.id, c.facultad_id, c.codigo, c.nombre, c.duracion_semestres, c.created_at,
                       f.codigo AS facultad_codigo, f.nombre AS facultad_nombre
                FROM carreras c
                INNER JOIN facultades f ON c.facultad_id = f.id
                WHERE c.facultad_id = :facultad_id
                ORDER BY c.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':facultad_id' => $facultadId]);
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function listar(): array
    {
        $sql = "SELECT c.id, c.facultad_id, c.codigo, c.nombre, c.duracion_semestres, c.created_at,
                       f.codigo AS facultad_codigo, f.nombre AS facultad_nombre
                FROM carreras c
                INNER JOIN facultades f ON c.facultad_id = f.id
                ORDER BY c.nombre ASC";

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll();

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): Escuela
    {
        $facultad = new Facultad(
            (string)$row['facultad_codigo'],
            (string)$row['facultad_nombre'],
            (int)$row['facultad_id']
        );

        return new Escuela(
            (int)$row['facultad_id'],
            (string)$row['codigo'],
            (string)$row['nombre'],
            (int)$row['duracion_semestres'],
            $facultad,
            (int)$row['id'],
            $row['created_at'] ?? null
        );
    }
}
