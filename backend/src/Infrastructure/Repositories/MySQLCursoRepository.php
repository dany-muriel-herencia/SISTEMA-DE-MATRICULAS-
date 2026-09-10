<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Curso;
use App\Domain\Entities\Seccion;
use App\Domain\Entities\Usuario;
use App\Domain\Repositories\CursoRepositoryInterface;
use App\Domain\ValueObjects\Dni;
use App\Domain\ValueObjects\Email;
use PDO;

class MySQLCursoRepository implements CursoRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(Curso $curso): int
    {
        $sql = "INSERT INTO cursos (codigo, nombre, creditos, horas_teoricas, horas_practicas, created_at)
                VALUES (:codigo, :nombre, :creditos, :horas_teoricas, :horas_practicas, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo' => $curso->getCodigo(),
            ':nombre' => $curso->getNombre(),
            ':creditos' => $curso->getCreditos(),
            ':horas_teoricas' => $curso->getHorasTeoricas(),
            ':horas_practicas' => $curso->getHorasPracticas(),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function actualizar(Curso $curso): bool
    {
        $sql = "UPDATE cursos 
                SET codigo = :codigo, nombre = :nombre, creditos = :creditos,
                    horas_teoricas = :horas_teoricas, horas_practicas = :horas_practicas
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $curso->getId(),
            ':codigo' => $curso->getCodigo(),
            ':nombre' => $curso->getNombre(),
            ':creditos' => $curso->getCreditos(),
            ':horas_teoricas' => $curso->getHorasTeoricas(),
            ':horas_practicas' => $curso->getHorasPracticas(),
        ]);
    }

    public function buscarPorId(int $id): ?Curso
    {
        $sql = "SELECT id, codigo, nombre, creditos, horas_teoricas, horas_practicas, created_at
                FROM cursos WHERE id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorCodigo(string $codigo): ?Curso
    {
        $sql = "SELECT id, codigo, nombre, creditos, horas_teoricas, horas_practicas, created_at
                FROM cursos WHERE codigo = :codigo LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id, codigo, nombre, creditos, horas_teoricas, horas_practicas, created_at
                FROM cursos ORDER BY nombre ASC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function obtenerPrerrequisitos(int $planEstudioId, int $cursoId): array
    {
        $sql = "SELECT p.curso_requisito_id, c.codigo AS curso_requisito_codigo, c.nombre AS curso_requisito_nombre
                FROM prerrequisitos p
                INNER JOIN plan_cursos pc ON p.plan_curso_id = pc.id
                INNER JOIN cursos c ON p.curso_requisito_id = c.id
                WHERE pc.plan_estudio_id = :plan_estudio_id AND pc.curso_id = :curso_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':plan_estudio_id' => $planEstudioId,
            ':curso_id' => $cursoId,
        ]);

        return $stmt->fetchAll();
    }

    public function buscarSeccionPorId(int $seccionId): ?Seccion
    {
        $sql = "SELECT s.id, s.periodo_id, s.curso_id, s.docente_id, s.letra_seccion, s.capacidad_maxima, s.vacantes_disponibles, s.created_at,
                       c.codigo AS curso_codigo, c.nombre AS curso_nombre, c.creditos, c.horas_teoricas, c.horas_practicas,
                       u.rol_id, u.dni, u.email, u.password_hash, u.nombre AS docente_nombre, u.apellido AS docente_apellido, u.telefono, u.activo
                FROM secciones s
                INNER JOIN cursos c ON s.curso_id = c.id
                LEFT JOIN usuarios u ON s.docente_id = u.id
                WHERE s.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $seccionId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $curso = new Curso(
            (string)$row['curso_codigo'],
            (string)$row['curso_nombre'],
            (int)$row['creditos'],
            (int)$row['horas_teoricas'],
            (int)$row['horas_practicas'],
            (int)$row['curso_id']
        );

        $docente = null;
        if (!empty($row['docente_id'])) {
            $docente = new Usuario(
                (int)$row['rol_id'],
                new Dni((string)$row['dni']),
                new Email((string)$row['email']),
                (string)$row['password_hash'],
                (string)$row['docente_nombre'],
                (string)$row['docente_apellido'],
                $row['telefono'] ? (string)$row['telefono'] : null,
                (bool)$row['activo'],
                (int)$row['docente_id']
            );
        }

        // Obtener horarios asociados
        $horariosSql = "SELECT h.id, h.aula_id, h.dia_semana, h.hora_inicio, h.hora_fin, a.codigo AS aula_codigo, a.pabellon
                        FROM horarios h
                        LEFT JOIN aulas a ON h.aula_id = a.id
                        WHERE h.seccion_id = :seccion_id";
        $hStmt = $this->db->prepare($horariosSql);
        $hStmt->execute([':seccion_id' => $seccionId]);
        $horarios = $hStmt->fetchAll();

        return new Seccion(
            (int)$row['periodo_id'],
            (int)$row['curso_id'],
            (string)$row['letra_seccion'],
            (int)$row['capacidad_maxima'],
            (int)$row['vacantes_disponibles'],
            $row['docente_id'] ? (int)$row['docente_id'] : null,
            $curso,
            $docente,
            $horarios,
            (int)$row['id'],
            $row['created_at'] ?? null
        );
    }

    public function listarOfertaPorPeriodoYCarrera(int $periodoId, int $carreraId): array
    {
        $sql = "SELECT 
                  s.id AS seccion_id,
                  s.periodo_id,
                  s.letra_seccion,
                  s.capacidad_maxima,
                  s.vacantes_disponibles,
                  c.id AS curso_id,
                  c.codigo AS curso_codigo,
                  c.nombre AS curso_nombre,
                  c.creditos,
                  c.horas_teoricas,
                  c.horas_practicas,
                  pc.ciclo,
                  CONCAT(u.nombre, ' ', u.apellido) AS docente_nombre,
                  h.dia_semana,
                  h.hora_inicio,
                  h.hora_fin,
                  a.codigo AS aula_codigo,
                  a.pabellon
                FROM secciones s
                INNER JOIN cursos c ON s.curso_id = c.id
                INNER JOIN plan_cursos pc ON pc.curso_id = c.id
                INNER JOIN planes_estudio pe ON pc.plan_estudio_id = pe.id
                LEFT JOIN usuarios u ON s.docente_id = u.id
                LEFT JOIN horarios h ON h.seccion_id = s.id
                LEFT JOIN aulas a ON h.aula_id = a.id
                WHERE s.periodo_id = :periodo_id 
                  AND pe.carrera_id = :carrera_id
                ORDER BY pc.ciclo ASC, c.nombre ASC, s.letra_seccion ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':periodo_id' => $periodoId,
            ':carrera_id' => $carreraId,
        ]);

        return $stmt->fetchAll();
    }

    private function hydrate(array $row): Curso
    {
        return new Curso(
            (string)$row['codigo'],
            (string)$row['nombre'],
            (int)$row['creditos'],
            (int)($row['horas_teoricas'] ?? 2),
            (int)($row['horas_practicas'] ?? 2),
            (int)$row['id'],
            $row['created_at'] ?? null
        );
    }
}
