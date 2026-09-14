<?php

declare(strict_types=1);

namespace App\Presentation\Routes;

use App\Application\CasoDeUso\Auditoria\ConsultarHistorialAuditoria;
use App\Application\CasoDeUso\Auditoria\RegistrarAuditoria;
use App\Application\CasoDeUso\Estudiante\AsociarEstudianteCarreraPlan;
use App\Application\CasoDeUso\Estudiante\ConsultarHistorialAcademico;
use App\Application\CasoDeUso\Estudiante\RegistrarEstadoEstudiante;
use App\Application\CasoDeUso\Estudiante\RegistrarEstudiante;
use App\Application\CasoDeUso\GestionAcademica\DefinirPrerrequisitosCurso;
use App\Application\CasoDeUso\GestionAcademica\GestionarCursoAcademico;
use App\Application\CasoDeUso\GestionAcademica\GestionarEstructuraAcademica;
use App\Application\CasoDeUso\GestionAcademica\GestionarPeriodoAcademico;
use App\Application\CasoDeUso\GestionAcademica\GestionarPlanEstudio;
use App\Application\CasoDeUso\GestionAcademica\OrganizarCursosPorCiclo;
use App\Application\CasoDeUso\Horario\ConsultarHorariosSeccion;
use App\Application\CasoDeUso\Horario\CrearHorarioSeccion;
use App\Application\CasoDeUso\Horario\RegistrarAula;
use App\Application\CasoDeUso\Pago\ConsultarConceptosPago;
use App\Application\CasoDeUso\Pago\ConsultarHistorialFinanciero;
use App\Application\CasoDeUso\Pago\EmitirComprobante;
use App\Application\CasoDeUso\Pago\GenerarOrdenPago;
use App\Application\CasoDeUso\Pago\IdentificarPagosPendientes;
use App\Application\CasoDeUso\Pago\RegistrarConceptoPago;
use App\Application\CasoDeUso\Pago\RegistrarPago;
use App\Application\CasoDeUso\Reporte\ConsultarDemandaCursos;
use App\Application\CasoDeUso\Reporte\ExportarReporte;
use App\Application\CasoDeUso\Reporte\GenerarActaCalificaciones;
use App\Application\CasoDeUso\Reporte\GenerarListaEstudiantesPorSeccion;
use App\Application\CasoDeUso\Reporte\GenerarReporteMatriculados;
use App\Application\CasoDeUso\Reporte\GenerarReportePagos;
use App\Application\CasoDeUso\Reporte\ObtenerEstadisticasAprobacion;
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
use App\Infrastructure\Repositories\MySQLAuditoriaRepositorio;
use App\Infrastructure\Repositories\MySQLAulaRepositorio;
use App\Infrastructure\Repositories\MySQLCarreraRepositorio;
use App\Infrastructure\Repositories\MySQLComprobantePagoRepositorio;
use App\Infrastructure\Repositories\MySQLConceptoPagoRepositorio;
use App\Infrastructure\Repositories\MySQLCurriculumRepositorio;
use App\Infrastructure\Repositories\MySQLCursoRepositorio;
use App\Infrastructure\Repositories\MySQLEscuelaRepositorio;
use App\Infrastructure\Repositories\MySQLEstudianteRepositorio;
use App\Infrastructure\Repositories\MySQLFacultadRepositorio;
use App\Infrastructure\Repositories\MySQLHorarioRepositorio;
use App\Infrastructure\Repositories\MySQLMatriculaRepositorio;
use App\Infrastructure\Repositories\MySQLPeriodoAcademicoRepositorio;
use App\Infrastructure\Repositories\MySQLPlanEstudioRepositorio;
use App\Infrastructure\Repositories\MySQLPrerequisitoRepositorio;
use App\Infrastructure\Repositories\MySQLReporteRepositorio;
use App\Infrastructure\Repositories\MySQLSeccionRepositorio;
use App\Infrastructure\Repositories\MySQLPagoRepositorio;
use App\Infrastructure\Repositories\MySQLUsuarioRepositorio;
use App\Presentation\Controllers\AuditoriaController;
use App\Presentation\Controllers\EstudianteModuloController;
use App\Presentation\Controllers\GestionAcademicaController;
use App\Presentation\Controllers\HorarioModuloController;
use App\Presentation\Controllers\PagoController;
use App\Presentation\Controllers\ReporteController;
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
    $auditoriaRepo    = new MySQLAuditoriaRepositorio($db);
    $usuarioRepo      = new MySQLUsuarioRepositorio($db);
    $estudianteRepo   = new MySQLEstudianteRepositorio($db);
    $cursoRepo        = new MySQLCursoRepositorio($db);
    $matriculaRepo    = new MySQLMatriculaRepositorio($db);
    $detalleRepo      = new \App\Infrastructure\Repositories\MySQLDetalleMatriculaRepositorio($db);
    $periodoRepo      = new MySQLPeriodoAcademicoRepositorio($db);
    $seccionRepo      = new MySQLSeccionRepositorio($db);
    $conceptoPagoRepo   = new MySQLConceptoPagoRepositorio($db);
    $pagoRepo           = new MySQLPagoRepositorio($db);
    $comprobanteRepo    = new MySQLComprobantePagoRepositorio($db);
    $reporteRepo        = new MySQLReporteRepositorio($db);
    $facultadRepo       = new MySQLFacultadRepositorio($db);
    $escuelaRepo        = new MySQLEscuelaRepositorio($db);
    $carreraRepo        = new MySQLCarreraRepositorio($db);
    $planEstudioRepo    = new MySQLPlanEstudioRepositorio($db);
    $prerequisitoRepo   = new MySQLPrerequisitoRepositorio($db);
    $curriculumRepo     = new MySQLCurriculumRepositorio($db);
    $aulaRepo           = new MySQLAulaRepositorio($db);
    $horarioRepo        = new MySQLHorarioRepositorio($db);

    // 3. Casos de Uso
    // Auditoría
    $registrarAuditoria  = new RegistrarAuditoria($auditoriaRepo);

    // Módulo Estudiantes (CU-07 a CU-11)
    $registrarEstudianteNuevo = new RegistrarEstudiante($estudianteRepo, $usuarioRepo);
    $asociarCarreraPlan       = new AsociarEstudianteCarreraPlan($estudianteRepo, $carreraRepo, $planEstudioRepo);
    $historialAcademico       = new ConsultarHistorialAcademico($estudianteRepo, $matriculaRepo, $detalleRepo);
    $registrarEstadoEst       = new RegistrarEstadoEstudiante($estudianteRepo);

    // Módulo Gestión Académica (CU-12 a CU-18)
    $gestionarEstructura  = new GestionarEstructuraAcademica($facultadRepo, $escuelaRepo, $carreraRepo);
    $gestionarPlanEstudio = new GestionarPlanEstudio($planEstudioRepo, $carreraRepo);
    $gestionarCursoAcad   = new GestionarCursoAcademico($cursoRepo);
    $definirPrerreq       = new DefinirPrerrequisitosCurso($prerequisitoRepo, $cursoRepo);
    $organizarCiclo       = new OrganizarCursosPorCiclo($curriculumRepo, $planEstudioRepo, $cursoRepo);
    $gestionarPeriodo     = new GestionarPeriodoAcademico($periodoRepo);

    // Módulo Horarios y Aulas (CU-32 a CU-36)
    $registrarAulaUC      = new RegistrarAula($aulaRepo);
    $crearHorarioUC       = new CrearHorarioSeccion($horarioRepo, $seccionRepo, $aulaRepo);
    $consultarHorariosUC  = new ConsultarHorariosSeccion($horarioRepo);

    // Pagos
    $registrarConcepto    = new RegistrarConceptoPago($conceptoPagoRepo);
    $consultarConceptos   = new ConsultarConceptosPago($conceptoPagoRepo);
    $generarOrdenPago     = new GenerarOrdenPago($pagoRepo, $conceptoPagoRepo, $estudianteRepo);
    $registrarPago        = new RegistrarPago($pagoRepo, $conceptoPagoRepo, $comprobanteRepo);
    $identificarPendientes = new IdentificarPagosPendientes($pagoRepo, $comprobanteRepo);
    $emitirComprobante    = new EmitirComprobante($pagoRepo, $comprobanteRepo);
    $historialFinanciero  = new ConsultarHistorialFinanciero($pagoRepo, $comprobanteRepo);
    $consultarHistorial  = new ConsultarHistorialAuditoria($auditoriaRepo);

    // Reportes (CU-49 a CU-55)
    $generarReporteMatriculados = new GenerarReporteMatriculados($reporteRepo);
    $generarListaEstudiantes    = new GenerarListaEstudiantesPorSeccion($reporteRepo);
    $generarActaCalificaciones  = new GenerarActaCalificaciones($reporteRepo);
    $consultarDemandaCursos     = new ConsultarDemandaCursos($reporteRepo);
    $generarReportePagos        = new GenerarReportePagos($reporteRepo);
    $obtenerEstadisticas        = new ObtenerEstadisticasAprobacion($reporteRepo);
    $exportarReporte            = new ExportarReporte();

    // Auth & Usuario
    $autenticarUsuario = new AutenticarUsuario($usuarioRepo);
    $crearUsuario      = new CrearUsuario($usuarioRepo);
    $consultarUsuario  = new ConsultarUsuario($usuarioRepo);

    // Estudiante base
    $crearEstudiante     = new CrearEstudiante($estudianteRepo, $usuarioRepo);
    $consultarEstudiante = new ConsultarEstudiante($estudianteRepo);
    $listarEstudiantes   = new ListarEstudiantes($estudianteRepo);

    // Curso base
    $crearCurso     = new CrearCurso($cursoRepo);
    $consultarCursos = new ConsultarCursos($cursoRepo);

    // Periodo Académico base
    $consultarPeriodoActivo = new ConsultarPeriodoActivo($periodoRepo);
    $listarPeriodos         = new ListarPeriodos($periodoRepo);

    // Matrícula
    $registrarMatricula = new RegistrarMatricula($matriculaRepo, $estudianteRepo, $periodoRepo, $cursoRepo, $seccionRepo);
    $consultarMatricula = new ConsultarMatricula($matriculaRepo);
    $anularMatricula    = new AnularMatricula($matriculaRepo);

    // 4. Controladores
    $estudianteModuloController = new EstudianteModuloController(
        $registrarEstudianteNuevo,
        $asociarCarreraPlan,
        $historialAcademico,
        $registrarEstadoEst
    );
    $gestionAcademicaController = new GestionAcademicaController(
        $gestionarEstructura,
        $gestionarPlanEstudio,
        $gestionarCursoAcad,
        $definirPrerreq,
        $organizarCiclo,
        $gestionarPeriodo
    );
    $horarioModuloController    = new HorarioModuloController(
        $registrarAulaUC,
        $crearHorarioUC,
        $consultarHorariosUC
    );
    $auditoriaController  = new AuditoriaController($registrarAuditoria, $consultarHistorial);
    $pagoController       = new PagoController(
        $registrarConcepto,
        $consultarConceptos,
        $generarOrdenPago,
        $registrarPago,
        $identificarPendientes,
        $emitirComprobante,
        $historialFinanciero
    );
    $reporteController    = new ReporteController(
        $generarReporteMatriculados,
        $generarListaEstudiantes,
        $generarActaCalificaciones,
        $consultarDemandaCursos,
        $generarReportePagos,
        $obtenerEstadisticas,
        $exportarReporte
    );
    $authController       = new AuthController($autenticarUsuario);
    $usuarioController    = new UsuarioController($crearUsuario, $consultarUsuario);
    $estudianteController = new EstudianteController($crearEstudiante, $consultarEstudiante, $listarEstudiantes);
    $cursoController      = new CursoController($crearCurso, $consultarCursos);
    $periodoController    = new PeriodoAcademicoController($consultarPeriodoActivo, $listarPeriodos);
    $matriculaController  = new MatriculaController($registrarMatricula, $consultarMatricula, $anularMatricula);

    // 5. Middleware de Autenticación
    $authMiddleware = new AuthMiddleware();

    // ==========================================
    // DEFINICIÓN DE RUTAS DE LA API
    // ==========================================

    // Health Check
    $router->get('/api/health', function () {
        ApiResponse::success([
            'status'      => 'UP',
            'system'      => 'SGAU - Matrículas Backend (Clean Architecture)',
            'php_version' => PHP_VERSION,
            'timestamp'   => date('c'),
        ], 'Servicio funcionando correctamente.');
    });

    // ==========================================
    // MÓDULO: HORARIOS Y AULAS (CU-32 a CU-36)
    // ==========================================
    $router->post('/api/horarios/aulas', [$horarioModuloController, 'registrarAula']);   // CU-32 (RN-07)
    $router->get('/api/horarios/aulas',  [$horarioModuloController, 'listarAulas']);     // CU-32
    $router->post('/api/horarios',       [$horarioModuloController, 'crearHorario']);    // CU-33, CU-34, CU-35, CU-36
    $router->get('/api/horarios',        [$horarioModuloController, 'listarHorarios']);  // CU-33

    // ==========================================
    // MÓDULO: ESTUDIANTES (CU-07 a CU-11)
    // ==========================================
    $router->post('/api/modulo-estudiantes/registrar',                      [$estudianteModuloController, 'registrar']);           // CU-07, CU-08
    $router->post('/api/modulo-estudiantes/asociar-carrera',                [$estudianteModuloController, 'asociarCarreraPlan']);  // CU-09
    $router->get('/api/modulo-estudiantes/{idEstudiante}/historial',        [$estudianteModuloController, 'historialAcademico']);  // CU-10
    $router->post('/api/modulo-estudiantes/{idEstudiante}/estado',          [$estudianteModuloController, 'registrarEstado']);     // CU-11

    // ==========================================
    // MÓDULO: GESTIÓN ACADÉMICA (CU-12 a CU-18)
    // ==========================================
    $router->get('/api/gestion-academica/estructura',                        [$gestionAcademicaController, 'listarEstructura']);    // CU-12
    $router->post('/api/gestion-academica/facultades',                       [$gestionAcademicaController, 'registrarFacultad']);   // CU-12
    $router->post('/api/gestion-academica/escuelas',                         [$gestionAcademicaController, 'registrarEscuela']);    // CU-12
    $router->post('/api/gestion-academica/carreras',                         [$gestionAcademicaController, 'registrarCarrera']);    // CU-12
    $router->post('/api/gestion-academica/planes',                           [$gestionAcademicaController, 'crearPlanEstudio']);    // CU-13
    $router->get('/api/gestion-academica/planes',                            [$gestionAcademicaController, 'listarPlanes']);        // CU-13
    $router->post('/api/gestion-academica/cursos',                           [$gestionAcademicaController, 'registrarCurso']);      // CU-14, CU-15
    $router->get('/api/gestion-academica/cursos',                            [$gestionAcademicaController, 'listarCursos']);        // CU-14
    $router->post('/api/gestion-academica/prerrequisitos',                   [$gestionAcademicaController, 'asignarPrerrequisito']);// CU-16
    $router->post('/api/gestion-academica/organizar-ciclo',                  [$gestionAcademicaController, 'asignarCursoACiclo']);  // CU-17
    $router->get('/api/gestion-academica/malla/{idPlan}',                    [$gestionAcademicaController, 'obtenerMalla']);        // CU-17
    $router->post('/api/gestion-academica/periodos',                         [$gestionAcademicaController, 'registrarPeriodo']);    // CU-18

    // ==========================================
    // AUDITORÍA (CU-56, CU-57, CU-58)
    // ==========================================
    $router->post('/api/auditoria',                    [$auditoriaController, 'registrar']);       // CU-56/57
    $router->get('/api/auditoria',                     [$auditoriaController, 'listar']);           // CU-58
    $router->get('/api/auditoria/{id}',                [$auditoriaController, 'buscar']);           // CU-58
    $router->get('/api/auditoria/usuario/{idUsuario}', [$auditoriaController, 'listarPorUsuario']); // CU-58

    // ==========================================
    // PAGOS (CU-43 a CU-48)
    // ==========================================
    $router->post('/api/conceptos-pago',                 [$pagoController, 'registrarConcepto']);
    $router->get('/api/conceptos-pago',                  [$pagoController, 'listarConceptos']);
    $router->get('/api/conceptos-pago/{id}',             [$pagoController, 'consultarConcepto']);
    $router->post('/api/pagos/orden',                    [$pagoController, 'generarOrden']);
    $router->post('/api/pagos',                          [$pagoController, 'registrarPago']);
    $router->get('/api/pagos/pendientes/{idEstudiante}', [$pagoController, 'pagosPendientes']);
    $router->post('/api/pagos/{id}/comprobante',         [$pagoController, 'emitirComprobante']);
    $router->get('/api/pagos/historial/{idEstudiante}',  [$pagoController, 'historialFinanciero']);

    // ==========================================
    // REPORTES (CU-49 a CU-55)
    // ==========================================
    $router->get('/api/reportes/matriculados/{idPeriodo}',        [$reporteController, 'matriculados']);
    $router->get('/api/reportes/seccion/{idSeccion}/estudiantes', [$reporteController, 'estudiantesPorSeccion']);
    $router->get('/api/reportes/curso/{idCurso}/estudiantes',     [$reporteController, 'estudiantesPorCurso']);
    $router->get('/api/reportes/seccion/{idSeccion}/acta',        [$reporteController, 'actaCalificaciones']);
    $router->get('/api/reportes/demanda-cursos/{idPeriodo}',      [$reporteController, 'demandaCursos']);
    $router->get('/api/reportes/pagos/{idPeriodo}',               [$reporteController, 'reportePagos']);
    $router->get('/api/reportes/estadisticas/{idSeccion}',        [$reporteController, 'estadisticasAprobacion']);
    $router->get('/api/reportes/exportar/{tipo}/{formato}',       [$reporteController, 'exportar']);

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
