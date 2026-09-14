<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\CasoDeUso\Estudiante\AsociarEstudianteCarreraPlan;
use App\Application\CasoDeUso\Estudiante\ConsultarHistorialAcademico;
use App\Application\CasoDeUso\Estudiante\RegistrarEstadoEstudiante;
use App\Application\CasoDeUso\Estudiante\RegistrarEstudiante;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador para el Módulo de Estudiantes (CU-07 a CU-11).
 */
class EstudianteModuloController
{
    private RegistrarEstudiante $registrarEstudiante;
    private AsociarEstudianteCarreraPlan $asociarCarreraPlan;
    private ConsultarHistorialAcademico $historialAcademico;
    private RegistrarEstadoEstudiante $registrarEstado;

    public function __construct(
        RegistrarEstudiante $registrarEstudiante,
        AsociarEstudianteCarreraPlan $asociarCarreraPlan,
        ConsultarHistorialAcademico $historialAcademico,
        RegistrarEstadoEstudiante $registrarEstado
    ) {
        $this->registrarEstudiante = $registrarEstudiante;
        $this->asociarCarreraPlan  = $asociarCarreraPlan;
        $this->historialAcademico  = $historialAcademico;
        $this->registrarEstado     = $registrarEstado;
    }

    /** POST /api/modulo-estudiantes/registrar (CU-07, CU-08) */
    public function registrar(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $resultado = $this->registrarEstudiante->ejecutar($input);
            ApiResponse::success($resultado, 'Estudiante registrado correctamente con código asignado.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::conflict($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar estudiante: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/modulo-estudiantes/asociar-carrera (CU-09) */
    public function asociarCarreraPlan(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $idEstudiante = (int) ($input['id_estudiante'] ?? 0);
            $idCarrera    = (int) ($input['id_carrera'] ?? 0);
            $idPlan       = (int) ($input['id_plan_estudio'] ?? 0);

            $resultado = $this->asociarCarreraPlan->ejecutar($idEstudiante, $idCarrera, $idPlan);
            ApiResponse::success($resultado, 'Estudiante asociado a la carrera y plan de estudios.');
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al asociar carrera: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/modulo-estudiantes/{idEstudiante}/historial (CU-10) */
    public function historialAcademico(int $idEstudiante): void
    {
        try {
            $resultado = $this->historialAcademico->ejecutar($idEstudiante);
            ApiResponse::success($resultado, 'Historial académico obtenido correctamente.');
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al consultar historial: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/modulo-estudiantes/{idEstudiante}/estado (CU-11) */
    public function registrarEstado(int $idEstudiante): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $nuevoEstado = (string) ($input['nuevo_estado'] ?? '');
            $motivo      = $input['motivo'] ?? null;

            $resultado = $this->registrarEstado->ejecutar($idEstudiante, $nuevoEstado, $motivo);
            ApiResponse::success($resultado, 'Estado del estudiante actualizado.');
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al actualizar estado: ' . $e->getMessage(), 500);
        }
    }
}
