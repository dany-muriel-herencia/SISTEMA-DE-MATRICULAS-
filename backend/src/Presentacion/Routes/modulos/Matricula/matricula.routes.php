<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\MatriculaControlador;

return function (Router $router, MatriculaControlador $controller): void {
    $router->post('/api/matriculas', [$controller, 'registrar']);
    $router->get('/api/matriculas/{idMatricula}', [$controller, 'buscarPorId']);
    $router->get('/api/matriculas/codigo/{codigo}', [$controller, 'buscarPorCodigo']);
    $router->get('/api/matriculas/estudiante/{idEstudiante}', [$controller, 'listarPorEstudiante']);
    $router->get('/api/matriculas/periodo/{idPeriodo}', [$controller, 'listarPorPeriodo']);
    $router->put('/api/matriculas/{idMatricula}', [$controller, 'actualizar']);
};
