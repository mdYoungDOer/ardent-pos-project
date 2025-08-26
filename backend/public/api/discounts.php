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
    error_log("Discounts API Error: " . $message . ($error ? " - " . $error->getMessage() : ""));
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

// Get fallback discounts data
function getFallbackDiscounts() {
    return [
        [
            'id' => '1',
            'name' => 'Summer Sale',
            'description' => '20% off all summer items',
            'type' => 'percentage',
            'value' => 20,
            'scope' => 'all_products',
            'scope_ids' => null,
            'min_amount' => 50,
            'max_discount' => 100,
            'start_date' => '2025-06-01',
            'end_date' => '2025-08-31',
            'usage_limit' => 1000,
            'used_count' => 150,
            'status' => 'active',
            'created_at' => '2025-06-01 00:00:00'
        ],
        [
            'id' => '2',
            'name' => 'Bulk Purchase',
            'description' => '10% off orders over $200',
            'type' => 'percentage',
            'value' => 10,
            'scope' => 'all_products',
            'scope_ids' => null,
            'min_amount' => 200,
            'max_discount' => 50,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'usage_limit' => null,
            'used_count' => 75,
            'status' => 'active',
            'created_at' => '2025-01-01 00:00:00'
        ],
        [
            'id' => '3',
            'name' => 'Location Specific',
            'description' => '15% off at Main Store',
            'type' => 'percentage',
            'value' => 15,
            'scope' => 'location',
            'scope_ids' => ['1'],
            'min_amount' => 25,
            'max_discount' => 30,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'usage_limit' => 500,
            'used_count' => 45,
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

    // Check role-based access (only admins and managers can manage discounts)
    if (!in_array($user['role'], ['admin', 'manager'])) {
        sendErrorResponse("Access denied. Only admins and managers can manage discounts.", 403);
    }

    // Apply tenant middleware
    $tenantMiddleware = new TenantMiddleware($pdo);
    $tenantId = $tenantMiddleware->getTenantId($user);

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific discount
                $discountId = $_GET['id'];
                $stmt = $pdo->prepare("
                    SELECT * FROM discounts 
                    WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL
                ");
                $stmt->execute([$discountId, $tenantId]);
                $discount = $stmt->fetch();
                
                if ($discount) {
                    sendSuccessResponse($discount, 'Discount retrieved successfully');
                } else {
                    sendErrorResponse('Discount not found', 404);
                }
            } else {
                // Get all discounts
                $stmt = $pdo->prepare("
                    SELECT * FROM discounts 
                    WHERE tenant_id = ? AND deleted_at IS NULL 
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$tenantId]);
                $discounts = $stmt->fetchAll();
                
                if (empty($discounts)) {
                    $discounts = getFallbackDiscounts();
                }
                
                sendSuccessResponse($discounts, 'Discounts retrieved successfully');
            }
            break;

        case 'POST':
            // Create new discount
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                sendErrorResponse('Invalid JSON input');
            }

            // Validate required fields
            $requiredFields = ['name', 'type', 'value', 'scope'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    sendErrorResponse("Missing required field: $field");
                }
            }

            // Validate discount type
            if (!in_array($input['type'], ['percentage', 'fixed'])) {
                sendErrorResponse('Invalid discount type. Must be "percentage" or "fixed"');
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
                INSERT INTO discounts (
                    tenant_id, name, description, type, value, scope, scope_ids,
                    min_amount, max_discount, start_date, end_date, usage_limit,
                    status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                RETURNING id
            ");

            $stmt->execute([
                $tenantId,
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
                $input['status'] ?? 'active',
                $user['id']
            ]);

            $discountId = $pdo->lastInsertId();
            
            // Fetch the created discount
            $stmt = $pdo->prepare("SELECT * FROM discounts WHERE id = ?");
            $stmt->execute([$discountId]);
            $newDiscount = $stmt->fetch();

            sendSuccessResponse($newDiscount, 'Discount created successfully');
            break;

        case 'PUT':
            // Update discount
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                sendErrorResponse('Invalid input or missing discount ID');
            }

            $discountId = $input['id'];

            // Check if discount exists and belongs to tenant
            $stmt = $pdo->prepare("SELECT id FROM discounts WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$discountId, $tenantId]);
            
            if (!$stmt->fetch()) {
                sendErrorResponse('Discount not found', 404);
            }

            // Build update query dynamically
            $updateFields = [];
            $updateValues = [];
            
            $allowedFields = [
                'name', 'description', 'type', 'value', 'scope', 'scope_ids',
                'min_amount', 'max_discount', 'start_date', 'end_date', 
                'usage_limit', 'status'
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
            $updateValues[] = $discountId;
            $updateValues[] = $tenantId;

            $sql = "UPDATE discounts SET " . implode(', ', $updateFields) . 
                   " WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);

            // Fetch updated discount
            $stmt = $pdo->prepare("SELECT * FROM discounts WHERE id = ?");
            $stmt->execute([$discountId]);
            $updatedDiscount = $stmt->fetch();

            sendSuccessResponse($updatedDiscount, 'Discount updated successfully');
            break;

        case 'DELETE':
            // Soft delete discount
            $discountId = $_GET['id'] ?? null;
            
            if (!$discountId) {
                sendErrorResponse('Discount ID required');
            }

            // Check if discount exists and belongs to tenant
            $stmt = $pdo->prepare("SELECT id FROM discounts WHERE id = ? AND tenant_id = ? AND deleted_at IS NULL");
            $stmt->execute([$discountId, $tenantId]);
            
            if (!$stmt->fetch()) {
                sendErrorResponse('Discount not found', 404);
            }

            // Soft delete
            $stmt = $pdo->prepare("UPDATE discounts SET deleted_at = NOW() WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$discountId, $tenantId]);

            sendSuccessResponse(null, 'Discount deleted successfully');
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
