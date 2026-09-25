<?php

declare(strict_types=1);

namespace App\Presentation\Routes;

use App\Application\CasoDeUso\Curso\ConsultarCursos;
use App\Application\CasoDeUso\Curso\CrearCurso;
use App\Application\CasoDeUso\Estudiante\ConsultarEstudiante;
use App\Application\CasoDeUso\Estudiante\CrearEstudiante;
use App\Application\CasoDeUso\Estudiante\ListarEstudiantes;
use App\Application\CasoDeUso\Matricula\AnularMatricula;
use App\Application\CasoDeUso\Matricula\ConsultarMatricula;
use App\Application\CasoDeUso\Matricula\RegistrarMatricula;
use App\Application\CasoDeUso\PeriodoAcademico\ConsultarPeriodoActivo;
use App\Application\CasoDeUso\PeriodoAcademico\ListarPeriodos;
use App\Application\CasoDeUso\Usuario\AutenticarUsuario;
use App\Application\CasoDeUso\Usuario\ConsultarUsuario;
use App\Application\CasoDeUso\Usuario\CrearUsuario;
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
use App\Presentation\Middleware\RoleMiddleware;
use App\Presentation\Middleware\MatriculaAccessMiddleware;
use App\Infrastructure\Repositories\MySQLSesionRepositorio;
use App\Application\CasoDeUso\Auth\IniciarSesion;
use App\Application\CasoDeUso\Auth\CerrarSesion;
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
    $sesionRepo = new MySQLSesionRepositorio($db);
    $authController = new AuthController(new IniciarSesion($usuarioRepo, $sesionRepo), new CerrarSesion($sesionRepo));
    $usuarioController = new UsuarioController($crearUsuario, $consultarUsuario);
    $estudianteController = new EstudianteController($crearEstudiante, $consultarEstudiante, $listarEstudiantes);
    $cursoController = new CursoController($crearCurso, $consultarCursos);
    $periodoController = new PeriodoAcademicoController($consultarPeriodoActivo, $listarPeriodos);
    $matriculaController = new MatriculaController($registrarMatricula, $consultarMatricula, $anularMatricula);

    // 5. Middleware de Autenticación
    $authMiddleware = new AuthMiddleware($sesionRepo, $usuarioRepo);
    $staff = [$authMiddleware, new RoleMiddleware(['ADMIN','COORDINADOR'])];
    $admin = [$authMiddleware, new RoleMiddleware(['ADMIN'])];

    // ==========================================
    // DEFINICIÓN DE RUTAS DE LA API
    // ==========================================

    // Health Check
    $router->get('/api/health', function () use ($db) {
        $start = microtime(true);
        $db->query('SELECT 1');
        ApiResponse::success([
            'status' => 'UP',
            'database' => ['status'=>'CONNECTED','latencyMs'=>round((microtime(true)-$start)*1000,2)],
            'system' => 'SGAU - Matrículas Backend (Clean Architecture)',
            'php_version' => PHP_VERSION,
            'timestamp' => date('c'),
        ], 'Servicio funcionando correctamente.');
    });

    // Autenticación
    $router->post('/api/auth/login', [$authController, 'login']);

    $router->post('/api/auth/logout', [$authController, 'logout'], [$authMiddleware]);

    // Matrículas
    $router->post('/api/matriculas', [$matriculaController, 'registrar'], [$authMiddleware, new MatriculaAccessMiddleware($matriculaRepo, 'crear')]);
    $router->get('/api/matriculas/{id}', [$matriculaController, 'consultar'], [$authMiddleware, new MatriculaAccessMiddleware($matriculaRepo, 'id')]);
    $router->get('/api/matriculas/codigo/{codigo}', [$matriculaController, 'consultarPorCodigo'], [$authMiddleware, new MatriculaAccessMiddleware($matriculaRepo, 'codigo')]);
    $router->get('/api/matriculas/estudiante/{id}', [$matriculaController, 'listarPorEstudiante'], [$authMiddleware, new MatriculaAccessMiddleware($matriculaRepo, 'estudiante')]);
    $router->delete('/api/matriculas/{id}', [$matriculaController, 'anular'], [$authMiddleware, new MatriculaAccessMiddleware($matriculaRepo, 'id')]);
    $router->post('/api/matriculas/{id}/anular', [$matriculaController, 'anular'], [$authMiddleware, new MatriculaAccessMiddleware($matriculaRepo, 'id')]);

    // Estudiantes
    $router->get('/api/estudiantes', [$estudianteController, 'listar'], $staff);
    $router->post('/api/estudiantes', [$estudianteController, 'registrar'], $staff);
    $router->get('/api/estudiantes/{id}', [$estudianteController, 'consultar'], $staff);
    $router->get('/api/estudiantes/codigo/{codigo}', [$estudianteController, 'consultarPorCodigo'], $staff);

    // Cursos & Oferta Académica
    $router->get('/api/cursos', [$cursoController, 'listar'], [$authMiddleware]);
    $router->post('/api/cursos', [$cursoController, 'registrar'], $staff);
    $router->get('/api/cursos/{id}', [$cursoController, 'consultar'], [$authMiddleware]);
    $router->get('/api/oferta-academica', [$cursoController, 'listarOfertaAcademica'], [$authMiddleware]);

    // Periodos Académicos
    $router->get('/api/periodos', [$periodoController, 'listar'], [$authMiddleware]);
    $router->get('/api/periodos/activo', [$periodoController, 'activo'], [$authMiddleware]);

    // Usuarios
    $router->get('/api/usuarios', [$usuarioController, 'listar'], $admin);
    $router->post('/api/usuarios', [$usuarioController, 'registrar'], $admin);
    $router->get('/api/usuarios/{id}', [$usuarioController, 'consultar'], $admin);
};
