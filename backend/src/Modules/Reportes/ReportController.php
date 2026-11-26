<?php

declare(strict_types=1);

namespace App\Modules\Reportes;

use App\Core\ApiResponse;
use App\Core\Router;

final class ReportController
{
    private ReportRepository $repo;

    public function __construct()
    {
        $this->repo = new ReportRepository();
    }

    // CU-31: Constancia de matrícula con QR y Hash SHA-256
    public function emitirConstanciaQr(string $idMatricula): void
    {
        $data = $this->repo->obtenerDatosConstancia((int)$idMatricula);
        if (!$data) {
            ApiResponse::error('Matrícula no encontrada.', 404, 'MATRICULA_NO_ENCONTRADA');
            return;
        }
        ApiResponse::success($data, 'Constancia oficial con código QR y firma SHA-256 generada.');
    }

    // CU-51: Nómina oficial por sección
    public function obtenerNominaSeccion(string $idSeccion): void
    {
        $nomina = $this->repo->obtenerNominaSeccion((int)$idSeccion);
        ApiResponse::success([
            'idSeccion' => (int)$idSeccion,
            'totalMatriculados' => count($nomina),
            'estudiantes' => $nomina,
        ]);
    }

    // CU-52: Acta oficial de evaluación con Hash SHA-256
    public function emitirActaEvaluacion(string $idSeccion): void
    {
        $nomina = $this->repo->obtenerNominaSeccion((int)$idSeccion);
        $hash = hash('sha256', 'ACTA-SECCION-' . $idSeccion . '-' . json_encode($nomina));

        ApiResponse::success([
            'idSeccion' => (int)$idSeccion,
            'titulo' => 'Acta Oficial de Evaluación — UNJBG ESIS',
            'hashCriptograficoSha256' => $hash,
            'fechaEmision' => date('c'),
            'registros' => $nomina,
        ], 'Acta oficial sellada criptográficamente.');
    }

    // TX-05: Cierre de semestre académico (sp_cerrar_semestre_academico)
    public function cerrarSemestre(): void
    {
        $body = Router::getJsonBody();
        $idPeriodo = (int)($body['idPeriodo'] ?? 0);
        if ($idPeriodo <= 0) {
            ApiResponse::error('idPeriodo es obligatorio.', 400);
            return;
        }

        $out = $this->repo->cerrarSemestre($idPeriodo);
        ApiResponse::success($out, (string)($out['mensaje'] ?? 'Semestre cerrado.'));
    }
}
