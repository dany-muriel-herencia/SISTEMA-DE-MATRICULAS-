<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Docente;
use App\Domain\Entities\Usuario;
use App\Domain\Repositories\DocenteRepositoryInterface;
use App\Domain\ValueObjects\Dni;
use App\Domain\ValueObjects\Email;
use PDO;

class MySQLDocenteRepository implements DocenteRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(Docente $docente): int
    {
        // En la base de datos actual los docentes son usuarios con rol DOCENTE (rol_id = 2)
        // Si existe tabla adicional o se maneja en usuarios:
        return $docente->getUsuarioId();
    }

    public function buscarPorId(int $id): ?Docente
    {
        $sql = "SELECT u.id AS usuario_id, u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo, u.created_at
                FROM usuarios u
                WHERE u.id = :id AND u.rol_id = 2 LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorUsuarioId(int $usuarioId): ?Docente
    {
        return $this->buscarPorId($usuarioId);
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT u.id AS usuario_id, u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo, u.created_at
                FROM usuarios u
                WHERE u.rol_id = 2
                ORDER BY u.apellido ASC, u.nombre ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): Docente
    {
        $usuario = new Usuario(
            (int)$row['rol_id'],
            new Dni((string)$row['dni']),
            new Email((string)$row['email']),
            (string)$row['password_hash'],
            (string)$row['nombre'],
            (string)$row['apellido'],
            $row['telefono'] ? (string)$row['telefono'] : null,
            (bool)$row['activo'],
            (int)$row['usuario_id']
        );

        return new Docente(
            (int)$row['usuario_id'],
            null,
            null,
            $usuario,
            (int)$row['usuario_id'],
            $row['created_at'] ?? null
        );
    }
}
