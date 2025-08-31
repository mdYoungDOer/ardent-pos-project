<?php
// Unified Authentication Helper
// Handles both base64 and JWT tokens for backward compatibility

class UnifiedAuth {
    private $pdo;
    private $jwtSecret;
    
    public function __construct($pdo, $jwtSecret = null) {
        $this->pdo = $pdo;
        $this->jwtSecret = $jwtSecret ?? 'your-secret-key';
    }
    
    /**
     * Verify token (supports both base64 and JWT)
     */
    public function verifyToken($token) {
        if (empty($token)) {
            return ['success' => false, 'error' => 'Token is required'];
        }
        
        // Try to decode as base64 first (registration tokens)
        $base64Data = $this->decodeBase64Token($token);
        if ($base64Data) {
            return $this->validateBase64Token($base64Data);
        }
        
        // Try to decode as JWT (login tokens)
        $jwtData = $this->decodeJWTToken($token);
        if ($jwtData) {
            return $this->validateJWTToken($jwtData);
        }
        
        return ['success' => false, 'error' => 'Invalid token format'];
    }
    
    /**
     * Decode base64 token
     */
    private function decodeBase64Token($token) {
        try {
            $decoded = base64_decode($token);
            if ($decoded === false) {
                return null;
            }
            
            $data = json_decode($decoded, true);
            if (!$data || !isset($data['user_id']) || !isset($data['tenant_id'])) {
                return null;
            }
            
            // Check if token is expired
            if (isset($data['exp']) && $data['exp'] < time()) {
                return null;
            }
            
            return $data;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Decode JWT token
     */
    private function decodeJWTToken($token) {
        try {
            // Load JWT library
            $autoloaderPaths = [
                __DIR__ . '/../../vendor/autoload.php',
                __DIR__ . '/../vendor/autoload.php',
                '/var/www/html/vendor/autoload.php',
                '/var/www/html/backend/vendor/autoload.php'
            ];
            
            $autoloaderFound = false;
            foreach ($autoloaderPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    $autoloaderFound = true;
                    break;
                }
            }
            
            if (!$autoloaderFound) {
                return null;
            }
            
            $decoded = Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($this->jwtSecret, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Validate base64 token data
     */
    private function validateBase64Token($data) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, t.name as tenant_name, t.status as tenant_status
                FROM users u 
                JOIN tenants t ON u.tenant_id = t.id 
                WHERE u.id = ? AND u.status = 'active'
            ");
            $stmt->execute([$data['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }
            
            if ($user['tenant_status'] !== 'active') {
                return ['success' => false, 'error' => 'Account is inactive'];
            }
            
            return [
                'success' => true,
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
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Token validation failed'];
        }
    }
    
    /**
     * Validate JWT token data
     */
    private function validateJWTToken($data) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, t.name as tenant_name, t.status as tenant_status
                FROM users u 
                JOIN tenants t ON u.tenant_id = t.id 
                WHERE u.id = ? AND u.status = 'active'
            ");
            $stmt->execute([$data['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }
            
            if ($user['tenant_status'] !== 'active') {
                return ['success' => false, 'error' => 'Account is inactive'];
            }
            
            return [
                'success' => true,
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
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Token validation failed'];
        }
    }
    
    /**
     * Generate JWT token
     */
    public function generateJWTToken($userData) {
        try {
            // Load JWT library
            $autoloaderPaths = [
                __DIR__ . '/../../vendor/autoload.php',
                __DIR__ . '/../vendor/autoload.php',
                '/var/www/html/vendor/autoload.php',
                '/var/www/html/backend/vendor/autoload.php'
            ];
            
            $autoloaderFound = false;
            foreach ($autoloaderPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    $autoloaderFound = true;
                    break;
                }
            }
            
            if (!$autoloaderFound) {
                throw new Exception('JWT library not found');
            }
            
            $payload = [
                'user_id' => $userData['user_id'],
                'tenant_id' => $userData['tenant_id'],
                'email' => $userData['email'],
                'role' => $userData['role'],
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60)
            ];
            
            return Firebase\JWT\JWT::encode($payload, $this->jwtSecret, 'HS256');
        } catch (Exception $e) {
            throw new Exception('Failed to generate JWT token: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate base64 token (for backward compatibility)
     */
    public function generateBase64Token($userData) {
        $payload = [
            'user_id' => $userData['user_id'],
            'tenant_id' => $userData['tenant_id'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'exp' => time() + (24 * 60 * 60)
        ];
        
        return base64_encode(json_encode($payload));
    }
    
    /**
     * Get user from token
     */
    public function getUserFromToken($token) {
        $result = $this->verifyToken($token);
        if ($result['success']) {
            return $result['user'];
        }
        return null;
    }
    
    /**
     * Get tenant from token
     */
    public function getTenantFromToken($token) {
        $result = $this->verifyToken($token);
        if ($result['success']) {
            return $result['tenant'];
        }
        return null;
    }
    
    /**
     * Check if user is super admin
     */
    public function isSuperAdmin($token) {
        $user = $this->getUserFromToken($token);
        return $user && $user['role'] === 'super_admin';
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin($token) {
        $user = $this->getUserFromToken($token);
        return $user && ($user['role'] === 'admin' || $user['role'] === 'super_admin');
    }
}
?>
