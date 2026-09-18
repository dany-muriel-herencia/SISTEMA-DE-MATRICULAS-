<?php

declare(strict_types=1);

namespace App\Presentacion\Responses;

class ApiResponse
{
    public static function json(
        bool $success,
        string $message,
        mixed $data = null,
        int $statusCode = 200,
        ?array $errors = null
    ): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Operación exitosa', int $statusCode = 200): void
    {
        self::json(true, $message, $data, $statusCode);
    }

    public static function created(mixed $data = null, string $message = 'Recurso creado exitosamente'): void
    {
        self::json(true, $message, $data, 201);
    }

    public static function error(string $message = 'Ha ocurrido un error', int $statusCode = 400, ?array $errors = null): void
    {
        self::json(false, $message, null, $statusCode, $errors);
    }

    public static function notFound(string $message = 'Recurso no encontrado'): void
    {
        self::json(false, $message, null, 404);
    }

    public static function unauthorized(string $message = 'No autorizado'): void
    {
        self::json(false, $message, null, 401);
    }

    public static function forbidden(string $message = 'Acceso denegado'): void
    {
        self::json(false, $message, null, 403);
    }

    public static function unprocessable(string $message = 'Error de validación', ?array $errors = null): void
    {
        self::json(false, $message, null, 422, $errors);
    }
}
