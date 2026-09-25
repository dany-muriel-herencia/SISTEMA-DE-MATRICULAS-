<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\DTO\CrearEstudianteDTO;
use App\Application\CasoDeUso\Estudiante\CrearEstudiante;
use App\Application\CasoDeUso\Estudiante\ConsultarEstudiante;
use App\Application\CasoDeUso\Estudiante\ListarEstudiantes;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

class EstudianteController
{
    private CrearEstudiante $crearEstudiante;
    private ConsultarEstudiante $consultarEstudiante;
    private ListarEstudiantes $listarEstudiantes;

    public function __construct(
        CrearEstudiante $crearEstudiante,
        ConsultarEstudiante $consultarEstudiante,
        ListarEstudiantes $listarEstudiantes
    ) {
        $this->crearEstudiante = $crearEstudiante;
        $this->consultarEstudiante = $consultarEstudiante;
        $this->listarEstudiantes = $listarEstudiantes;
    }

    public function listar(): void
    {
        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $estudiantes = $this->listarEstudiantes->ejecutar($limit, $offset);
            $data = array_map(fn($e) => $e->toArray(), $estudiantes);
            ApiResponse::success($data, "Lista de estudiantes obtenida.");
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function consultar(int $id): void
    {
        try {
            $estudiante = $this->consultarEstudiante->ejecutarPorId($id);
            ApiResponse::success($estudiante->toArray(), "Estudiante obtenido correctamente.");
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function consultarPorCodigo(string $codigo): void
    {
        try {
            $estudiante = $this->consultarEstudiante->ejecutarPorCodigo($codigo);
            ApiResponse::success($estudiante->toArray(), "Estudiante obtenido correctamente.");
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }

    public function registrar(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = CrearEstudianteDTO::fromArray($input);
            $estudiante = $this->crearEstudiante->ejecutar($dto);

            ApiResponse::created($estudiante->toArray(), "Estudiante registrado exitosamente.");
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            error_log((string)$e);
            ApiResponse::error('Error interno del servidor.', 500);
        }
    }
}
