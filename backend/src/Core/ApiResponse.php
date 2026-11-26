<?php

declare(strict_types=1);

namespace App\Core;

final class ApiResponse
{
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function success(mixed $data = null, string $message = 'Operación exitosa.', int $status = 200): void
    {
        self::json([
            'ok' => true,
            'codigo' => 'EXITO',
            'mensaje' => $message,
            'data' => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, string $code = 'ERROR', mixed $details = null): void
    {
        $payload = [
            'ok' => false,
            'codigo' => $code,
            'mensaje' => $message,
        ];
        if ($details !== null) {
            $payload['detalles'] = $details;
        }
        self::json($payload, $status);
    }
}
