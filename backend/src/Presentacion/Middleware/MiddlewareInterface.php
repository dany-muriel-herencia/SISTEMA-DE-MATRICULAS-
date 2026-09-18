<?php

declare(strict_types=1);

namespace App\Presentacion\Middleware;

interface MiddlewareInterface
{
    /**
     * Procesa la petición y retorna true si continúa, o false / termina si se detiene
     */
    public function handle(array $params = []): bool;
}
