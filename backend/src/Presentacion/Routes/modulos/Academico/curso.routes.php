<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\CursoControlador;

return function (Router $router, CursoControlador $controller): void {
    $router->post('/api/cursos', [$controller, 'registrar']);
    $router->get('/api/cursos', [$controller, 'listar']);
    $router->get('/api/cursos/{idCurso}', [$controller, 'buscarPorId']);
    $router->get('/api/cursos/codigo/{codigo}', [$controller, 'buscarPorCodigo']);
    $router->put('/api/cursos/{idCurso}', [$controller, 'actualizar']);
};
