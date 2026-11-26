<?php

declare(strict_types=1);

namespace App\Modules\Horarios;

use App\Core\ApiResponse;
use App\Core\Router;

final class ScheduleController
{
    private ScheduleRepository $repo;

    public function __construct()
    {
        $this->repo = new ScheduleRepository();
    }

    public function consultarVacantes(string $idPeriodo): void
    {
        ApiResponse::success($this->repo->consultarVacantesPorPeriodo((int)$idPeriodo));
    }

    public function listarSeccionesPorCurso(string $idCurso): void
    {
        ApiResponse::success($this->repo->listarSeccionesPorCurso((int)$idCurso));
    }

    public function listarAulas(): void
    {
        ApiResponse::success($this->repo->listarAulas());
    }

    public function validarCruceHorario(): void
    {
        $body = Router::getJsonBody();
        $idMatricula = (int)($body['idMatricula'] ?? 0);
        $idSeccion = (int)($body['idSeccion'] ?? 0);

        if ($idMatricula <= 0 || $idSeccion <= 0) {
            ApiResponse::error('idMatricula e idSeccion son obligatorios.', 400);
            return;
        }

        $cruce = $this->repo->detectarCruceHorario($idMatricula, $idSeccion);
        ApiResponse::success([
            'idMatricula' => $idMatricula,
            'idSeccion' => $idSeccion,
            'tieneCruce' => $cruce,
        ], $cruce ? 'Se detectó cruce de horario.' : 'Sin cruce de horario.');
    }
}
