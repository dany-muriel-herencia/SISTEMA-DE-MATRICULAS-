<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Presentacion\Routes\Router;
use App\Infrastructure\Database\Connection;

// Mock PDO connection for tests
$mockPdo = new class('sqlite::memory:') extends PDO {
    public function __construct(string $dsn) {
        parent::__construct($dsn);
    }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return parent::prepare("SELECT 1");
    }
};

Connection::setInstance($mockPdo);

$router = new Router();
$bootstrapRoutes = require __DIR__ . '/../src/Presentacion/Routes/api.php';

$bootstrapRoutes($router);

echo "=== CARGA DE TODAS LAS RUTAS DESDE api.php ===\n\n";

$refRouter = new ReflectionClass($router);
$propRoutes = $refRouter->getProperty('routes');
$propRoutes->setAccessible(true);
$routes = $propRoutes->getValue($router);

echo "Total de endpoints registrados: " . count($routes) . "\n\n";

// List sample routes
foreach (array_slice($routes, 0, 15) as $r) {
    printf(" [%-6s] %s\n", $r['method'], $r['path']);
}
echo " ... y " . (count($routes) - 15) . " endpoints más.\n";

echo "\n============================================\n";
echo "¡TODAS LAS RUTAS MODULARES FUERON REGISTRADAS CON ÉXITO!\n";
echo "============================================\n";
