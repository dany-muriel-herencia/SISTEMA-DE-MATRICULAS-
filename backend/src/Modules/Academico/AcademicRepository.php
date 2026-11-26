<?php

declare(strict_types=1);

namespace App\Modules\Academico;

use App\Core\Database;
use PDO;

/**
 * M04: Repositorio de Gestión Curricular, Malla y Prerrequisitos (ISSUE-04 Dany).
 */
final class AcademicRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function listarFacultadesYEscuelas(): array
    {
        $sql = <<<'SQL'
            SELECT f.id_facultad, f.codigo_facultad, f.nombre AS facultad, f.decano,
                   ep.id_escuela, ep.codigo_escuela, ep.nombre AS escuela, ep.director
            FROM facultades f
            LEFT JOIN escuelas_profesionales ep ON ep.id_facultad = f.id_facultad
            WHERE f.activo = 1
            ORDER BY f.id_facultad ASC, ep.id_escuela ASC
        SQL;
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerMallaPorPlan(int $idPlan): array
    {
        $sql = <<<'SQL'
            SELECT mc.id_malla, mc.id_plan, mc.ciclo, c.id_curso, c.codigo_curso,
                   c.nombre AS curso, c.creditos, c.horas_teoria, c.horas_practica, c.tipo,
                   GROUP_CONCAT(cr.codigo_curso ORDER BY cr.codigo_curso SEPARATOR ', ') AS prerrequisitos
            FROM malla_curricular mc
            INNER JOIN cursos c ON c.id_curso = mc.id_curso
            LEFT JOIN prerrequisitos pr ON pr.id_curso = c.id_curso
            LEFT JOIN cursos cr ON cr.id_curso = pr.id_curso_req
            WHERE mc.id_plan = :id_plan
            GROUP BY mc.id_malla, mc.id_plan, mc.ciclo, c.id_curso, c.codigo_curso,
                     c.nombre, c.creditos, c.horas_teoria, c.horas_practica, c.tipo
            ORDER BY mc.ciclo ASC, c.codigo_curso ASC
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_plan' => $idPlan]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPeriodoActivo(): ?array
    {
        $stmt = $this->pdo->query("SELECT * FROM periodos_academicos WHERE estado = 'ACTIVO' ORDER BY id_periodo DESC LIMIT 1");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        return $row ?: null;
    }

    public function validarPrerrequisitosYPlan(int $idEstudiante, int $idCurso): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT fn_validar_curso_en_plan(:est1, :cur1) AS en_plan,
                    fn_validar_prerrequisitos(:est2, :cur2) AS cumple_prerrequisitos'
        );
        $stmt->execute([
            ':est1' => $idEstudiante,
            ':cur1' => $idCurso,
            ':est2' => $idEstudiante,
            ':cur2' => $idCurso,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['en_plan' => 0, 'cumple_prerrequisitos' => 0];
    }
}
