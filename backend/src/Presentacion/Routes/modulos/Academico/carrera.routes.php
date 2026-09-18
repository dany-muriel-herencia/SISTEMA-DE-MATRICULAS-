<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\CarreraControlador;

return function (Router $router, CarreraControlador $controller): void {
    $router->post('/api/carreras', [$controller, 'registrar']);
    $router->get('/api/carreras', [$controller, 'listar']);
    $router->get('/api/carreras/{idCarrera}', [$controller, 'buscarPorId']);
    $router->get('/api/carreras/codigo/{codigo}', [$controller, 'buscarPorCodigo']);
    $router->put('/api/carreras/{idCarrera}', [$controller, 'actualizar']);
};
