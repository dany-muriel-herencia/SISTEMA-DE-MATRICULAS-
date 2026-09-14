<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Usuario;
use App\Domain\Repositories\UsuarioRepositorio;
use App\Domain\ValueObjects\Dni;
use App\Domain\ValueObjects\Email;
use PDO;

class MySQLUsuarioRepository implements UsuarioRepositorio
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(Usuario $usuario): int
    {
        $sql = "INSERT INTO usuarios (rol_id, dni, email, password_hash, nombre, apellido, telefono, activo, created_at, updated_at)
                VALUES (:rol_id, :dni, :email, :password_hash, :nombre, :apellido, :telefono, :activo, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':rol_id' => $usuario->getRolId(),
            ':dni' => $usuario->getDni()->getValue(),
            ':email' => $usuario->getEmail()->getValue(),
            ':password_hash' => $usuario->getPasswordHash(),
            ':nombre' => $usuario->getNombre(),
            ':apellido' => $usuario->getApellido(),
            ':telefono' => $usuario->getTelefono(),
            ':activo' => $usuario->isActivo() ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function actualizar(Usuario $usuario): bool
    {
        $sql = "UPDATE usuarios 
                SET rol_id = :rol_id, dni = :dni, email = :email, nombre = :nombre,
                    apellido = :apellido, telefono = :telefono, activo = :activo, updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $usuario->getId(),
            ':rol_id' => $usuario->getRolId(),
            ':dni' => $usuario->getDni()->getValue(),
            ':email' => $usuario->getEmail()->getValue(),
            ':nombre' => $usuario->getNombre(),
            ':apellido' => $usuario->getApellido(),
            ':telefono' => $usuario->getTelefono(),
            ':activo' => $usuario->isActivo() ? 1 : 0,
        ]);
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $sql = "SELECT id, rol_id, dni, email, password_hash, nombre, apellido, telefono, activo, created_at, updated_at
                FROM usuarios WHERE id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorEmail(Email $email): ?Usuario
    {
        $sql = "SELECT id, rol_id, dni, email, password_hash, nombre, apellido, telefono, activo, created_at, updated_at
                FROM usuarios WHERE email = :email LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email->getValue()]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorDni(Dni $dni): ?Usuario
    {
        $sql = "SELECT id, rol_id, dni, email, password_hash, nombre, apellido, telefono, activo, created_at, updated_at
                FROM usuarios WHERE dni = :dni LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':dni' => $dni->getValue()]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id, rol_id, dni, email, password_hash, nombre, apellido, telefono, activo, created_at, updated_at
                FROM usuarios ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function eliminar(int $id): bool
    {
        $sql = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    private function hydrate(array $row): Usuario
    {
        return new Usuario(
            (int)$row['rol_id'],
            new Dni((string)$row['dni']),
            new Email((string)$row['email']),
            (string)$row['password_hash'],
            (string)$row['nombre'],
            (string)$row['apellido'],
            $row['telefono'] ? (string)$row['telefono'] : null,
            (bool)$row['activo'],
            (int)$row['id'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}