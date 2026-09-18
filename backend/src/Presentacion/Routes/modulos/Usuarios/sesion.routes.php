<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\SesionControlador;

return function (Router $router, SesionControlador $controller): void {
    $router->post('/api/sesiones/login', [$controller, 'iniciarSesion']);
    $router->get('/api/sesiones/token/{token}', [$controller, 'buscarPorToken']);
    $router->post('/api/sesiones/logout', function () use ($controller) {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_POST['token'] ?? '';
        $token = str_replace('Bearer ', '', $token);
        return $controller->cerrarSesion($token);
    });
    $router->get('/api/sesiones/usuario/{idUsuario}', [$controller, 'listarPorUsuario']);
};
