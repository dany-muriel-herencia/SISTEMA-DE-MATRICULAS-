<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\AulaControlador;

return function (Router $router, AulaControlador $controller): void {
    $router->post('/api/aulas', [$controller, 'registrar']);
    $router->get('/api/aulas', [$controller, 'listar']);
    $router->get('/api/aulas/disponibles', [$controller, 'listarDisponibles']);
    $router->get('/api/aulas/{idAula}', [$controller, 'buscarPorId']);
    $router->put('/api/aulas/{idAula}', [$controller, 'actualizar']);
};
