<?php

declare(strict_types=1);

namespace App\Presentation\Middleware;

use App\Presentation\Responses\ApiResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(array $params = []): bool
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            ApiResponse::unauthorized("Token de autorización no proporcionado o inválido.");
            return false;
        }

        $token = $matches[1];
        // En una implementación completa JWT se valida la firma
        // Para desarrollo/mock permitimos tokens válidos o tokens de prueba
        if (empty($token)) {
            ApiResponse::unauthorized("Token inválido.");
            return false;
        }

        return true;
    }
}
