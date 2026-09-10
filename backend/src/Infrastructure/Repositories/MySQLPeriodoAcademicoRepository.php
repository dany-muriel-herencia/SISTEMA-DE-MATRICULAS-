<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\PeriodoAcademico;
use App\Domain\Repositories\PeriodoAcademicoRepositoryInterface;
use PDO;

class MySQLPeriodoAcademicoRepository implements PeriodoAcademicoRepositoryInterface
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function guardar(PeriodoAcademico $periodo): int
    {
        $sql = "INSERT INTO periodos_academicos (codigo, anio, semestre, fecha_inicio, fecha_fin, fecha_inicio_matricula, fecha_fin_matricula, estado, created_at)
                VALUES (:codigo, :anio, :semestre, :fecha_inicio, :fecha_fin, :fecha_inicio_matricula, :fecha_fin_matricula, :estado, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo' => $periodo->getCodigo(),
            ':anio' => $periodo->getAnio(),
            ':semestre' => $periodo->getSemestre(),
            ':fecha_inicio' => $periodo->getFechaInicio(),
            ':fecha_fin' => $periodo->getFechaFin(),
            ':fecha_inicio_matricula' => $periodo->getFechaInicioMatricula(),
            ':fecha_fin_matricula' => $periodo->getFechaFinMatricula(),
            ':estado' => $periodo->getEstado(),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function buscarPorId(int $id): ?PeriodoAcademico
    {
        $sql = "SELECT id, codigo, anio, semestre, fecha_inicio, fecha_fin, fecha_inicio_matricula, fecha_fin_matricula, estado, created_at
                FROM periodos_academicos WHERE id = :id LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function buscarPorCodigo(string $codigo): ?PeriodoAcademico
    {
        $sql = "SELECT id, codigo, anio, semestre, fecha_inicio, fecha_fin, fecha_inicio_matricula, fecha_fin_matricula, estado, created_at
                FROM periodos_academicos WHERE codigo = :codigo LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function obtenerPeriodoActivo(): ?PeriodoAcademico
    {
        $sql = "SELECT id, codigo, anio, semestre, fecha_inicio, fecha_fin, fecha_inicio_matricula, fecha_fin_matricula, estado, created_at
                FROM periodos_academicos 
                WHERE estado IN ('MATRICULA_ABIERTA', 'EN_CURSO') 
                ORDER BY id DESC LIMIT 1";

        $stmt = $this->db->query($sql);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listar(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id, codigo, anio, semestre, fecha_inicio, fecha_fin, fecha_inicio_matricula, fecha_fin_matricula, estado, created_at
                FROM periodos_academicos 
                ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll();
        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): PeriodoAcademico
    {
        return new PeriodoAcademico(
            (string)$row['codigo'],
            (int)$row['anio'],
            (string)$row['semestre'],
            (string)$row['fecha_inicio'],
            (string)$row['fecha_fin'],
            (string)$row['fecha_inicio_matricula'],
            (string)$row['fecha_fin_matricula'],
            (string)$row['estado'],
            (int)$row['id'],
            $row['created_at'] ?? null
        );
    }
}
