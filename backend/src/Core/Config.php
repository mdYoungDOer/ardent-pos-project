<?php

namespace ArdentPOS\Core;

class Config
{
    private static array $config = [];

    public static function init(): void
    {
        self::$config = [
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '5432',
                'database' => $_ENV['DB_NAME'] ?? 'ardent_pos',
                'username' => $_ENV['DB_USER'] ?? 'postgres',
                'password' => $_ENV['DB_PASS'] ?? 'password',
            ],
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key',
                'expiry' => (int)($_ENV['JWT_EXPIRY'] ?? 3600),
            ],
            'sendgrid' => [
                'api_key' => $_ENV['SENDGRID_API_KEY'] ?? '',
                'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@ardentpos.com',
                'from_name' => $_ENV['FROM_NAME'] ?? 'Ardent POS',
            ],
            'paystack' => [
                'public_key' => $_ENV['PAYSTACK_PUBLIC_KEY'] ?? '',
                'secret_key' => $_ENV['PAYSTACK_SECRET_KEY'] ?? '',
                'webhook_secret' => $_ENV['PAYSTACK_WEBHOOK_SECRET'] ?? '',
            ],
            'app' => [
                'url' => $_ENV['APP_URL'] ?? 'http://localhost:3000',
                'api_url' => $_ENV['API_URL'] ?? 'http://localhost:8000',
                'env' => $_ENV['APP_ENV'] ?? 'development',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
            'cors' => [
                'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:3000'),
            ],
            'upload' => [
                'max_size' => (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 5242880), // 5MB
                'path' => $_ENV['UPLOAD_PATH'] ?? 'uploads/',
            ],
            'rate_limit' => [
                'requests' => (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100),
                'window' => (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 3600),
            ],
        ];
    }

    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (!isset($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    public static function all(): array
    {
        return self::$config;
    }
}
