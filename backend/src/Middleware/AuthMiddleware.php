<?php

namespace ArdentPOS\Middleware;

use ArdentPOS\Core\Config;
use ArdentPOS\Core\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public static function handle(): void
    {
        $token = self::getTokenFromRequest();
        
        if (!$token) {
            self::unauthorized('Token not provided');
            return;
        }

        try {
            $decoded = JWT::decode($token, new Key(Config::get('jwt.secret'), 'HS256'));
            
            // Verify user exists and is active
            $user = Database::fetch(
                'SELECT * FROM users WHERE id = ? AND status = ?',
                [$decoded->user_id, 'active']
            );

            if (!$user) {
                self::unauthorized('Invalid token');
                return;
            }

            // Store user data in globals for access in controllers
            $GLOBALS['current_user'] = $user;
            $GLOBALS['current_tenant_id'] = $user['tenant_id'];

        } catch (\Exception $e) {
            self::unauthorized('Invalid token');
        }
    }

    private static function getTokenFromRequest(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function unauthorized(string $message): void
    {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized', 'message' => $message]);
        exit;
    }

    public static function requireRole(string $role): void
    {
        $currentUser = $GLOBALS['current_user'] ?? null;
        
        if (!$currentUser || $currentUser['role'] !== $role) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden', 'message' => 'Insufficient permissions']);
            exit;
        }
    }

    public static function requireAnyRole(array $roles): void
    {
        $currentUser = $GLOBALS['current_user'] ?? null;
        
        if (!$currentUser || !in_array($currentUser['role'], $roles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden', 'message' => 'Insufficient permissions']);
            exit;
        }
    }
}
