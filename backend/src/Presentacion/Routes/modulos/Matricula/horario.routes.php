<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\HorarioControlador;

return function (Router $router, HorarioControlador $controller): void {
    $router->post('/api/horarios', [$controller, 'registrar']);
    $router->get('/api/horarios', [$controller, 'listar']);
    $router->get('/api/horarios/{idHorario}', [$controller, 'buscarPorId']);
    $router->put('/api/horarios/{idHorario}', [$controller, 'actualizar']);
    $router->delete('/api/horarios/{idHorario}', [$controller, 'eliminar']);
};
