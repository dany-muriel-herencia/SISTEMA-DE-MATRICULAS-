<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\EstudianteControlador;

return function (Router $router, EstudianteControlador $controller): void {
    $router->post('/api/estudiantes', [$controller, 'registrar']);
    $router->get('/api/estudiantes/{idUsuario}', [$controller, 'buscarPorId']);
    $router->get('/api/estudiantes/codigo/{codigo}', [$controller, 'buscarPorCodigo']);
    $router->get('/api/estudiantes/dni/{dni}', [$controller, 'buscarPorDni']);
    $router->put('/api/estudiantes/{idUsuario}', [$controller, 'actualizar']);
};
