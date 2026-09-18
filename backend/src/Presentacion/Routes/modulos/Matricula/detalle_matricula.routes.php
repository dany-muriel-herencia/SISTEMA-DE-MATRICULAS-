<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\DetalleMatriculaControlador;

return function (Router $router, DetalleMatriculaControlador $controller): void {
    $router->post('/api/detalles-matricula', [$controller, 'registrar']);
    $router->get('/api/detalles-matricula/{idDetalle}', [$controller, 'buscarPorId']);
    $router->get('/api/detalles-matricula/matricula/{idMatricula}', [$controller, 'listarPorMatricula']);
    $router->put('/api/detalles-matricula/{idDetalle}', [$controller, 'actualizar']);
    $router->delete('/api/detalles-matricula/{idDetalle}', [$controller, 'eliminar']);
};
