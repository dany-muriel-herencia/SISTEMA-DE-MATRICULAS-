<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\AdministradorControlador;

return function (Router $router, AdministradorControlador $controller): void {
    $router->post('/api/administradores', [$controller, 'registrar']);
    $router->get('/api/administradores/{idUsuario}', [$controller, 'buscarPorId']);
    $router->put('/api/administradores/{idUsuario}', [$controller, 'actualizar']);
};
