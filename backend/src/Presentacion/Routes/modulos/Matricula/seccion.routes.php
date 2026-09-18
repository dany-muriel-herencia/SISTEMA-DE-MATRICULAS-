<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\SeccionControlador;

return function (Router $router, SeccionControlador $controller): void {
    $router->post('/api/secciones', [$controller, 'registrar']);
    $router->get('/api/secciones', [$controller, 'listar']);
    $router->get('/api/secciones/{idSeccion}', [$controller, 'buscarPorId']);
    $router->put('/api/secciones/{idSeccion}', [$controller, 'actualizar']);
};
