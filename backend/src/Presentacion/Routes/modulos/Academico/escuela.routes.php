<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\EscuelaControlador;

return function (Router $router, EscuelaControlador $controller): void {
    $router->post('/api/escuelas', [$controller, 'registrar']);
    $router->get('/api/escuelas', [$controller, 'listar']);
    $router->get('/api/escuelas/{idEscuela}', [$controller, 'buscarPorId']);
    $router->put('/api/escuelas/{idEscuela}', [$controller, 'actualizar']);
};
