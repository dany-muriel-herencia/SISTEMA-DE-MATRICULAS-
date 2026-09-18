<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\ComprobantePagoControlador;

return function (Router $router, ComprobantePagoControlador $controller): void {
    $router->post('/api/comprobantes', [$controller, 'emitir']);
    $router->get('/api/comprobantes/{idComprobante}', [$controller, 'buscarPorId']);
    $router->get('/api/comprobantes/pago/{idPago}', [$controller, 'buscarPorPago']);
    $router->put('/api/comprobantes/{idComprobante}', [$controller, 'actualizar']);
};
