<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\PrerequisitoControlador;

return function (Router $router, PrerequisitoControlador $controller): void {
    $router->post('/api/prerequisitos', [$controller, 'registrar']);
    $router->get('/api/prerequisitos', [$controller, 'listar']);
    $router->get('/api/prerequisitos/{idPrerequisito}', [$controller, 'buscarPorId']);
    $router->delete('/api/prerequisitos/{idPrerequisito}', [$controller, 'eliminar']);
};
