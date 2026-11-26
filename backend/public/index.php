<?php

declare(strict_types=1);

define('BACKEND_BASE_PATH', dirname(__DIR__));
define('PROJECT_ROOT_PATH', dirname(__DIR__, 2));

// 1. Autoloader PSR-4 Nativo (No requiere procesos externos ni ORM)
$composerAutoload = BACKEND_BASE_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = BACKEND_BASE_PATH . '/src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// 2. Cargar Variables de Entorno desde .env en la raíz del proyecto
$envFiles = [PROJECT_ROOT_PATH . '/.env', BACKEND_BASE_PATH . '/.env'];
foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
        break;
    }
}

date_default_timezone_set('America/Lima');

use App\Core\ApiResponse;
use App\Core\Router;
use App\Modules\Academico\AcademicController;
use App\Modules\Auth\AuthController;
use App\Modules\Estudiantes\StudentController;
use App\Modules\Finanzas\FinanceController;
use App\Modules\Horarios\ScheduleController;
use App\Modules\Matricula\EnrollmentController;
use App\Modules\Rectificacion\RectificationController;
use App\Modules\Reportes\ReportController;

$router = new Router();

// Instanciar controladores modulares (1 solo proceso, 1 sola conexión PDO compartida)
$authController = new AuthController();
$studentController = new StudentController();
$academicController = new AcademicController();
$scheduleController = new ScheduleController();
$enrollmentController = new EnrollmentController();
$financeController = new FinanceController();
$reportController = new ReportController();
$rectificationController = new RectificationController();

// =========================================================================
// HEALTH CHECK (Verificación de la API Única)
// =========================================================================
$router->get('/api/v1/health', function (): void {
    ApiResponse::success([
        'service' => 'SGAU-UNJBG API Única Modular (PHP Nativo + PDO - Cero ORM)',
        'status' => 'UP',
        'php_version' => PHP_VERSION,
        'database' => $_ENV['DB_NAME'] ?? 'bd_matricula_universitaria',
        'timestamp' => date('c'),
    ], 'Endpoint único operativo en Apache/XAMPP.');
});

// =========================================================================
// M01 & M02: AUTENTICACIÓN, SEGURIDAD, ROLES Y AUDITORÍA (ISSUE-01, ISSUE-02)
// =========================================================================
$router->post('/api/v1/auth/login', [$authController, 'login']);
$router->post('/api/v1/auth/logout', [$authController, 'logout']);
$router->get('/api/v1/auth/usuarios', [$authController, 'listarUsuarios']);
$router->post('/api/v1/auth/roles/asignar', [$authController, 'asignarRol']);
$router->get('/api/v1/auth/auditoria', [$authController, 'listarAuditoria']);

// =========================================================================
// M03: GESTIÓN DE ESTUDIANTES Y KARDEX (ISSUE-03)
// =========================================================================
$router->get('/api/v1/students', [$studentController, 'listar']);
$router->get('/api/v1/students/{idEstudiante}/kardex', [$studentController, 'obtenerKardex']);
$router->get('/api/v1/students/{idEstudiante}/ficha-medica', [$studentController, 'obtenerFichaMedica']);

// =========================================================================
// M04: GESTIÓN CURRICULAR, PLANES Y PRERREQUISITOS (ISSUE-04)
// =========================================================================
$router->get('/api/v1/academic/facultades', [$academicController, 'listarFacultades']);
$router->get('/api/v1/academic/planes/{idPlan}/malla', [$academicController, 'obtenerMalla']);
$router->get('/api/v1/academic/periodos/activo', [$academicController, 'obtenerPeriodoActivo']);
$router->post('/api/v1/academic/prerrequisitos/validar', [$academicController, 'validarPrerrequisitos']);

// =========================================================================
// M07: HORARIOS, AULAS Y DETECCIÓN DE CRUCES (ISSUE-08)
// =========================================================================
$router->get('/api/v1/schedules/vacantes/{idPeriodo}', [$scheduleController, 'consultarVacantes']);
$router->get('/api/v1/schedules/secciones/{idCurso}', [$scheduleController, 'listarSeccionesPorCurso']);
$router->get('/api/v1/schedules/aulas', [$scheduleController, 'listarAulas']);
$router->post('/api/v1/schedules/validar-cruce', [$scheduleController, 'validarCruceHorario']);

// =========================================================================
// M05 & M06: MATRÍCULA TRANSACCIONAL, COLA VIRTUAL Y EXCEPCIONES (ISSUE-05, ISSUE-07)
// =========================================================================
$router->post('/api/v1/enrollment/registrar-completa', [$enrollmentController, 'registrarMatriculaCompleta']);
$router->post('/api/v1/enrollment/pendiente', [$enrollmentController, 'crearPendiente']);
$router->post('/api/v1/enrollment/confirmar', [$enrollmentController, 'confirmar']);
$router->post('/api/v1/enrollment/curso/inscribir', [$enrollmentController, 'inscribirCurso']);
$router->post('/api/v1/enrollment/curso/retirar', [$enrollmentController, 'retirarCurso']);
$router->post('/api/v1/enrollment/anular', [$enrollmentController, 'anular']);
$router->post('/api/v1/enrollment/excepcion', [$enrollmentController, 'aplicarExcepcion']);
$router->get('/api/v1/enrollment/consultar/{idEstudiante}/{idPeriodo}', [$enrollmentController, 'consultarMatricula']);

// Cola de espera de turnos en vivo (M06)
$router->post('/api/v1/enrollment/cola/ingresar', [$enrollmentController, 'ingresarCola']);
$router->post('/api/v1/enrollment/cola/atender-siguiente', [$enrollmentController, 'atenderSiguienteCola']);
$router->post('/api/v1/enrollment/cola/finalizar', [$enrollmentController, 'finalizarTurnoCola']);
$router->post('/api/v1/enrollment/cola/abandonar', [$enrollmentController, 'abandonarCola']);
$router->get('/api/v1/enrollment/cola/posicion/{idEstudiante}/{idPeriodo}', [$enrollmentController, 'consultarPosicionCola']);

// Lista de espera por sección llena
$router->post('/api/v1/enrollment/espera/inscribir', [$enrollmentController, 'inscribirListaEspera']);
$router->post('/api/v1/enrollment/espera/cancelar', [$enrollmentController, 'cancelarListaEspera']);
$router->post('/api/v1/enrollment/espera/promover', [$enrollmentController, 'promoverListaEspera']);
$router->get('/api/v1/enrollment/espera/{idMatricula}', [$enrollmentController, 'consultarListaEspera']);

// =========================================================================
// M08: FINANZAS, CAJA Y TESORERÍA (ISSUE-09)
// =========================================================================
$router->get('/api/v1/finance/conceptos', [$financeController, 'listarConceptos']);
$router->get('/api/v1/finance/deudas/{idEstudiante}', [$financeController, 'listarDeudasEstudiante']);
$router->post('/api/v1/finance/pagos/ventanilla', [$financeController, 'registrarPago']);

// =========================================================================
// M09 & M05-QR: REPORTES, CONSTANCIA QR Y ACTAS CON HASH SHA-256 (ISSUE-06, ISSUE-10)
// =========================================================================
$router->get('/api/v1/reporting/constancia/{idMatricula}', [$reportController, 'emitirConstanciaQr']);
$router->get('/api/v1/reporting/nomina/{idSeccion}', [$reportController, 'obtenerNominaSeccion']);
$router->get('/api/v1/reporting/actas/{idSeccion}', [$reportController, 'emitirActaEvaluacion']);
$router->post('/api/v1/reporting/cierre-semestre', [$reportController, 'cerrarSemestre']);

// =========================================================================
// M10: RECTIFICACIÓN DE MATRÍCULA (ISSUE-11)
// =========================================================================
$router->get('/api/v1/rectification/pendientes', [$rectificationController, 'listarPendientes']);
$router->post('/api/v1/rectification/solicitar', [$rectificationController, 'solicitar']);
$router->post('/api/v1/rectification/aprobar', [$rectificationController, 'aprobar']);
$router->post('/api/v1/rectification/rechazar', [$rectificationController, 'rechazar']);

// Despachar solicitud
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
