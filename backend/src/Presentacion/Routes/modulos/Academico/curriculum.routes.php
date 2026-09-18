<?php

declare(strict_types=1);

use App\Presentacion\Routes\Router;
use App\Presentacion\Controladores\CurriculumControlador;

return function (Router $router, CurriculumControlador $controller): void {
    $router->post('/api/curriculums', [$controller, 'registrar']);
    $router->get('/api/curriculums', [$controller, 'listar']);
    $router->get('/api/curriculums/{idCurriculum}', [$controller, 'buscarPorId']);
    $router->put('/api/curriculums/{idCurriculum}', [$controller, 'actualizar']);
};
