<?php

declare(strict_types=1);

namespace App\Presentacion\Middleware;

use App\Presentacion\Responses\ApiResponse;

class RoleMiddleware implements MiddlewareInterface
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = [])
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(array $params = []): bool
    {
        // Obtener rol del usuario autenticado (desde header o contexto)
        $userRole = $params['user_role'] ?? $_SERVER['HTTP_X_USER_ROLE'] ?? 'ADMIN';

        if (!empty($this->allowedRoles) && !in_array($userRole, $this->allowedRoles, true)) {
            ApiResponse::forbidden("No cuenta con los permisos necesarios para realizar esta acción.");
            return false;
        }

        return true;
    }
}
