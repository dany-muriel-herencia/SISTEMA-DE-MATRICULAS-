<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\UsuarioControlador;

return function (Router $router, UsuarioControlador $controller): void {
    $router->post('/api/usuarios', [$controller, 'registrar']);
    $router->get('/api/usuarios', [$controller, 'listar']);
    $router->get('/api/usuarios/{idUsuario}', [$controller, 'buscarPorId']);
    $router->get('/api/usuarios/email/{email}', [$controller, 'buscarPorEmail']);
    $router->put('/api/usuarios/{idUsuario}', [$controller, 'actualizar']);
    $router->delete('/api/usuarios/{idUsuario}', [$controller, 'eliminar']);
};
