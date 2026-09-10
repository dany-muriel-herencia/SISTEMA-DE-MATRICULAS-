<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\DTO\RegistrarMatriculaDTO;
use App\Application\UseCases\Matricula\AnularMatricula;
use App\Application\UseCases\Matricula\ConsultarMatricula;
use App\Application\UseCases\Matricula\RegistrarMatricula;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

class MatriculaController
{
    private RegistrarMatricula $registrarMatricula;
    private ConsultarMatricula $consultarMatricula;
    private AnularMatricula $anularMatricula;

    public function __construct(
        RegistrarMatricula $registrarMatricula,
        ConsultarMatricula $consultarMatricula,
        AnularMatricula $anularMatricula
    ) {
        $this->registrarMatricula = $registrarMatricula;
        $this->consultarMatricula = $consultarMatricula;
        $this->anularMatricula = $anularMatricula;
    }

    public function registrar(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $dto = RegistrarMatriculaDTO::fromArray($input);

            $matricula = $this->registrarMatricula->ejecutar($dto);

            ApiResponse::created(
                $matricula->toArray(),
                "Matrícula registrada correctamente con código {$matricula->getCodigoMatricula()}."
            );
        } catch (InvalidArgumentException | DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error("Error al procesar la matrícula: " . $e->getMessage(), 500);
        }
    }

    public function consultar(int $id): void
    {
        try {
            $matricula = $this->consultarMatricula->ejecutarPorId($id);
            ApiResponse::success($matricula->toArray(), "Matrícula obtenida correctamente.");
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error("Error al consultar la matrícula: " . $e->getMessage(), 500);
        }
    }

    public function consultarPorCodigo(string $codigo): void
    {
        try {
            $matricula = $this->consultarMatricula->ejecutarPorCodigo($codigo);
            ApiResponse::success($matricula->toArray(), "Matrícula obtenida correctamente.");
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error("Error al consultar la matrícula: " . $e->getMessage(), 500);
        }
    }

    public function listarPorEstudiante(int $estudianteId): void
    {
        try {
            $matriculas = $this->consultarMatricula->listarPorEstudiante($estudianteId);
            $data = array_map(fn($m) => $m->toArray(), $matriculas);
            ApiResponse::success($data, "Historial de matrículas obtenido correctamente.");
        } catch (Throwable $e) {
            ApiResponse::error("Error al listar matrículas: " . $e->getMessage(), 500);
        }
    }

    public function anular(int $id): void
    {
        try {
            $matricula = $this->anularMatricula->ejecutar($id);
            ApiResponse::success(
                $matricula->toArray(),
                "Matrícula {$matricula->getCodigoMatricula()} anulada correctamente y vacantes liberadas."
            );
        } catch (DomainException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error("Error al anular la matrícula: " . $e->getMessage(), 500);
        }
    }
}
