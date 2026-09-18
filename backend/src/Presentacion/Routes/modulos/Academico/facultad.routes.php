<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\FacultadControlador;

return function (Router $router, FacultadControlador $controller): void {
    $router->post('/api/facultades', [$controller, 'registrar']);
    $router->get('/api/facultades', [$controller, 'listar']);
    $router->get('/api/facultades/{idFacultad}', [$controller, 'buscarPorId']);
    $router->put('/api/facultades/{idFacultad}', [$controller, 'actualizar']);
};
