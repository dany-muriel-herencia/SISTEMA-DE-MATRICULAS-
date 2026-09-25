<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'SGAU - Sistema de Gestión Académica y Matrículas',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'port' => (int)($_ENV['APP_PORT'] ?? 8000),
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Lima',
    'session_expiration' => (int)($_ENV['SESSION_EXPIRATION'] ?? 86400),
];
