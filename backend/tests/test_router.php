<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Presentation\Routes\Router;

$router = new Router();
$registerRoutes = require dirname(__DIR__) . '/src/Presentation/Routes/api.php';
$registerRoutes($router);

echo "Router cargado y configurado exitosamente.\n";
