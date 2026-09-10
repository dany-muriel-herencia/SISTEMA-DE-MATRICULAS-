<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Estudiante;
use App\Domain\Entities\Usuario;
use App\Domain\Repositories\EstudianteRepositoryInterface;
use App\Domain\ValueObjects\Dni;
use App\Domain\ValueObjects\Email;
use PDO;

class MySQLEstudianteRepository implements EstudianteRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(Estudiante $estudiante): int
    {
        $sql = "INSERT INTO estudiantes (usuario_id, carrera_id, plan_estudio_id, codigo_estudiante, anio_ingreso, estado_academico, created_at, updated_at)
                VALUES (:usuario_id, :carrera_id, :plan_estudio_id, :codigo_estudiante, :anio_ingreso, :estado_academico, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $estudiante->getUsuarioId(),
            ':carrera_id' => $estudiante->getCarreraId(),
            ':plan_estudio_id' => $estudiante->getPlanEstudioId(),
            ':codigo_estudiante' => $estudiante->getCodigoEstudiante(),
            ':anio_ingreso' => $estudiante->getAnioIngreso(),
            ':estado_academico' => $estudiante->getEstadoAcademico(),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function actualizar(Estudiante $estudiante): bool
    {
        $sql = "UPDATE estudiantes 
                SET carrera_id = :carrera_id, plan_estudio_id = :plan_estudio_id,
                    estado_academico = :estado_academico, updated_at = NOW()
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $estudiante->getId(),
            ':carrera_id' => $estudiante->getCarreraId(),
            ':plan_estudio_id' => $estudiante->getPlanEstudioId(),
            ':estado_academico' => $estudiante->getEstadoAcademico(),
        ]);
    }

    public function buscarPorId(int $id): ?Estudiante
    {
        $sql = "SELECT e.id, e.usuario_id, e.carrera_id, e.plan_estudio_id, e.codigo_estudiante, e.anio_ingreso, e.estado_academico,
                       e.created_at, e.updated_at,
                       u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo
                FROM estudiantes e
                INNER JOIN usuarios u ON e.usuario_id = u.id
                WHERE e.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorUsuarioId(int $usuarioId): ?Estudiante
    {
        $sql = "SELECT e.id, e.usuario_id, e.carrera_id, e.plan_estudio_id, e.codigo_estudiante, e.anio_ingreso, e.estado_academico,
                       e.created_at, e.updated_at,
                       u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo
                FROM estudiantes e
                INNER JOIN usuarios u ON e.usuario_id = u.id
                WHERE e.usuario_id = :usuario_id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorCodigo(string $codigo): ?Estudiante
    {
        $sql = "SELECT e.id, e.usuario_id, e.carrera_id, e.plan_estudio_id, e.codigo_estudiante, e.anio_ingreso, e.estado_academico,
                       e.created_at, e.updated_at,
                       u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo
                FROM estudiantes e
                INNER JOIN usuarios u ON e.usuario_id = u.id
                WHERE e.codigo_estudiante = :codigo LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT e.id, e.usuario_id, e.carrera_id, e.plan_estudio_id, e.codigo_estudiante, e.anio_ingreso, e.estado_academico,
                       e.created_at, e.updated_at,
                       u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo
                FROM estudiantes e
                INNER JOIN usuarios u ON e.usuario_id = u.id
                ORDER BY e.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function obtenerHistorialCursosAprobados(int $estudianteId): array
    {
        $sql = "SELECT DISTINCT s.curso_id
                FROM matricula_detalles md
                INNER JOIN matriculas m ON md.matricula_id = m.id
                INNER JOIN secciones s ON md.seccion_id = s.id
                WHERE m.estudiante_id = :estudiante_id 
                  AND md.estado_curso = 'APROBADO'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estudiante_id' => $estudianteId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function hydrate(array $row): Estudiante
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

        return new Estudiante(
            (int)$row['usuario_id'],
            (int)$row['carrera_id'],
            (int)$row['plan_estudio_id'],
            (string)$row['codigo_estudiante'],
            (int)$row['anio_ingreso'],
            (string)$row['estado_academico'],
            $usuario,
            (int)$row['id'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
