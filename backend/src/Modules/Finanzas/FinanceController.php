<?php

declare(strict_types=1);

namespace App\Modules\Finanzas;

use App\Core\ApiResponse;
use App\Core\Router;

final class FinanceController
{
    private FinanceRepository $repo;

    public function __construct()
    {
        $this->repo = new FinanceRepository();
    }

    public function listarConceptos(): void
    {
        ApiResponse::success($this->repo->listarConceptos());
    }

    public function listarDeudasEstudiante(string $idEstudiante): void
    {
        ApiResponse::success($this->repo->listarDeudasEstudiante((int)$idEstudiante));
    }

    // TX-03: Registro transaccional de pago y emisión de boleta QR (sp_registrar_pago)
    public function registrarPago(): void
    {
        $body = Router::getJsonBody();
        $idDeuda = (int)($body['idDeuda'] ?? 0);
        $idAdmin = isset($body['idAdmin']) ? (int)$body['idAdmin'] : null;
        $monto = (float)($body['monto'] ?? 0);
        $metodo = strtoupper((string)($body['metodo'] ?? 'VENTANILLA'));
        $nroOperacion = (string)($body['nroOperacion'] ?? ('OP-' . date('YmdHis') . '-' . random_int(100, 999)));

        if ($idDeuda <= 0 || $monto <= 0) {
            ApiResponse::error('idDeuda y monto mayor a 0 son obligatorios.', 400);
            return;
        }

        $out = $this->repo->registrarPago($idDeuda, $idAdmin, $monto, $metodo, $nroOperacion);
        if (($out['codigo'] ?? '') !== 'EXITO') {
            ApiResponse::error((string)($out['mensaje'] ?? 'Error al registrar pago.'), 400, (string)($out['codigo'] ?? 'ERROR'), $out);
            return;
        }

        ApiResponse::success($out, (string)$out['mensaje']);
    }
}
