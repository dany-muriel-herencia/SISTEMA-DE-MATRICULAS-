<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
        ];
        return $this;
    }

    public static function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) {
            return $_POST ?: [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function dispatch(string $requestMethod, string $requestUri): void
    {
        // Cabeceras CORS para consumo desde el Frontend
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        $method = strtoupper($requestMethod);
        if ($method === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        $uri = parse_url($requestUri, PHP_URL_PATH) ?? '/';
        $uri = rawurldecode($uri);

        // Normalizar la ruta a partir de /api/... para que funcione en cualquier subcarpeta de XAMPP
        if (($apiPos = strpos($uri, '/api/')) !== false) {
            $uri = substr($uri, $apiPos);
        } elseif (str_ends_with($uri, '/index.php')) {
            $uri = '/api/v1/health';
        }

        // Permitir tanto /api/v1/... como /api/...
        $normalizedUri = preg_replace('#^/api/(?!v1/)#', '/api/v1/', $uri) ?? $uri;

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $normalizedUri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                try {
                    $handler = $route['handler'];
                    if (is_array($handler)) {
                        [$controllerOrClass, $action] = $handler;
                        $controller = is_string($controllerOrClass) ? new $controllerOrClass() : $controllerOrClass;
                        call_user_func_array([$controller, $action], array_values($params));
                    } else {
                        call_user_func_array($handler, array_values($params));
                    }
                    return;
                } catch (Throwable $e) {
                    ApiResponse::error('Error en el servidor: ' . $e->getMessage(), 500, 'ERROR_SERVIDOR');
                    return;
                }
            }
        }

        ApiResponse::error("Endpoint no encontrado: {$method} {$normalizedUri}", 404, 'NO_ENCONTRADO');
    }
}
