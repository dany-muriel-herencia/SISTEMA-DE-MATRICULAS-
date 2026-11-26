<?php

declare(strict_types=1);

namespace App\Modules\Rectificacion;

use App\Core\ApiResponse;
use App\Core\Router;

final class RectificationController
{
    private RectificationRepository $repo;

    public function __construct()
    {
        $this->repo = new RectificationRepository();
    }

    public function listarPendientes(): void
    {
        ApiResponse::success($this->repo->listarPendientes());
    }

    public function solicitar(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->solicitar(
            (int)($body['idMatricula'] ?? 0),
            strtoupper((string)($body['tipoMovimiento'] ?? '')),
            (int)($body['idSeccionOrigen'] ?? 0),
            isset($body['idSeccionDestino']) ? (int)$body['idSeccionDestino'] : null
        );

        if (($out['codigo'] ?? '') !== 'EXITO') {
            ApiResponse::error((string)($out['mensaje'] ?? 'No se pudo solicitar la rectificación.'), 400, (string)($out['codigo'] ?? 'ERROR'), $out);
            return;
        }
        ApiResponse::success($out, (string)$out['mensaje']);
    }

    public function aprobar(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->aprobar((int)($body['idRectificacion'] ?? 0), (int)($body['idAdmin'] ?? 0));

        if (($out['codigo'] ?? '') !== 'EXITO') {
            ApiResponse::error((string)($out['mensaje'] ?? 'Error al aprobar rectificación.'), 400, (string)($out['codigo'] ?? 'ERROR'), $out);
            return;
        }
        ApiResponse::success($out, (string)$out['mensaje']);
    }

    public function rechazar(): void
    {
        $body = Router::getJsonBody();
        $out = $this->repo->rechazar(
            (int)($body['idRectificacion'] ?? 0),
            (int)($body['idAdmin'] ?? 0),
            (string)($body['observaciones'] ?? 'Rechazado por administración')
        );

        if (($out['codigo'] ?? '') !== 'EXITO') {
            ApiResponse::error((string)($out['mensaje'] ?? 'Error al rechazar rectificación.'), 400, (string)($out['codigo'] ?? 'ERROR'), $out);
            return;
        }
        ApiResponse::success($out, (string)$out['mensaje']);
    }
}
