<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\CasoDeUso\Horario\ConsultarHorariosSeccion;
use App\Application\CasoDeUso\Horario\CrearHorarioSeccion;
use App\Application\CasoDeUso\Horario\RegistrarAula;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador HTTP para el Módulo de Horarios y Aulas (CU-32 a CU-36).
 */
class HorarioModuloController
{
    private RegistrarAula $registrarAula;
    private CrearHorarioSeccion $crearHorario;
    private ConsultarHorariosSeccion $consultarHorarios;

    public function __construct(
        RegistrarAula $registrarAula,
        CrearHorarioSeccion $crearHorario,
        ConsultarHorariosSeccion $consultarHorarios
    ) {
        $this->registrarAula     = $registrarAula;
        $this->crearHorario       = $crearHorario;
        $this->consultarHorarios = $consultarHorarios;
    }

    /** POST /api/horarios/aulas (CU-32) */
    public function registrarAula(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->registrarAula->ejecutar($input);
            ApiResponse::success($res, 'Aula/laboratorio registrado correctamente.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::conflict($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar aula: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/horarios/aulas (CU-32) */
    public function listarAulas(): void
    {
        try {
            $res = $this->registrarAula->listarAulas();
            ApiResponse::success($res, 'Lista de aulas y laboratorios.');
        } catch (Throwable $e) {
            ApiResponse::error('Error al listar aulas: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/horarios (CU-33, CU-34, CU-35, CU-36) */
    public function crearHorario(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->crearHorario->ejecutar($input);
            ApiResponse::success($res, 'Horario de sección creado exitosamente sin conflictos.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::conflict($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al crear horario: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/horarios */
    public function listarHorarios(): void
    {
        try {
            $res = $this->consultarHorarios->listarHorarios();
            ApiResponse::success($res, 'Lista de horarios registrados.');
        } catch (Throwable $e) {
            ApiResponse::error('Error al listar horarios: ' . $e->getMessage(), 500);
        }
    }
}
