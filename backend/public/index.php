<?php

declare(strict_types=1);

// Definir constante del directorio raíz del backend
define('BASE_PATH', dirname(__DIR__));

// 1. Cargar Autoloader de Composer
$autoloadFile = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require_once $autoloadFile;
} else {
    // Autoloader fallback PSR-4 si vendor/autoload.php aún no se ha generado
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = BASE_PATH . '/src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = str_replace('Dominio\\Repositorios\\', 'Dominio\\Repositories\\', substr($class, $len));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

// 2. Cargar Variables de Entorno desde .env
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

// 3. Configurar entorno y manejo de errores
$appConfig = file_exists(BASE_PATH . '/config/app.php') ? require BASE_PATH . '/config/app.php' : [];
date_default_timezone_set($appConfig['timezone'] ?? 'America/Lima');

if (!empty($appConfig['debug'])) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

set_exception_handler(function (\Throwable $e): void {
    error_log((string)$e);
    \App\Presentation\Responses\ApiResponse::error('Error interno del servidor.', 500);
});
(new \App\Presentation\Middleware\CorsMiddleware())->handle();

// 4. Inicializar Enrutador y Cargar Rutas
use App\Presentation\Routes\Router;

$router = new Router();

$routesRegistrar = require BASE_PATH . '/src/Presentation/Routes/api.php';
$routesRegistrar($router);

// 5. Despachar Solicitud HTTP
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Limpiar base path si se ejecuta en subcarpeta en Apache/Laragon (ej. /software%20de%20datos/...)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = dirname($scriptName);
if (PHP_SAPI !== 'cli-server' && $scriptDir !== '/' && $scriptDir !== '\\' && str_starts_with($requestUri, $scriptDir)) {
    $requestUri = substr($requestUri, strlen($scriptDir));
}

$router->dispatch($requestMethod, $requestUri);
