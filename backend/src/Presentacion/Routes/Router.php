<?php

declare(strict_types=1);

namespace App\Presentation\Routes;

use App\Presentation\Responses\ApiResponse;
use Closure;
use Throwable;

class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];

    public function use(object $middleware): self
    {
        $this->globalMiddleware[] = $middleware;
        return $this;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function options(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->addRoute('OPTIONS', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, callable|array $handler, array $middleware = []): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
        return $this;
    }

    public function dispatch(string $requestMethod, string $requestUri): void
    {
        $method = strtoupper($requestMethod);
        $uri = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // Ejecutar Middleware Global
        foreach ($this->globalMiddleware as $mid) {
            if (method_exists($mid, 'handle')) {
                $continue = $mid->handle();
                if ($continue === false) {
                    return;
                }
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Ejecutar Middleware de Ruta
                foreach ($route['middleware'] as $routeMid) {
                    if (method_exists($routeMid, 'handle')) {
                        $continue = $routeMid->handle($params);
                        if ($continue === false) {
                            return;
                        }
                    }
                }

                // Invocar Handler
                $handler = $route['handler'];
                try {
                    if (is_array($handler)) {
                        [$controller, $action] = $handler;
                        call_user_func_array([$controller, $action], array_values($params));
                    } elseif (is_callable($handler)) {
                        call_user_func_array($handler, array_values($params));
                    }
                    return;
                } catch (Throwable $e) {
                    ApiResponse::error("Error interno del servidor: " . $e->getMessage(), 500);
                    return;
                }
            }
        }

        ApiResponse::notFound("Endpoint no encontrado: {$method} {$uri}");
    }
}
