<?php

declare(strict_types=1);

namespace App\Modules\Academico;

use App\Core\ApiResponse;
use App\Core\Router;

final class AcademicController
{
    private AcademicRepository $repo;

    public function __construct()
    {
        $this->repo = new AcademicRepository();
    }

    public function listarFacultades(): void
    {
        ApiResponse::success($this->repo->listarFacultadesYEscuelas());
    }

    public function obtenerMalla(string $idPlan): void
    {
        ApiResponse::success($this->repo->obtenerMallaPorPlan((int)$idPlan));
    }

    public function obtenerPeriodoActivo(): void
    {
        ApiResponse::success($this->repo->obtenerPeriodoActivo());
    }

    public function validarPrerrequisitos(): void
    {
        $body = Router::getJsonBody();
        $idEstudiante = (int)($body['idEstudiante'] ?? 0);
        $idCurso = (int)($body['idCurso'] ?? 0);

        if ($idEstudiante <= 0 || $idCurso <= 0) {
            ApiResponse::error('idEstudiante e idCurso son obligatorios.', 400);
            return;
        }

        ApiResponse::success($this->repo->validarPrerrequisitosYPlan($idEstudiante, $idCurso));
    }
}
