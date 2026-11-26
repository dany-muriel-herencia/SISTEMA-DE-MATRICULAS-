<?php

declare(strict_types=1);

namespace App\Modules\Horarios;

use App\Core\Database;
use PDO;

/**
 * M07: Repositorio de Horarios, Aulas y Detección de Cruces (ISSUE-08 Franklin).
 * Invoca sp_consultar_vacantes, fn_detectar_cruce_horario y fn_vacantes_disponibles.
 */
final class ScheduleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function consultarVacantesPorPeriodo(int $idPeriodo): array
    {
        $res = Database::callStoredProcedure('CALL sp_consultar_vacantes(?)', [$idPeriodo]);
        return $res['rows'];
    }

    public function listarSeccionesPorCurso(int $idCurso): array
    {
        $sql = <<<'SQL'
            SELECT s.id_seccion, s.id_periodo, s.id_curso, c.codigo_curso, c.nombre AS curso, c.creditos,
                   s.codigo_seccion, s.turno, a.codigo_aula, a.nombre AS aula, a.aforo_maximo,
                   CONCAT(u.apellidos, ', ', u.nombres) AS docente,
                   s.vacantes_total, s.vacantes_extra, s.vacantes_ocupadas,
                   fn_vacantes_disponibles(s.id_seccion) AS vacantes_disponibles,
                   GROUP_CONCAT(CONCAT(hs.dia_semana, ' ', SUBSTRING(hs.hora_inicio, 1, 5), '-', SUBSTRING(hs.hora_fin, 1, 5))
                                ORDER BY FIELD(hs.dia_semana, 'LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO'), hs.hora_inicio
                                SEPARATOR ' | ') AS horario
            FROM secciones s
            INNER JOIN cursos c ON c.id_curso = s.id_curso
            INNER JOIN aulas a ON a.id_aula = s.id_aula
            INNER JOIN usuarios u ON u.id_usuario = s.id_docente
            LEFT JOIN horarios_seccion hs ON hs.id_seccion = s.id_seccion
            WHERE s.id_curso = :id_curso
            GROUP BY s.id_seccion
            ORDER BY s.codigo_seccion ASC
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_curso' => $idCurso]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarAulas(): array
    {
        $sql = 'SELECT a.*, f.codigo_facultad FROM aulas a INNER JOIN facultades f ON f.id_facultad = a.id_facultad ORDER BY a.codigo_aula ASC';
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function detectarCruceHorario(int $idMatricula, int $idSeccion): bool
    {
        $stmt = $this->pdo->prepare('SELECT fn_detectar_cruce_horario(:id_matricula, :id_seccion) AS tiene_cruce');
        $stmt->execute([
            ':id_matricula' => $idMatricula,
            ':id_seccion' => $idSeccion,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['tiene_cruce'] ?? 0) === 1;
    }
}
