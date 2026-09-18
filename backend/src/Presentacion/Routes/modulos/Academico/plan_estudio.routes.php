<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\PlanEstudioControlador;

return function (Router $router, PlanEstudioControlador $controller): void {
    $router->post('/api/planes-estudio', [$controller, 'registrar']);
    $router->get('/api/planes-estudio', [$controller, 'listar']);
    $router->get('/api/planes-estudio/{idPlan}', [$controller, 'buscarPorId']);
    $router->put('/api/planes-estudio/{idPlan}', [$controller, 'actualizar']);
};
