<?php
class SuperAdminMiddleware {
    private $db;

    public function __construct($db = null) {
        $this->db = $db;
    }

    public function isSuperAdmin($user) {
        // Check if user has super_admin role
        if (isset($user['role']) && $user['role'] === 'super_admin') {
            return true;
        }
        
        // Check if user is in super_admin_users table
        if (isset($user['id']) && $this->db) {
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count 
                    FROM super_admin_users 
                    WHERE user_id = :user_id 
                    AND deleted_at IS NULL
                ");
                $stmt->execute(['user_id' => $user['id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $result['count'] > 0;
            } catch (Exception $e) {
                error_log("Super Admin check error: " . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
}
?>
