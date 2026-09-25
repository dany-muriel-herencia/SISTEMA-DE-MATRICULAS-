<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    public static function createConnection(): PDO
    {
        $configFile = dirname(__DIR__, 3) . '/config/database.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $dbConfig = $config['connections']['mysql'] ?? [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => (int)($_ENV['DB_PORT'] ?? 3306),
            'database' => $_ENV['DB_DATABASE'] ?? 'sgau',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $dbConfig['driver'] ?? 'mysql',
            $dbConfig['host'] ?? '127.0.0.1',
            $dbConfig['port'] ?? 3306,
            $dbConfig['database'] ?? 'sgau',
            $dbConfig['charset'] ?? 'utf8mb4'
        );

        $options = $dbConfig['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            return new PDO(
                $dsn,
                $dbConfig['username'] ?? 'root',
                $dbConfig['password'] ?? '',
                $options
            );
        } catch (PDOException $e) {
            throw new RuntimeException("Error de conexión a la base de datos: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function setInstance(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
