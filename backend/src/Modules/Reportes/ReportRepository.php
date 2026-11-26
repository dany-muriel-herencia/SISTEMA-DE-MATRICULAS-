<?php

declare(strict_types=1);

namespace App\Modules\Reportes;

use App\Core\Database;
use PDO;

/**
 * M09 & M05-QR: Repositorio de Reportes, Constancia QR, Nóminas y Actas con Hash SHA-256
 * (ISSUE-06 y ISSUE-10 Jhosep Arocutipa).
 */
final class ReportRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function obtenerDatosConstancia(int $idMatricula): ?array
    {
        $stmtCab = $this->pdo->prepare(
            'SELECT m.id_matricula, m.nro_constancia, m.estado, m.total_creditos,
                    m.creditos_adicionales, m.tipo_matricula, m.fecha_matricula,
                    p.codigo_periodo, e.codigo_universitario,
                    CONCAT(u.apellidos, ", ", u.nombres) AS estudiante,
                    ep.nombre AS escuela
             FROM matriculas m
             INNER JOIN periodos_academicos p ON p.id_periodo = m.id_periodo
             INNER JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
             INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
             INNER JOIN escuelas_profesionales ep ON ep.id_escuela = e.id_escuela
             WHERE m.id_matricula = :id'
        );
        $stmtCab->execute([':id' => $idMatricula]);
        $cabecera = $stmtCab->fetch(PDO::FETCH_ASSOC);
        if (!$cabecera) {
            return null;
        }

        $stmtDet = $this->pdo->prepare(
            'SELECT c.codigo_curso, c.nombre AS curso, c.creditos, s.codigo_seccion, s.turno,
                    dm.estado, dm.origen
             FROM detalle_matricula dm
             INNER JOIN secciones s ON s.id_seccion = dm.id_seccion
             INNER JOIN cursos c ON c.id_curso = s.id_curso
             WHERE dm.id_matricula = :id AND dm.estado = "INSCRITO"
             ORDER BY c.codigo_curso ASC'
        );
        $stmtDet->execute([':id' => $idMatricula]);
        $cursos = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        $payloadFirma = json_encode([$cabecera['nro_constancia'], $cabecera['codigo_universitario'], $cabecera['total_creditos'], $cursos]);
        $hashSha256 = hash('sha256', (string)$payloadFirma);

        return [
            'constancia' => $cabecera,
            'cursos' => $cursos,
            'hashVerificacionSha256' => $hashSha256,
            'qrPayload' => "SGAU-UNJBG|{$cabecera['nro_constancia']}|{$cabecera['codigo_universitario']}|{$hashSha256}",
        ];
    }

    public function obtenerNominaSeccion(int $idSeccion): array
    {
        $sql = <<<'SQL'
            SELECT m.nro_constancia, e.codigo_universitario,
                   CONCAT(u.apellidos, ', ', u.nombres) AS estudiante,
                   u.email_institucional, dm.estado, dm.origen,
                   dm.nota_teoria, dm.nota_practica, dm.nota_final
            FROM detalle_matricula dm
            INNER JOIN matriculas m ON m.id_matricula = dm.id_matricula
            INNER JOIN estudiantes e ON e.id_estudiante = m.id_estudiante
            INNER JOIN usuarios u ON u.id_usuario = e.id_usuario
            WHERE dm.id_seccion = :id_seccion AND dm.estado = 'INSCRITO'
            ORDER BY u.apellidos ASC, u.nombres ASC
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_seccion' => $idSeccion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cerrarSemestre(int $idPeriodo): array
    {
        $res = Database::callStoredProcedure(
            'CALL sp_cerrar_semestre_academico(?, @p_total_actualizados, @p_mensaje)',
            [$idPeriodo],
            [
                'totalActualizados' => '@p_total_actualizados',
                'mensaje' => '@p_mensaje',
            ]
        );
        return $res['out'];
    }
}
