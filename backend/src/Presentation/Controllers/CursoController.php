<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\DTO\CrearCursoDTO;
use App\Application\CasoDeUso\Curso\CrearCurso;
use App\Application\CasoDeUso\Curso\ConsultarCursos;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

class CursoController
{
    private CrearCurso $crearCurso;
    private ConsultarCursos $consultarCursos;

    public function __construct(CrearCurso $crearCurso, ConsultarCursos $consultarCursos)
    {
        $this->crearCurso = $crearCurso;
        $this->consultarCursos = $consultarCursos;
    }

    public function listar(): void
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $cursos = $this->consultarCursos->listar($limit, $offset);
            $data = array_map(fn($c) => $c->toArray(), $cursos);
            ApiResponse::success($data, "Lista de cursos obtenida.");
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function consultar(int $id): void
    {
        try {
            $curso = $this->consultarCursos->ejecutarPorId($id);
            ApiResponse::success($curso->toArray(), "Curso obtenido correctamente.");
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function listarOfertaAcademica(): void
    {
        try {
            $periodoId = isset($_GET['periodo_id']) ? (int)$_GET['periodo_id'] : 0;
            $carreraId = isset($_GET['carrera_id']) ? (int)$_GET['carrera_id'] : 0;

            if ($periodoId <= 0 || $carreraId <= 0) {
                ApiResponse::unprocessable("Los parámetros 'periodo_id' y 'carrera_id' son requeridos.");
                return;
            }

            $oferta = $this->consultarCursos->listarOfertaPorPeriodoYCarrera($periodoId, $carreraId);
            ApiResponse::success($oferta, "Oferta académica obtenida correctamente.");
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function registrar(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = CrearCursoDTO::fromArray($input);
            $curso = $this->crearCurso->ejecutar($dto);

            ApiResponse::created($curso->toArray(), "Curso creado exitosamente.");
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
