<?php

namespace ArdentPOS\Controllers;

use ArdentPOS\Core\Database;
use ArdentPOS\Core\Config;
use Firebase\JWT\JWT;
use Ramsey\Uuid\Uuid;

class AuthController
{
    public function register(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $errors = $this->validateRegistration($input);
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        try {
            Database::beginTransaction();

            // Create tenant
            $tenantId = Database::insert('tenants', [
                'name' => $input['business_name'],
                'subdomain' => $this->generateSubdomain($input['business_name']),
                'plan' => 'free',
                'status' => 'active'
            ]);

            // Create user
            $userId = Database::insert('users', [
                'tenant_id' => $tenantId,
                'email' => $input['email'],
                'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
                'first_name' => $input['first_name'],
                'last_name' => $input['last_name'],
                'role' => 'admin',
                'status' => 'active'
            ]);

            Database::commit();

            $token = $this->generateToken($userId, $tenantId);

            echo json_encode([
                'message' => 'Registration successful',
                'token' => $token,
                'user' => [
                    'id' => $userId,
                    'email' => $input['email'],
                    'first_name' => $input['first_name'],
                    'last_name' => $input['last_name'],
                    'role' => 'admin'
                ],
                'tenant' => [
                    'id' => $tenantId,
                    'name' => $input['business_name']
                ]
            ]);

        } catch (\Exception $e) {
            Database::rollback();
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed', 'message' => $e->getMessage()]);
        }
    }

    public function login(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['email']) || empty($input['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required']);
                return;
            }

            // Debug: Log the login attempt
            error_log("Login attempt for email: " . $input['email']);

            $user = Database::fetch(
                'SELECT u.*, t.name as tenant_name FROM users u 
                 JOIN tenants t ON u.tenant_id = t.id 
                 WHERE u.email = ? AND u.status = ? AND t.status = ?',
                [$input['email'], 'active', 'active']
            );

            if (!$user) {
                error_log("User not found for email: " . $input['email']);
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }

            if (!password_verify($input['password'], $user['password_hash'])) {
                error_log("Invalid password for email: " . $input['email']);
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }

            // Update last login
            Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

            $token = $this->generateToken($user['id'], $user['tenant_id']);

            error_log("Login successful for user: " . $user['id']);

            echo json_encode([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'role' => $user['role']
                ],
                'tenant' => [
                    'id' => $user['tenant_id'],
                    'name' => $user['tenant_name']
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => 'Login failed', 
                'message' => Config::get('app.debug') ? $e->getMessage() : 'Something went wrong'
            ]);
        }
    }

    public function logout(): void
    {
        // In a more sophisticated implementation, we'd blacklist the token
        echo json_encode(['message' => 'Logged out successfully']);
    }

    public function me(): void
    {
        $user = $GLOBALS['current_user'];
        $tenant = $GLOBALS['current_tenant'];

        echo json_encode([
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role']
            ],
            'tenant' => [
                'id' => $tenant['id'],
                'name' => $tenant['name']
            ]
        ]);
    }

    public function forgotPassword(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email is required']);
            return;
        }

        // Always return success for security (don't reveal if email exists)
        echo json_encode(['message' => 'If the email exists, a reset link has been sent']);
        
        // TODO: Implement password reset email with SendGrid
    }

    public function resetPassword(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // TODO: Implement password reset logic
        echo json_encode(['message' => 'Password reset functionality coming soon']);
    }

    private function validateRegistration(array $input): array
    {
        $errors = [];

        if (empty($input['email']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }

        if (empty($input['password']) || strlen($input['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        }

        if (empty($input['first_name'])) {
            $errors['first_name'] = 'First name is required';
        }

        if (empty($input['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        }

        if (empty($input['business_name'])) {
            $errors['business_name'] = 'Business name is required';
        }

        // Check if email already exists
        if (!empty($input['email'])) {
            $existing = Database::fetch('SELECT id FROM users WHERE email = ?', [$input['email']]);
            if ($existing) {
                $errors['email'] = 'Email already exists';
            }
        }

        return $errors;
    }

    private function generateSubdomain(string $businessName): string
    {
        $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $businessName));
        $subdomain = substr($subdomain, 0, 20);
        
        // Ensure uniqueness
        $counter = 1;
        $originalSubdomain = $subdomain;
        
        while (Database::fetch('SELECT id FROM tenants WHERE subdomain = ?', [$subdomain])) {
            $subdomain = $originalSubdomain . $counter;
            $counter++;
        }

        return $subdomain;
    }

    private function generateToken(string $userId, string $tenantId): string
    {
        $payload = [
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'iat' => time(),
            'exp' => time() + Config::get('jwt.expiry')
        ];

        return JWT::encode($payload, Config::get('jwt.secret'), 'HS256');
    }
}
