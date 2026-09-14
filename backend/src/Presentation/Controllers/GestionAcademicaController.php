<?php

declare(strict_types=1);

namespace App\Presentation\Controllers;

use App\Application\CasoDeUso\GestionAcademica\DefinirPrerrequisitosCurso;
use App\Application\CasoDeUso\GestionAcademica\GestionarCursoAcademico;
use App\Application\CasoDeUso\GestionAcademica\GestionarEstructuraAcademica;
use App\Application\CasoDeUso\GestionAcademica\GestionarPeriodoAcademico;
use App\Application\CasoDeUso\GestionAcademica\GestionarPlanEstudio;
use App\Application\CasoDeUso\GestionAcademica\OrganizarCursosPorCiclo;
use App\Presentation\Responses\ApiResponse;
use DomainException;
use InvalidArgumentException;
use Throwable;

/**
 * Controlador para el Módulo de Gestión Académica (CU-12 a CU-18).
 */
class GestionAcademicaController
{
    private GestionarEstructuraAcademica $estructura;
    private GestionarPlanEstudio $planEstudio;
    private GestionarCursoAcademico $cursoAcademico;
    private DefinirPrerrequisitosCurso $prerrequisitos;
    private OrganizarCursosPorCiclo $cursosPorCiclo;
    private GestionarPeriodoAcademico $periodoAcademico;

    public function __construct(
        GestionarEstructuraAcademica $estructura,
        GestionarPlanEstudio $planEstudio,
        GestionarCursoAcademico $cursoAcademico,
        DefinirPrerrequisitosCurso $prerrequisitos,
        OrganizarCursosPorCiclo $cursosPorCiclo,
        GestionarPeriodoAcademico $periodoAcademico
    ) {
        $this->estructura       = $estructura;
        $this->planEstudio      = $planEstudio;
        $this->cursoAcademico   = $cursoAcademico;
        $this->prerrequisitos   = $prerrequisitos;
        $this->cursosPorCiclo   = $cursosPorCiclo;
        $this->periodoAcademico = $periodoAcademico;
    }

    /** GET /api/gestion-academica/estructura (CU-12) */
    public function listarEstructura(): void
    {
        try {
            $data = $this->estructura->listarEstructura();
            ApiResponse::success($data, 'Estructura académica obtenida.');
        } catch (Throwable $e) {
            ApiResponse::error('Error al listar estructura: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/gestion-academica/facultades (CU-12) */
    public function registrarFacultad(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->estructura->registrarFacultad($input['nombre'] ?? '', $input['codigo'] ?? '');
            ApiResponse::success($res, 'Facultad registrada.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar facultad: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/gestion-academica/escuelas (CU-12) */
    public function registrarEscuela(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->estructura->registrarEscuela((int)($input['id_facultad'] ?? 0), $input['nombre'] ?? '', $input['codigo'] ?? '');
            ApiResponse::success($res, 'Escuela registrada.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar escuela: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/gestion-academica/carreras (CU-12) */
    public function registrarCarrera(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->estructura->registrarCarrera((int)($input['id_escuela'] ?? 0), $input['nombre'] ?? '', $input['codigo'] ?? '');
            ApiResponse::success($res, 'Carrera registrada.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar carrera: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/gestion-academica/planes (CU-13) */
    public function crearPlanEstudio(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->planEstudio->crearPlanEstudio(
                (int)($input['id_carrera'] ?? 0),
                $input['nombre'] ?? '',
                $input['fecha_inicio'] ?? date('Y-m-d'),
                $input['fecha_fin'] ?? null
            );
            ApiResponse::success($res, 'Plan de estudio creado.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al crear plan de estudio: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/gestion-academica/planes (CU-13) */
    public function listarPlanes(): void
    {
        try {
            $res = $this->planEstudio->listarPlanes();
            ApiResponse::success($res, 'Planes de estudio listados.');
        } catch (Throwable $e) {
            ApiResponse::error('Error al listar planes: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/gestion-academica/cursos (CU-14, CU-15) */
    public function registrarCurso(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->cursoAcademico->registrarCurso(
                $input['nombre'] ?? '',
                $input['codigo'] ?? '',
                (int)($input['creditos'] ?? 3),
                (int)($input['horas_teoria'] ?? 2),
                (int)($input['horas_practica'] ?? 2),
                $input['ciclo'] ?? 'I',
                $input['tipo'] ?? 'OBLIGATORIO'
            );
            ApiResponse::success($res, 'Curso registrado y clasificado.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::conflict($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar curso: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/gestion-academica/cursos (CU-14) */
    public function listarCursos(): void
    {
        try {
            $res = $this->cursoAcademico->listarCursos();
            ApiResponse::success($res, 'Cursos académicos listados.');
        } catch (Throwable $e) {
            ApiResponse::error('Error al listar cursos: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/gestion-academica/prerrequisitos (CU-16) */
    public function asignarPrerrequisito(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->prerrequisitos->asignarPrerrequisito(
                (int)($input['id_curso'] ?? 0),
                (int)($input['id_curso_requerido'] ?? 0)
            );
            ApiResponse::success($res, 'Prerrequisito asignado.');
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al asignar prerrequisito: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/gestion-academica/organizar-ciclo (CU-17) */
    public function asignarCursoACiclo(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->cursosPorCiclo->asignarCursoACiclo(
                (int)($input['id_plan'] ?? 0),
                (int)($input['id_curso'] ?? 0),
                (int)($input['ciclo'] ?? 1),
                (bool)($input['obligatorio'] ?? true)
            );
            ApiResponse::success($res, 'Curso organizado en ciclo.');
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (DomainException $e) {
            ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al organizar curso: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/gestion-academica/malla/{idPlan} (CU-17) */
    public function obtenerMalla(int $idPlan): void
    {
        try {
            $res = $this->cursosPorCiclo->obtenerMallaPorCiclos($idPlan);
            ApiResponse::success($res, 'Malla curricular por ciclos.');
        } catch (Throwable $e) {
            ApiResponse::error('Error al obtener malla: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/gestion-academica/periodos (CU-18) */
    public function registrarPeriodo(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $res = $this->periodoAcademico->registrarPeriodo(
                $input['nombre'] ?? '',
                $input['fecha_inicio'] ?? date('Y-m-d'),
                $input['fecha_fin'] ?? date('Y-m-d'),
                $input['fecha_matricula_inicio'] ?? date('Y-m-d'),
                $input['fecha_matricula_fin'] ?? date('Y-m-d'),
                $input['estado'] ?? 'INACTIVO'
            );
            ApiResponse::success($res, 'Periodo académico registrado.', 201);
        } catch (InvalidArgumentException $e) {
            ApiResponse::unprocessable($e->getMessage());
        } catch (Throwable $e) {
            ApiResponse::error('Error al registrar periodo: ' . $e->getMessage(), 500);
        }
    }
}
