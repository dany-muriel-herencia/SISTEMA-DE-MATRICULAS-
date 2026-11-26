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
$router->post('/api/v1/auth/login', [AuthController::class, 'login']);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/v1/auth/usuarios', [AuthController::class, 'listarUsuarios']);
$router->post('/api/v1/auth/roles/asignar', [AuthController::class, 'asignarRol']);
$router->get('/api/v1/auth/auditoria', [AuthController::class, 'listarAuditoria']);

// =========================================================================
// M03: GESTIÓN DE ESTUDIANTES Y KARDEX (ISSUE-03)
// =========================================================================
$router->get('/api/v1/students', [StudentController::class, 'listar']);
$router->get('/api/v1/students/{idEstudiante}/kardex', [StudentController::class, 'obtenerKardex']);
$router->get('/api/v1/students/{idEstudiante}/ficha-medica', [StudentController::class, 'obtenerFichaMedica']);

// =========================================================================
// M04: GESTIÓN CURRICULAR, PLANES Y PRERREQUISITOS (ISSUE-04)
// =========================================================================
$router->get('/api/v1/academic/facultades', [AcademicController::class, 'listarFacultades']);
$router->get('/api/v1/academic/planes/{idPlan}/malla', [AcademicController::class, 'obtenerMalla']);
$router->get('/api/v1/academic/periodos/activo', [AcademicController::class, 'obtenerPeriodoActivo']);
$router->post('/api/v1/academic/prerrequisitos/validar', [AcademicController::class, 'validarPrerrequisitos']);

// =========================================================================
// M07: HORARIOS, AULAS Y DETECCIÓN DE CRUCES (ISSUE-08)
// =========================================================================
$router->get('/api/v1/schedules/vacantes/{idPeriodo}', [ScheduleController::class, 'consultarVacantes']);
$router->get('/api/v1/schedules/secciones/{idCurso}', [ScheduleController::class, 'listarSeccionesPorCurso']);
$router->get('/api/v1/schedules/aulas', [ScheduleController::class, 'listarAulas']);
$router->post('/api/v1/schedules/validar-cruce', [ScheduleController::class, 'validarCruceHorario']);

// =========================================================================
// M05 & M06: MATRÍCULA TRANSACCIONAL, COLA VIRTUAL Y EXCEPCIONES (ISSUE-05, ISSUE-07)
// =========================================================================
$router->post('/api/v1/enrollment/registrar-completa', [EnrollmentController::class, 'registrarMatriculaCompleta']);
$router->post('/api/v1/enrollment/pendiente', [EnrollmentController::class, 'crearPendiente']);
$router->post('/api/v1/enrollment/confirmar', [EnrollmentController::class, 'confirmar']);
$router->post('/api/v1/enrollment/curso/inscribir', [EnrollmentController::class, 'inscribirCurso']);
$router->post('/api/v1/enrollment/curso/retirar', [EnrollmentController::class, 'retirarCurso']);
$router->post('/api/v1/enrollment/anular', [EnrollmentController::class, 'anular']);
$router->post('/api/v1/enrollment/excepcion', [EnrollmentController::class, 'aplicarExcepcion']);
$router->get('/api/v1/enrollment/consultar/{idEstudiante}/{idPeriodo}', [EnrollmentController::class, 'consultarMatricula']);

// Cola de espera de turnos en vivo (M06)
$router->post('/api/v1/enrollment/cola/ingresar', [EnrollmentController::class, 'ingresarCola']);
$router->post('/api/v1/enrollment/cola/atender-siguiente', [EnrollmentController::class, 'atenderSiguienteCola']);
$router->post('/api/v1/enrollment/cola/finalizar', [EnrollmentController::class, 'finalizarTurnoCola']);
$router->post('/api/v1/enrollment/cola/abandonar', [EnrollmentController::class, 'abandonarCola']);
$router->get('/api/v1/enrollment/cola/posicion/{idEstudiante}/{idPeriodo}', [EnrollmentController::class, 'consultarPosicionCola']);

// Lista de espera por sección llena
$router->post('/api/v1/enrollment/espera/inscribir', [EnrollmentController::class, 'inscribirListaEspera']);
$router->post('/api/v1/enrollment/espera/cancelar', [EnrollmentController::class, 'cancelarListaEspera']);
$router->post('/api/v1/enrollment/espera/promover', [EnrollmentController::class, 'promoverListaEspera']);
$router->get('/api/v1/enrollment/espera/{idMatricula}', [EnrollmentController::class, 'consultarListaEspera']);

// =========================================================================
// M08: FINANZAS, CAJA Y TESORERÍA (ISSUE-09)
// =========================================================================
$router->get('/api/v1/finance/conceptos', [FinanceController::class, 'listarConceptos']);
$router->get('/api/v1/finance/deudas/{idEstudiante}', [FinanceController::class, 'listarDeudasEstudiante']);
$router->post('/api/v1/finance/pagos/ventanilla', [FinanceController::class, 'registrarPago']);

// =========================================================================
// M09 & M05-QR: REPORTES, CONSTANCIA QR Y ACTAS CON HASH SHA-256 (ISSUE-06, ISSUE-10)
// =========================================================================
$router->get('/api/v1/reporting/constancia/{idMatricula}', [ReportController::class, 'emitirConstanciaQr']);
$router->get('/api/v1/reporting/nomina/{idSeccion}', [ReportController::class, 'obtenerNominaSeccion']);
$router->get('/api/v1/reporting/actas/{idSeccion}', [ReportController::class, 'emitirActaEvaluacion']);
$router->post('/api/v1/reporting/cierre-semestre', [ReportController::class, 'cerrarSemestre']);

// =========================================================================
// M10: RECTIFICACIÓN DE MATRÍCULA (ISSUE-11)
// =========================================================================
$router->get('/api/v1/rectification/pendientes', [RectificationController::class, 'listarPendientes']);
$router->post('/api/v1/rectification/solicitar', [RectificationController::class, 'solicitar']);
$router->post('/api/v1/rectification/aprobar', [RectificationController::class, 'aprobar']);
$router->post('/api/v1/rectification/rechazar', [RectificationController::class, 'rechazar']);

// Despachar solicitud
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
