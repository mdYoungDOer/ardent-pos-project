<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../middleware/AuthMiddleware.php';
require_once '../middleware/TenantMiddleware.php';

// Initialize response functions
function logError($message, $error = null) {
    error_log("Coupons API Error: " . $message . ($error ? " - " . $error->getMessage() : ""));
}

function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

function sendSuccessResponse($data, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Get fallback coupons data
function getFallbackCoupons() {
    return [
        [
            'id' => '1',
            'code' => 'WELCOME20',
            'name' => 'Welcome Discount',
            'description' => '20% off for new customers',
            'type' => 'percentage',
            'value' => 20,
            'scope' => 'all_products',
            'scope_ids' => null,
            'min_amount' => 50,
            'max_discount' => 100,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'usage_limit' => 1000,
            'used_count' => 150,
            'per_customer_limit' => 1,
            'status' => 'active',
            'created_at' => '2025-01-01 00:00:00'
        ],
        [
            'id' => '2',
            'code' => 'SAVE10',
            'name' => 'Save 10',
            'description' => '10% off any purchase',
            'type' => 'percentage',
            'value' => 10,
            'scope' => 'all_products',
            'scope_ids' => null,
            'min_amount' => 25,
            'max_discount' => 50,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'usage_limit' => 500,
            'used_count' => 75,
            'per_customer_limit' => 3,
            'status' => 'active',
            'created_at' => '2025-01-01 00:00:00'
        ],
        [
            'id' => '3',
            'code' => 'FIXED5',
            'name' => 'Fixed Discount',
            'description' => '5 GHS off any purchase',
            'type' => 'fixed',
            'value' => 5,
            'scope' => 'all_products',
            'scope_ids' => null,
            'min_amount' => 20,
            'max_discount' => 5,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'usage_limit' => 200,
            'used_count' => 25,
            'per_customer_limit' => 1,
            'status' => 'active',
            'created_at' => '2025-01-01 00:00:00'
        ]
    ];
}

try {
    // Load environment variables
    $envFile = __DIR__ . '/../../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
                putenv(trim($key) . '=' . trim($value));
            }
        }
    }

    // Database connection with fallbacks
    $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbName = $_ENV['DB_NAME'] ?? 'ardent_pos';
    $dbUser = $_ENV['DB_USER'] ?? 'postgres';
    $dbPass = $_ENV['DB_PASS'] ?? '';
    $dbPort = $_ENV['DB_PORT'] ?? '5432';

    if (empty($dbPass)) {
        logError("Database password not configured");
        sendErrorResponse("Database configuration error", 500);
    }

    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;options='--client_encoding=UTF8'";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);

    // Authenticate user
    $authMiddleware = new AuthMiddleware($pdo);
    $user = $authMiddleware->authenticate();
    
    if (!$user) {
        sendErrorResponse("Authentication required", 401);
    }

    // Apply tenant middleware
    $tenantMiddleware = new TenantMiddleware($pdo);
    $tenantId = $tenantMiddleware->getTenantId($user);

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));

    // Check if this is a validation request
    if (end($pathParts) === 'validate') {
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['code'])) {
                sendErrorResponse('Coupon code required');
            }

            $couponCode = strtoupper(trim($input['code']));
            $customerId = $input['customer_id'] ?? null;
            $subtotal = $input['subtotal'] ?? 0;

            // Check if coupon exists and is valid
            $stmt = $pdo->prepare("
                SELECT * FROM coupons 
                WHERE code = ? AND tenant_id = ? AND status = 'active' 
                AND deleted_at IS NULL
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
            ");
            $stmt->execute([$couponCode, $tenantId]);
            $coupon = $stmt->fetch();

            if (!$coupon) {
                sendErrorResponse('Invalid or expired coupon code');
            }

            // Check usage limit
            if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
                sendErrorResponse('Coupon usage limit exceeded');
            }

            // Check minimum amount
            if ($coupon['min_amount'] && $subtotal < $coupon['min_amount']) {
                sendErrorResponse("Minimum purchase amount of " . $coupon['min_amount'] . " required");
            }

            // Check per customer limit if customer is provided
            if ($customerId && $coupon['per_customer_limit']) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as usage_count 
                    FROM coupon_usage 
                    WHERE coupon_id = ? AND customer_id = ?
                ");
                $stmt->execute([$coupon['id'], $customerId]);
                $usage = $stmt->fetch();
                
                if ($usage['usage_count'] >= $coupon['per_customer_limit']) {
                    sendErrorResponse('Coupon usage limit reached for this customer');
                }
            }

            // Calculate discount amount
            $discountAmount = 0;
            if ($coupon['type'] === 'percentage') {
                $discountAmount = $subtotal * ($coupon['value'] / 100);
                if ($coupon['max_discount']) {
                    $discountAmount = min($discountAmount, $coupon['max_discount']);
                }
            } else {
                $discountAmount = $coupon['value'];
            }

            sendSuccessResponse([
                'coupon' => $coupon,
                'discount_amount' => $discountAmount
            ], 'Coupon is valid');
        }
        exit;
    }

    // Check role-based access (only admins and managers can manage coupons)
    if (!in_array($user['role'], ['admin', 'manager'])) {
        sendErrorResponse("Access denied. Only admins and managers can manage coupons.", 403);
    }

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific coupon
                $couponId = $_GET['id'];
                $stmt = $pdo->prepare("
                    SELECT * FROM coupons 
                    WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL
                ");
                $stmt->execute([$couponId, $tenantId]);
                $coupon = $stmt->fetch();
                
                if ($coupon) {
                    sendSuccessResponse($coupon, 'Coupon retrieved successfully');
                } else {
                    sendErrorResponse('Coupon not found', 404);
                }
            } else {
                // Get all coupons
                $stmt = $pdo->prepare("
                    SELECT * FROM coupons 
                    WHERE tenant_id = ? AND deleted_at IS NULL 
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$tenantId]);
                $coupons = $stmt->fetchAll();
                
                if (empty($coupons)) {
                    $coupons = getFallbackCoupons();
                }
                
                sendSuccessResponse($coupons, 'Coupons retrieved successfully');
            }
            break;

        case 'POST':
            // Create new coupon
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                sendErrorResponse('Invalid JSON input');
            }

            // Validate required fields
            $requiredFields = ['code', 'name', 'type', 'value'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    sendErrorResponse("Missing required field: $field");
                }
            }

            // Validate coupon code format
            $couponCode = strtoupper(trim($input['code']));
            if (!preg_match('/^[A-Z0-9]{3,20}$/', $couponCode)) {
                sendErrorResponse('Coupon code must be 3-20 characters long and contain only letters and numbers');
            }

            // Check if coupon code already exists
            $stmt = $pdo->prepare("SELECT id FROM coupons WHERE code = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$couponCode, $tenantId]);
            if ($stmt->fetch()) {
                sendErrorResponse('Coupon code already exists');
            }

            // Validate coupon type
            if (!in_array($input['type'], ['percentage', 'fixed'])) {
                sendErrorResponse('Invalid coupon type. Must be "percentage" or "fixed"');
            }

            // Validate scope
            if (!in_array($input['scope'], ['all_products', 'category', 'product', 'location'])) {
                sendErrorResponse('Invalid scope. Must be "all_products", "category", "product", or "location"');
            }

            // Validate value
            if ($input['type'] === 'percentage' && ($input['value'] < 0 || $input['value'] > 100)) {
                sendErrorResponse('Percentage discount must be between 0 and 100');
            }

            if ($input['type'] === 'fixed' && $input['value'] < 0) {
                sendErrorResponse('Fixed discount cannot be negative');
            }

            $stmt = $pdo->prepare("
                INSERT INTO coupons (
                    tenant_id, code, name, description, type, value, scope, scope_ids,
                    min_amount, max_discount, start_date, end_date, usage_limit,
                    per_customer_limit, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                RETURNING id
            ");

            $stmt->execute([
                $tenantId,
                $couponCode,
                $input['name'],
                $input['description'] ?? '',
                $input['type'],
                $input['value'],
                $input['scope'],
                $input['scope_ids'] ? json_encode($input['scope_ids']) : null,
                $input['min_amount'] ?? null,
                $input['max_discount'] ?? null,
                $input['start_date'] ?? null,
                $input['end_date'] ?? null,
                $input['usage_limit'] ?? null,
                $input['per_customer_limit'] ?? null,
                $input['status'] ?? 'active',
                $user['id']
            ]);

            $couponId = $pdo->lastInsertId();
            
            // Fetch the created coupon
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
            $stmt->execute([$couponId]);
            $newCoupon = $stmt->fetch();

            sendSuccessResponse($newCoupon, 'Coupon created successfully');
            break;

        case 'PUT':
            // Update coupon
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                sendErrorResponse('Invalid input or missing coupon ID');
            }

            $couponId = $input['id'];

            // Check if coupon exists and belongs to tenant
            $stmt = $pdo->prepare("SELECT id FROM coupons WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$couponId, $tenantId]);
            
            if (!$stmt->fetch()) {
                sendErrorResponse('Coupon not found', 404);
            }

            // Build update query dynamically
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = [
                'name', 'description', 'type', 'value', 'scope', 'scope_ids',
                'min_amount', 'max_discount', 'start_date', 'end_date', 
                'usage_limit', 'per_customer_limit', 'status'
            ];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $field === 'scope_ids' && $input[$field] ? 
                        json_encode($input[$field]) : $input[$field];
                }
            }

            if (empty($updateFields)) {
                sendErrorResponse('No valid fields to update');
            }

            $updateFields[] = 'updated_at = NOW()';
            $updateValues[] = $couponId;
            $updateValues[] = $tenantId;

            $sql = "UPDATE coupons SET " . implode(', ', $updateFields) . 
                   " WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);

            // Fetch updated coupon
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
            $stmt->execute([$couponId]);
            $updatedCoupon = $stmt->fetch();

            sendSuccessResponse($updatedCoupon, 'Coupon updated successfully');
            break;

        case 'DELETE':
            // Soft delete coupon
            $couponId = $_GET['id'] ?? null;
            
            if (!$couponId) {
                sendErrorResponse('Coupon ID required');
            }

            // Check if coupon exists and belongs to tenant
            $stmt = $pdo->prepare("SELECT id FROM coupons WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$couponId, $tenantId]);
            
            if (!$stmt->fetch()) {
                sendErrorResponse('Coupon not found', 404);
            }

            // Soft delete
            $stmt = $pdo->prepare("UPDATE coupons SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$couponId, $tenantId]);

            sendSuccessResponse(null, 'Coupon deleted successfully');
            break;

        default:
            sendErrorResponse('Method not allowed', 405);
    }

} catch (PDOException $e) {
    logError("Database error", $e);
    sendErrorResponse("Database error occurred", 500);
} catch (Exception $e) {
    logError("Unexpected error", $e);
    sendErrorResponse("An unexpected error occurred", 500);
}
?>
