<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\AuditoriaControlador;

return function (Router $router, AuditoriaControlador $controller): void {
    $router->post('/api/auditoria', [$controller, 'registrar']);
    $router->get('/api/auditoria', [$controller, 'listar']);
    $router->get('/api/auditoria/{idAuditoria}', [$controller, 'buscarPorId']);
    $router->get('/api/auditoria/usuario/{idUsuario}', [$controller, 'listarPorUsuario']);
};
