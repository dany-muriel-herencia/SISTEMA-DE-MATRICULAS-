<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\DocenteControlador;

return function (Router $router, DocenteControlador $controller): void {
    $router->post('/api/docentes', [$controller, 'registrar']);
    $router->get('/api/docentes/{idUsuario}', [$controller, 'buscarPorId']);
    $router->get('/api/docentes/codigo/{codigo}', [$controller, 'buscarPorCodigo']);
    $router->put('/api/docentes/{idUsuario}', [$controller, 'actualizar']);
};
