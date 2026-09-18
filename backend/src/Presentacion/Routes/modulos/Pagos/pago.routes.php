<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\PagoControlador;

return function (Router $router, PagoControlador $controller): void {
    $router->post('/api/pagos', [$controller, 'registrar']);
    $router->get('/api/pagos/{idPago}', [$controller, 'buscarPorId']);
    $router->get('/api/pagos/estudiante/{idEstudiante}', [$controller, 'listarPorEstudiante']);
    $router->put('/api/pagos/{idPago}', [$controller, 'actualizar']);
};
