<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\PeriodoAcademicoControlador;

return function (Router $router, PeriodoAcademicoControlador $controller): void {
    $router->post('/api/periodos', [$controller, 'registrar']);
    $router->get('/api/periodos', [$controller, 'listar']);
    $router->get('/api/periodos/activo', [$controller, 'obtenerPeriodoActivo']);
    $router->get('/api/periodos/{idPeriodo}', [$controller, 'buscarPorId']);
    $router->put('/api/periodos/{idPeriodo}', [$controller, 'actualizar']);
};
