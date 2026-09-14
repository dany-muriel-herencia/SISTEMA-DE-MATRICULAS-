<?php

declare(strict_types=1);

namespace App\Presentation\Routes;

use App\Application\UseCases\Curso\ConsultarCursos;
use App\Application\UseCases\Curso\CrearCurso;
use App\Application\UseCases\Estudiante\ConsultarEstudiante;
use App\Application\UseCases\Estudiante\CrearEstudiante;
use App\Application\UseCases\Estudiante\ListarEstudiantes;
use App\Application\CasoDeUso\Matricula\AnularMatricula;
use App\Application\CasoDeUso\Matricula\ConsultarMatricula;
use App\Application\CasoDeUso\Matricula\RegistrarMatricula;
use App\Application\UseCases\PeriodoAcademico\ConsultarPeriodoActivo;
use App\Application\UseCases\PeriodoAcademico\ListarPeriodos;
use App\Application\UseCases\Usuario\AutenticarUsuario;
use App\Application\UseCases\Usuario\ConsultarUsuario;
use App\Application\UseCases\Usuario\CrearUsuario;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repositories\MySQLCursoRepositorio;
use App\Infrastructure\Repositories\MySQLEstudianteRepositorio;
use App\Infrastructure\Repositories\MySQLMatriculaRepositorio;
use App\Infrastructure\Repositories\MySQLPeriodoAcademicoRepositorio;
use App\Infrastructure\Repositories\MySQLSeccionRepositorio;
use App\Infrastructure\Repositories\MySQLUsuarioRepositorio;
use App\Presentation\Controllers\AuthController;
use App\Presentation\Controllers\CursoController;
use App\Presentation\Controllers\EstudianteController;
use App\Presentation\Controllers\MatriculaController;
use App\Presentation\Controllers\PeriodoAcademicoController;
use App\Presentation\Controllers\UsuarioController;
use App\Presentation\Middleware\AuthMiddleware;
use App\Presentation\Middleware\CorsMiddleware;
use App\Presentation\Responses\ApiResponse;

return function (Router $router): void {
    // 1. Middleware Global
    $router->use(new CorsMiddleware());

    // 2. Inyección de Dependencias (Database & Repositories)
    $db = Connection::getInstance();
    $usuarioRepo = new MySQLUsuarioRepositorio($db);
    $estudianteRepo = new MySQLEstudianteRepositorio($db);
    $cursoRepo = new MySQLCursoRepositorio($db);
    $matriculaRepo = new MySQLMatriculaRepositorio($db);
    $periodoRepo = new MySQLPeriodoAcademicoRepositorio($db);
    $seccionRepo = new MySQLSeccionRepositorio($db);

    // 3. Casos de Uso
    // Auth & Usuario
    $autenticarUsuario = new AutenticarUsuario($usuarioRepo);
    $crearUsuario = new CrearUsuario($usuarioRepo);
    $consultarUsuario = new ConsultarUsuario($usuarioRepo);

    // Estudiante
    $crearEstudiante = new CrearEstudiante($estudianteRepo, $usuarioRepo);
    $consultarEstudiante = new ConsultarEstudiante($estudianteRepo);
    $listarEstudiantes = new ListarEstudiantes($estudianteRepo);

    // Curso
    $crearCurso = new CrearCurso($cursoRepo);
    $consultarCursos = new ConsultarCursos($cursoRepo);

    // Periodo Académico
    $consultarPeriodoActivo = new ConsultarPeriodoActivo($periodoRepo);
    $listarPeriodos = new ListarPeriodos($periodoRepo);

    // Matrícula
    $registrarMatricula = new RegistrarMatricula($matriculaRepo, $estudianteRepo, $periodoRepo, $cursoRepo, $seccionRepo);
    $consultarMatricula = new ConsultarMatricula($matriculaRepo);
    $anularMatricula = new AnularMatricula($matriculaRepo);

    // 4. Controladores
    $authController = new AuthController($autenticarUsuario);
    $usuarioController = new UsuarioController($crearUsuario, $consultarUsuario);
    $estudianteController = new EstudianteController($crearEstudiante, $consultarEstudiante, $listarEstudiantes);
    $cursoController = new CursoController($crearCurso, $consultarCursos);
    $periodoController = new PeriodoAcademicoController($consultarPeriodoActivo, $listarPeriodos);
    $matriculaController = new MatriculaController($registrarMatricula, $consultarMatricula, $anularMatricula);

    // 5. Middleware de Autenticación
    $authMiddleware = new AuthMiddleware();

    // ==========================================
    // DEFINICIÓN DE RUTAS DE LA API
    // ==========================================

    // Health Check
    $router->get('/api/health', function () {
        ApiResponse::success([
            'status' => 'UP',
            'system' => 'SGAU - Matrículas Backend (Clean Architecture)',
            'php_version' => PHP_VERSION,
            'timestamp' => date('c'),
        ], 'Servicio funcionando correctamente.');
    });

    // Autenticación
    $router->post('/api/auth/login', [$authController, 'login']);

    // Matrículas
    $router->post('/api/matriculas', [$matriculaController, 'registrar']);
    $router->get('/api/matriculas/{id}', [$matriculaController, 'consultar']);
    $router->get('/api/matriculas/codigo/{codigo}', [$matriculaController, 'consultarPorCodigo']);
    $router->get('/api/matriculas/estudiante/{id}', [$matriculaController, 'listarPorEstudiante']);
    $router->delete('/api/matriculas/{id}', [$matriculaController, 'anular']);
    $router->post('/api/matriculas/{id}/anular', [$matriculaController, 'anular']);

    // Estudiantes
    $router->get('/api/estudiantes', [$estudianteController, 'listar']);
    $router->post('/api/estudiantes', [$estudianteController, 'registrar']);
    $router->get('/api/estudiantes/{id}', [$estudianteController, 'consultar']);
    $router->get('/api/estudiantes/codigo/{codigo}', [$estudianteController, 'consultarPorCodigo']);

    // Cursos & Oferta Académica
    $router->get('/api/cursos', [$cursoController, 'listar']);
    $router->post('/api/cursos', [$cursoController, 'registrar']);
    $router->get('/api/cursos/{id}', [$cursoController, 'consultar']);
    $router->get('/api/oferta-academica', [$cursoController, 'listarOfertaAcademica']);

    // Periodos Académicos
    $router->get('/api/periodos', [$periodoController, 'listar']);
    $router->get('/api/periodos/activo', [$periodoController, 'activo']);

    // Usuarios
    $router->get('/api/usuarios', [$usuarioController, 'listar']);
    $router->post('/api/usuarios', [$usuarioController, 'registrar']);
    $router->get('/api/usuarios/{id}', [$usuarioController, 'consultar']);
};
