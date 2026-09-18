<?php

declare(strict_types=1);

namespace App\Presentacion\Middleware;

class CorsMiddleware implements MiddlewareInterface
{
    public function handle(array $params = []): bool
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-Role");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return true;
    }
}
