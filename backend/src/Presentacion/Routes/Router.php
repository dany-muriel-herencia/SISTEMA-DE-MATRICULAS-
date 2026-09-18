<?php

declare(strict_types=1);

namespace App\Presentacion\Routes;

use App\Presentacion\Responses\ApiResponse;
use Closure;
use ReflectionMethod;
use ReflectionFunction;
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

        // Obtener cuerpo de petición (JSON o POST)
        $rawInput = file_get_contents('php://input');
        $body = !empty($rawInput) ? (json_decode($rawInput, true) ?? $_POST) : $_POST;

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
                    $response = null;

                    if (is_array($handler)) {
                        [$controller, $action] = $handler;
                        $refMethod = new ReflectionMethod($controller, $action);
                        $methodParams = $refMethod->getParameters();

                        $args = [];
                        foreach ($methodParams as $param) {
                            $pName = $param->getName();
                            $pType = $param->getType()?->getName();

                            if (isset($params[$pName])) {
                                $val = $params[$pName];
                                $args[] = ($pType === 'int') ? (int)$val : (($pType === 'float') ? (float)$val : (string)$val);
                            } elseif ($pType === 'array' || $pName === 'datos' || $pName === 'input') {
                                // Combinar parámetros de ruta con el body
                                $combined = array_merge($body, $params);
                                $args[] = $combined;
                            } else {
                                $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                            }
                        }

                        $response = $refMethod->invokeArgs($controller, $args);
                    } elseif (is_callable($handler)) {
                        $refFunc = new ReflectionFunction(Closure::fromCallable($handler));
                        $funcParams = $refFunc->getParameters();

                        $args = [];
                        foreach ($funcParams as $param) {
                            $pName = $param->getName();
                            $pType = $param->getType()?->getName();

                            if (isset($params[$pName])) {
                                $val = $params[$pName];
                                $args[] = ($pType === 'int') ? (int)$val : (($pType === 'float') ? (float)$val : (string)$val);
                            } elseif ($pType === 'array' || $pName === 'datos' || $pName === 'input') {
                                $args[] = array_merge($body, $params);
                            } else {
                                $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                            }
                        }

                        $response = call_user_func_array($handler, $args);
                    }

                    // Si el controlador retornó un array de respuesta estandarizada
                    if (is_array($response) && isset($response['success'])) {
                        $status = $response['success'] 
                            ? ($method === 'POST' ? 201 : 200) 
                            : (isset($response['message']) && str_contains(strtolower($response['message']), 'no encontrado') ? 404 : 400);

                        ApiResponse::json(
                            (bool)$response['success'],
                            (string)($response['message'] ?? ''),
                            $response['data'] ?? null,
                            $status
                        );
                        return;
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
