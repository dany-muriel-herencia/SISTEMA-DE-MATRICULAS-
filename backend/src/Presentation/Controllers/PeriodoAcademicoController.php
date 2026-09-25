<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\CasoDeUso\PeriodoAcademico\ConsultarPeriodoActivo;
use App\Application\CasoDeUso\PeriodoAcademico\ListarPeriodos;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use Throwable;

class PeriodoAcademicoController
{
    private ConsultarPeriodoActivo $consultarPeriodoActivo;
    private ListarPeriodos $listarPeriodos;

    public function __construct(
        ConsultarPeriodoActivo $consultarPeriodoActivo,
        ListarPeriodos $listarPeriodos
    ) {
        $this->consultarPeriodoActivo = $consultarPeriodoActivo;
        $this->listarPeriodos = $listarPeriodos;
    }

    public function listar(): void
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $periodos = $this->listarPeriodos->ejecutar($limit, $offset);
            $data = array_map(fn($p) => $p->toArray(), $periodos);
            ApiResponse::success($data, "Lista de periodos académicos obtenida.");
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function activo(): void
    {
        try {
            $periodo = $this->consultarPeriodoActivo->ejecutar();
            ApiResponse::success($periodo->toArray(), "Periodo académico activo obtenido.");
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
