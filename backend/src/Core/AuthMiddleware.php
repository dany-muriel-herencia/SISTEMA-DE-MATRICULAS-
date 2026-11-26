<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autenticación JWT nativa con HMAC-SHA256 (sin dependencias externas ni ORM).
 */
final class AuthMiddleware
{
    public static function generateToken(array $claims, int $ttlSeconds = 28800): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'super_secret_jwt_key_unjbg_2026';
        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadData = array_merge($claims, [
            'iat' => time(),
            'exp' => time() + $ttlSeconds,
        ]);
        $payload = self::base64UrlEncode(json_encode($payloadData, JSON_UNESCAPED_UNICODE));
        $signature = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        return "{$header}.{$payload}.{$signature}";
    }

    public static function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $secret = $_ENV['JWT_SECRET'] ?? 'super_secret_jwt_key_unjbg_2026';
        $expected = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $decoded = json_decode(self::base64UrlDecode($payload), true);
        if (!is_array($decoded) || (isset($decoded['exp']) && time() > (int)$decoded['exp'])) {
            return null;
        }

        return $decoded;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return (string)base64_decode(strtr($data, '-_', '+/'));
    }
}
