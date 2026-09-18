<?php

declare(strict_types=1);

namespace App\Presentacion\Routes;

use App\Infrastructure\Database\Connection;
use App\Presentation\Middleware\CorsMiddleware;
use App\Presentacion\Routes\Router;

// Repositorios MySQL
use App\Infrastructure\Repositories\MySQLUsuarioRepositorio;
use App\Infrastructure\Repositories\MySQLEstudianteRepositorio;
use App\Infrastructure\Repositories\MySQLDocenteRepositorio;
use App\Infrastructure\Repositories\MySQLSesionRepositorio;
use App\Infrastructure\Repositories\MySQLAuditoriaRepositorio;
use App\Infrastructure\Repositories\MySQLFacultadRepositorio;
use App\Infrastructure\Repositories\MySQLEscuelaRepositorio;
use App\Infrastructure\Repositories\MySQLCarreraRepositorio;
use App\Infrastructure\Repositories\MySQLPlanEstudioRepositorio;
use App\Infrastructure\Repositories\MySQLCursoRepositorio;
use App\Infrastructure\Repositories\MySQLCurriculumRepositorio;
use App\Infrastructure\Repositories\MySQLPrerequisitoRepositorio;
use App\Infrastructure\Repositories\MySQLPeriodoAcademicoRepositorio;
use App\Infrastructure\Repositories\MySQLSeccionRepositorio;
use App\Infrastructure\Repositories\MySQLHorarioRepositorio;
use App\Infrastructure\Repositories\MySQLAulaRepositorio;
use App\Infrastructure\Repositories\MySQLMatriculaRepositorio;
use App\Infrastructure\Repositories\MySQLDetalleMatriculaRepositorio;
use App\Infrastructure\Repositories\MySQLPagoRepositorio;
use App\Infrastructure\Repositories\MySQLConceptoPagoRepositorio;
use App\Infrastructure\Repositories\MySQLComprobantePagoRepositorio;

// Controladores
use App\Presentacion\Controladores\UsuarioControlador;
use App\Presentacion\Controladores\EstudianteControlador;
use App\Presentacion\Controladores\DocenteControlador;
use App\Presentacion\Controladores\AdministradorControlador;
use App\Presentacion\Controladores\SesionControlador;
use App\Presentacion\Controladores\AuditoriaControlador;
use App\Presentacion\Controladores\FacultadControlador;
use App\Presentacion\Controladores\EscuelaControlador;
use App\Presentacion\Controladores\CarreraControlador;
use App\Presentacion\Controladores\PlanEstudioControlador;
use App\Presentacion\Controladores\CursoControlador;
use App\Presentacion\Controladores\CurriculumControlador;
use App\Presentacion\Controladores\PrerequisitoControlador;
use App\Presentacion\Controladores\PeriodoAcademicoControlador;
use App\Presentacion\Controladores\SeccionControlador;
use App\Presentacion\Controladores\HorarioControlador;
use App\Presentacion\Controladores\AulaControlador;
use App\Presentacion\Controladores\MatriculaControlador;
use App\Presentacion\Controladores\DetalleMatriculaControlador;
use App\Presentacion\Controladores\PagoControlador;
use App\Presentacion\Controladores\ConceptoPagoControlador;
use App\Presentacion\Controladores\ComprobantePagoControlador;

return function (Router $router): void {
    // 1. Middleware Global
    if (class_exists(CorsMiddleware::class)) {
        $router->use(new CorsMiddleware());
    }

    // 2. Conexión a Base de Datos
    $db = Connection::getInstance();

    // 3. Instanciación de Repositorios
    $usuarioRepo     = new MySQLUsuarioRepositorio($db);
    $estudianteRepo  = new MySQLEstudianteRepositorio($db);
    $docenteRepo     = new MySQLDocenteRepositorio($db);
    $sesionRepo      = new MySQLSesionRepositorio($db);
    $auditoriaRepo   = new MySQLAuditoriaRepositorio($db);

    $facultadRepo    = new MySQLFacultadRepositorio($db);
    $escuelaRepo     = new MySQLEscuelaRepositorio($db);
    $carreraRepo     = new MySQLCarreraRepositorio($db);
    $planEstudioRepo = new MySQLPlanEstudioRepositorio($db);
    $cursoRepo       = new MySQLCursoRepositorio($db);
    $curriculumRepo  = new MySQLCurriculumRepositorio($db);
    $prerequisitoRepo = new MySQLPrerequisitoRepositorio($db);

    $periodoRepo     = new MySQLPeriodoAcademicoRepositorio($db);
    $seccionRepo     = new MySQLSeccionRepositorio($db);
    $horarioRepo     = new MySQLHorarioRepositorio($db);
    $aulaRepo        = new MySQLAulaRepositorio($db);
    $matriculaRepo   = new MySQLMatriculaRepositorio($db);
    $detalleRepo     = new MySQLDetalleMatriculaRepositorio($db);

    $pagoRepo        = new MySQLPagoRepositorio($db);
    $conceptoPagoRepo = new MySQLConceptoPagoRepositorio($db);
    $comprobanteRepo = new MySQLComprobantePagoRepositorio($db);

    // 4. Instanciación de Controladores
    $usuarioCtrl     = new UsuarioControlador($usuarioRepo);
    $estudianteCtrl  = new EstudianteControlador($estudianteRepo);
    $docenteCtrl     = new DocenteControlador($docenteRepo);
    $adminCtrl       = new AdministradorControlador($usuarioRepo);
    $sesionCtrl      = new SesionControlador($sesionRepo, $usuarioRepo);
    $auditoriaCtrl   = new AuditoriaControlador($auditoriaRepo);

    $facultadCtrl    = new FacultadControlador($facultadRepo);
    $escuelaCtrl     = new EscuelaControlador($escuelaRepo);
    $carreraCtrl     = new CarreraControlador($carreraRepo);
    $planEstudioCtrl = new PlanEstudioControlador($planEstudioRepo);
    $cursoCtrl       = new CursoControlador($cursoRepo);
    $curriculumCtrl  = new CurriculumControlador($curriculumRepo);
    $prerequisitoCtrl = new PrerequisitoControlador($prerequisitoRepo);

    $periodoCtrl     = new PeriodoAcademicoControlador($periodoRepo);
    $seccionCtrl     = new SeccionControlador($seccionRepo);
    $horarioCtrl     = new HorarioControlador($horarioRepo);
    $aulaCtrl        = new AulaControlador($aulaRepo);
    $matriculaCtrl   = new MatriculaControlador($matriculaRepo);
    $detalleCtrl     = new DetalleMatriculaControlador($detalleRepo);

    $pagoCtrl        = new PagoControlador($pagoRepo);
    $conceptoPagoCtrl = new ConceptoPagoControlador($conceptoPagoRepo);
    $comprobanteCtrl = new ComprobantePagoControlador($comprobanteRepo);

    // 5. Carga de Rutas por Módulos (Opción 2: Granular por Entidad)
    $modulosDir = __DIR__ . '/modulos';

    // --- Módulo: Usuarios y Seguridad ---
    (require "{$modulosDir}/Usuarios/usuario.routes.php")($router, $usuarioCtrl);
    (require "{$modulosDir}/Usuarios/estudiante.routes.php")($router, $estudianteCtrl);
    (require "{$modulosDir}/Usuarios/docente.routes.php")($router, $docenteCtrl);
    (require "{$modulosDir}/Usuarios/administrador.routes.php")($router, $adminCtrl);
    (require "{$modulosDir}/Usuarios/sesion.routes.php")($router, $sesionCtrl);
    (require "{$modulosDir}/Usuarios/auditoria.routes.php")($router, $auditoriaCtrl);

    // --- Módulo: Estructura Académica ---
    (require "{$modulosDir}/Academico/facultad.routes.php")($router, $facultadCtrl);
    (require "{$modulosDir}/Academico/escuela.routes.php")($router, $escuelaCtrl);
    (require "{$modulosDir}/Academico/carrera.routes.php")($router, $carreraCtrl);
    (require "{$modulosDir}/Academico/plan_estudio.routes.php")($router, $planEstudioCtrl);
    (require "{$modulosDir}/Academico/curso.routes.php")($router, $cursoCtrl);
    (require "{$modulosDir}/Academico/curriculum.routes.php")($router, $curriculumCtrl);
    (require "{$modulosDir}/Academico/prerequisito.routes.php")($router, $prerequisitoCtrl);

    // --- Módulo: Matrícula ---
    (require "{$modulosDir}/Matricula/periodo_academico.routes.php")($router, $periodoCtrl);
    (require "{$modulosDir}/Matricula/seccion.routes.php")($router, $seccionCtrl);
    (require "{$modulosDir}/Matricula/horario.routes.php")($router, $horarioCtrl);
    (require "{$modulosDir}/Matricula/aula.routes.php")($router, $aulaCtrl);
    (require "{$modulosDir}/Matricula/matricula.routes.php")($router, $matriculaCtrl);
    (require "{$modulosDir}/Matricula/detalle_matricula.routes.php")($router, $detalleCtrl);

    // --- Módulo: Pagos ---
    (require "{$modulosDir}/Pagos/pago.routes.php")($router, $pagoCtrl);
    (require "{$modulosDir}/Pagos/concepto_pago.routes.php")($router, $conceptoPagoCtrl);
    (require "{$modulosDir}/Pagos/comprobante_pago.routes.php")($router, $comprobanteCtrl);
};
