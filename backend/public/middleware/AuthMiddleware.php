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
            // Verify token and get user
            $stmt = $this->db->prepare("
                SELECT u.*, t.name as tenant_name 
                FROM users u 
                LEFT JOIN tenants t ON u.tenant_id = t.id 
                WHERE u.token = :token 
                AND u.deleted_at IS NULL
            ");
            $stmt->execute(['token' => $token]);
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
}
?>
