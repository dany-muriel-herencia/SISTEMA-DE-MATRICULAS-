<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Estudiante;
use App\Domain\Entities\Matricula;
use App\Domain\Entities\MatriculaDetalle;
use App\Domain\Entities\PeriodoAcademico;
use App\Domain\Entities\Usuario;
use App\Domain\Repositories\MatriculaRepositoryInterface;
use App\Domain\ValueObjects\CodigoMatricula;
use App\Domain\ValueObjects\Dni;
use App\Domain\ValueObjects\Email;
use PDO;

class MySQLMatriculaRepository implements MatriculaRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(Matricula $matricula): int
    {
        $sql = "INSERT INTO matriculas (estudiante_id, periodo_id, codigo_matricula, fecha_matricula, total_creditos, estado, created_at, updated_at)
                VALUES (:estudiante_id, :periodo_id, :codigo_matricula, :fecha_matricula, :total_creditos, :estado, NOW(), NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estudiante_id' => $matricula->getEstudianteId(),
            ':periodo_id' => $matricula->getPeriodoId(),
            ':codigo_matricula' => $matricula->getCodigoMatricula()->getValue(),
            ':fecha_matricula' => $matricula->getFechaMatricula(),
            ':total_creditos' => $matricula->getTotalCreditos(),
            ':estado' => $matricula->getEstado(),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function guardarDetalle(MatriculaDetalle $detalle): int
    {
        $sql = "INSERT INTO matricula_detalles (matricula_id, seccion_id, creditos, estado_curso)
                VALUES (:matricula_id, :seccion_id, :creditos, :estado_curso)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':matricula_id' => $detalle->getMatriculaId(),
            ':seccion_id' => $detalle->getSeccionId(),
            ':creditos' => $detalle->getCreditos(),
            ':estado_curso' => $detalle->getEstadoCurso(),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function buscarPorId(int $id): ?Matricula
    {
        $sql = "SELECT m.id, m.estudiante_id, m.periodo_id, m.codigo_matricula, m.fecha_matricula, m.total_creditos, m.estado, m.created_at, m.updated_at,
                       p.codigo AS periodo_codigo, p.anio AS periodo_anio, p.semestre AS periodo_semestre, p.fecha_inicio AS periodo_fecha_inicio,
                       p.fecha_fin AS periodo_fecha_fin, p.fecha_inicio_matricula AS periodo_inicio_mat, p.fecha_fin_matricula AS periodo_fin_mat, p.estado AS periodo_estado,
                       e.usuario_id, e.carrera_id, e.plan_estudio_id, e.codigo_estudiante, e.anio_ingreso, e.estado_academico,
                       u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo
                FROM matriculas m
                INNER JOIN periodos_academicos p ON m.periodo_id = p.id
                INNER JOIN estudiantes e ON m.estudiante_id = e.id
                INNER JOIN usuarios u ON e.usuario_id = u.id
                WHERE m.id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $matricula = $this->hydrateMatricula($row);
        $matricula->setDetalles($this->obtenerDetallesPorMatriculaId($id));

        return $matricula;
    }

    public function buscarPorCodigo(string $codigo): ?Matricula
    {
        $sql = "SELECT m.id, m.estudiante_id, m.periodo_id, m.codigo_matricula, m.fecha_matricula, m.total_creditos, m.estado, m.created_at, m.updated_at,
                       p.codigo AS periodo_codigo, p.anio AS periodo_anio, p.semestre AS periodo_semestre, p.fecha_inicio AS periodo_fecha_inicio,
                       p.fecha_fin AS periodo_fecha_fin, p.fecha_inicio_matricula AS periodo_inicio_mat, p.fecha_fin_matricula AS periodo_fin_mat, p.estado AS periodo_estado,
                       e.usuario_id, e.carrera_id, e.plan_estudio_id, e.codigo_estudiante, e.anio_ingreso, e.estado_academico,
                       u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo
                FROM matriculas m
                INNER JOIN periodos_academicos p ON m.periodo_id = p.id
                INNER JOIN estudiantes e ON m.estudiante_id = e.id
                INNER JOIN usuarios u ON e.usuario_id = u.id
                WHERE m.codigo_matricula = :codigo LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $matricula = $this->hydrateMatricula($row);
        $matricula->setDetalles($this->obtenerDetallesPorMatriculaId((int)$row['id']));

        return $matricula;
    }

    public function buscarPorEstudianteYPeriodo(int $estudianteId, int $periodoId): ?Matricula
    {
        $sql = "SELECT m.id, m.estudiante_id, m.periodo_id, m.codigo_matricula, m.fecha_matricula, m.total_creditos, m.estado, m.created_at, m.updated_at,
                       p.codigo AS periodo_codigo, p.anio AS periodo_anio, p.semestre AS periodo_semestre, p.fecha_inicio AS periodo_fecha_inicio,
                       p.fecha_fin AS periodo_fecha_fin, p.fecha_inicio_matricula AS periodo_inicio_mat, p.fecha_fin_matricula AS periodo_fin_mat, p.estado AS periodo_estado,
                       e.usuario_id, e.carrera_id, e.plan_estudio_id, e.codigo_estudiante, e.anio_ingreso, e.estado_academico,
                       u.rol_id, u.dni, u.email, u.password_hash, u.nombre, u.apellido, u.telefono, u.activo
                FROM matriculas m
                INNER JOIN periodos_academicos p ON m.periodo_id = p.id
                INNER JOIN estudiantes e ON m.estudiante_id = e.id
                INNER JOIN usuarios u ON e.usuario_id = u.id
                WHERE m.estudiante_id = :estudiante_id AND m.periodo_id = :periodo_id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':estudiante_id' => $estudianteId,
            ':periodo_id' => $periodoId,
        ]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $matricula = $this->hydrateMatricula($row);
        $matricula->setDetalles($this->obtenerDetallesPorMatriculaId((int)$row['id']));

        return $matricula;
    }

    public function anular(int $id): bool
    {
        $sql = "UPDATE matriculas 
                SET estado = 'ANULADA', updated_at = NOW() 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $res = $stmt->execute([':id' => $id]);

        $sqlDetalles = "UPDATE matricula_detalles 
                        SET estado_curso = 'RETIRADO' 
                        WHERE matricula_id = :id";
        $stmtDetalles = $this->db->prepare($sqlDetalles);
        $stmtDetalles->execute([':id' => $id]);

        return $res;
    }

    public function verificarCupoSeccionConBloqueo(int $seccionId): int
    {
        $sql = "SELECT vacantes_disponibles FROM secciones WHERE id = :id FOR UPDATE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $seccionId]);
        $val = $stmt->fetchColumn();

        return $val !== false ? (int)$val : 0;
    }

    public function decrementarCupoSeccion(int $seccionId): bool
    {
        $sql = "UPDATE secciones 
                SET vacantes_disponibles = vacantes_disponibles - 1 
                WHERE id = :id AND vacantes_disponibles > 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $seccionId]);
    }

    public function incrementarCupoSeccion(int $seccionId): bool
    {
        $sql = "UPDATE secciones 
                SET vacantes_disponibles = LEAST(vacantes_disponibles + 1, capacidad_maxima) 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $seccionId]);
    }

    public function verificarCruceHorarios(array $seccionIds): array
    {
        if (count($seccionIds) < 2) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($seccionIds), '?'));
        $sql = "SELECT 
                    h1.seccion_id AS seccion1_id,
                    h2.seccion_id AS seccion2_id,
                    h1.dia_semana,
                    h1.hora_inicio,
                    h1.hora_fin
                FROM horarios h1
                INNER JOIN horarios h2 
                    ON h1.dia_semana = h2.dia_semana 
                    AND h1.seccion_id < h2.seccion_id
                    AND h1.hora_inicio < h2.hora_fin 
                    AND h1.hora_fin > h2.hora_inicio
                WHERE h1.seccion_id IN ({$placeholders}) 
                  AND h2.seccion_id IN ({$placeholders})";

        $params = array_merge($seccionIds, $seccionIds);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function listarPorEstudiante(int $estudianteId): array
    {
        $sql = "SELECT id, estudiante_id, periodo_id, codigo_matricula, fecha_matricula, total_creditos, estado, created_at, updated_at
                FROM matriculas 
                WHERE estudiante_id = :estudiante_id 
                ORDER BY id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':estudiante_id' => $estudianteId]);
        $rows = $stmt->fetchAll();

        $matriculas = [];
        foreach ($rows as $row) {
            $m = $this->buscarPorId((int)$row['id']);
            if ($m) {
                $matriculas[] = $m;
            }
        }
        return $matriculas;
    }

    public function listarPorPeriodo(int $periodoId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id FROM matriculas WHERE periodo_id = :periodo_id ORDER BY id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':periodo_id', $periodoId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $matriculas = [];
        foreach ($ids as $id) {
            $m = $this->buscarPorId((int)$id);
            if ($m) {
                $matriculas[] = $m;
            }
        }
        return $matriculas;
    }

    private function obtenerDetallesPorMatriculaId(int $matriculaId): array
    {
        $sql = "SELECT md.id, md.matricula_id, md.seccion_id, md.creditos, md.estado_curso
                FROM matricula_detalles md
                WHERE md.matricula_id = :matricula_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':matricula_id' => $matriculaId]);
        $rows = $stmt->fetchAll();

        return array_map(function (array $row) {
            return new MatriculaDetalle(
                (int)$row['matricula_id'],
                (int)$row['seccion_id'],
                (int)$row['creditos'],
                (string)$row['estado_curso'],
                null,
                (int)$row['id']
            );
        }, $rows);
    }

    private function hydrateMatricula(array $row): Matricula
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

        $estudiante = new Estudiante(
            (int)$row['usuario_id'],
            (int)$row['carrera_id'],
            (int)$row['plan_estudio_id'],
            (string)$row['codigo_estudiante'],
            (int)$row['anio_ingreso'],
            (string)$row['estado_academico'],
            $usuario,
            (int)$row['estudiante_id']
        );

        $periodo = new PeriodoAcademico(
            (string)$row['periodo_codigo'],
            (int)$row['periodo_anio'],
            (string)$row['periodo_semestre'],
            (string)$row['periodo_fecha_inicio'],
            (string)$row['periodo_fecha_fin'],
            (string)$row['periodo_inicio_mat'],
            (string)$row['periodo_fin_mat'],
            (string)$row['periodo_estado'],
            (int)$row['periodo_id']
        );

        return new Matricula(
            (int)$row['estudiante_id'],
            (int)$row['periodo_id'],
            new CodigoMatricula((string)$row['codigo_matricula']),
            (string)$row['fecha_matricula'],
            (int)$row['total_creditos'],
            (string)$row['estado'],
            [],
            $estudiante,
            $periodo,
            (int)$row['id'],
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
