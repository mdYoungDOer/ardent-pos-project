<?php
class AuthMiddleware {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function authenticate() {
        $headers = getallheaders();
        $token = null;

        // Get token from Authorization header
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        // Get token from query parameter if not in header
        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token) {
            return null;
        }

        try {
            // Verify JWT token
            $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
            
            // Load JWT library
            $jwtPath = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($jwtPath)) {
                require_once $jwtPath;
            } else {
                // Fallback to manual JWT verification
                return $this->verifyJWTManually($token, $jwtSecret);
            }

            // Use Firebase JWT library if available
            if (class_exists('Firebase\JWT\JWT')) {
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($jwtSecret, 'HS256'));
                $userId = $decoded->user_id;
            } else {
                // Fallback to manual verification
                $decoded = $this->verifyJWTManually($token, $jwtSecret);
                if (!$decoded) {
                    return null;
                }
                $userId = $decoded->user_id;
            }

            // Get user from database
            $stmt = $this->db->prepare("
                SELECT u.*, t.name as tenant_name 
                FROM users u 
                LEFT JOIN tenants t ON u.tenant_id = t.id 
                WHERE u.id = :user_id 
                AND u.deleted_at IS NULL
            ");
            $stmt->execute(['user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Update last activity
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET last_activity_at = NOW() 
                    WHERE id = :id
                ");
                $stmt->execute(['id' => $user['id']]);
                
                return $user;
            }

            return null;
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return null;
        }
    }

    private function verifyJWTManually($token, $secret) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0])));
            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])));
            $signature = $parts[2];

            // Verify signature
            $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret, true);
            $expectedSignature = str_replace(['+', '/'], ['-', '_'], base64_encode($expectedSignature));

            if ($signature !== $expectedSignature) {
                return null;
            }

            // Check expiration
            if (isset($payload->exp) && $payload->exp < time()) {
                return null;
            }

            return $payload;
        } catch (Exception $e) {
            error_log("Manual JWT verification error: " . $e->getMessage());
            return null;
        }
    }
}
?>
