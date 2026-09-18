<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\ConceptoPagoControlador;

return function (Router $router, ConceptoPagoControlador $controller): void {
    $router->post('/api/conceptos-pago', [$controller, 'registrar']);
    $router->get('/api/conceptos-pago', [$controller, 'listar']);
    $router->get('/api/conceptos-pago/{idConcepto}', [$controller, 'buscarPorId']);
    $router->put('/api/conceptos-pago/{idConcepto}', [$controller, 'actualizar']);
};
