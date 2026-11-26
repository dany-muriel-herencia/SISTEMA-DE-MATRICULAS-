<?php

declare(strict_types=1);

namespace App\Modules\Estudiantes;

use App\Core\Database;
use PDO;

/**
 * M03: Repositorio de Gestión de Estudiantes, Ficha Médica y Kardex (ISSUE-03 Franklin).
 */
final class StudentRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function listarEstudiantes(): array
    {
        $sql = <<<'SQL'
            SELECT e.id_estudiante, e.codigo_universitario, u.nombres, u.apellidos, u.dni,
                   u.email_institucional, ep.nombre AS escuela, pe.codigo_plan,
                   e.ciclo_actual, e.estado_academico, e.promedio_ponderado,
                   e.promedio_semestre_anterior, e.creditos_aprobados
            FROM estudiantes e
            INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
            INNER JOIN escuelas_profesionales ep ON ep.id_escuela = e.id_escuela
            INNER JOIN planes_estudio pe ON pe.id_plan = e.id_plan
            ORDER BY e.codigo_universitario ASC
        SQL;
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerKardex(int $idEstudiante): ?array
    {
        $stmtEst = $this->pdo->prepare(
            'SELECT e.id_estudiante, e.codigo_universitario, u.nombres, u.apellidos,
                    e.ciclo_actual, e.estado_academico, e.promedio_ponderado,
                    e.promedio_semestre_anterior, e.creditos_aprobados,
                    fn_limite_creditos_base() AS creditos_base
             FROM estudiantes e
             INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             WHERE e.id_estudiante = :id'
        );
        $stmtEst->execute([':id' => $idEstudiante]);
        $estudiante = $stmtEst->fetch(PDO::FETCH_ASSOC);
        if (!$estudiante) {
            return null;
        }

        $stmtHist = $this->pdo->prepare(
            'SELECT p.codigo_periodo, c.codigo_curso, c.nombre AS curso, c.creditos,
                    s.codigo_seccion, dm.estado, dm.nota_teoria, dm.nota_practica,
                    dm.nota_final, dm.origen
             FROM detalle_matricula dm
             INNER JOIN matriculas m ON m.id_matricula = dm.id_matricula
             INNER JOIN periodos_academicos p ON p.id_periodo = m.id_periodo
             INNER JOIN secciones s ON s.id_seccion = dm.id_seccion
             INNER JOIN cursos c ON c.id_curso = s.id_curso
             WHERE m.id_estudiante = :id
             ORDER BY p.anio DESC, p.semestre DESC, c.codigo_curso ASC'
        );
        $stmtHist->execute([':id' => $idEstudiante]);

        $estudiante['historial'] = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
        return $estudiante;
    }

    public function obtenerFichaMedica(int $idEstudiante): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ficha_medica WHERE id_estudiante = :id LIMIT 1');
        $stmt->execute([':id' => $idEstudiante]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
