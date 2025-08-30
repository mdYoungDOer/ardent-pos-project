<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load environment variables
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database configuration
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'dbname' => $_ENV['DB_NAME'] ?? 'ardent_pos',
    'user' => $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? ''
];

// Simple authentication check
function checkSuperAdminAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized - Token required']);
        exit;
    }
    
    $token = substr($authHeader, 7);
    
    // For now, accept any token (in production, validate JWT)
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized - Invalid token']);
        exit;
    }
}

try {
    // Check authentication
    checkSuperAdminAuth();
    
    // Connect to database
    $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ensure contact_submissions table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_submissions (
            id SERIAL PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            company VARCHAR(255),
            subject VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(50) DEFAULT 'new',
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    switch ($method) {
        case 'GET':
            if (is_numeric($endpoint)) {
                getContactSubmission($pdo, $endpoint);
            } else {
                getContactSubmissions($pdo, $_GET);
            }
            break;
        case 'PUT':
            if (is_numeric($endpoint)) {
                updateContactSubmission($pdo, $endpoint, file_get_contents('php://input'));
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid submission ID']);
            }
            break;
        case 'DELETE':
            if (is_numeric($endpoint)) {
                deleteContactSubmission($pdo, $endpoint);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid submission ID']);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Contact Submissions API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function getContactSubmissions($pdo, $params) {
    $page = (int)($params['page'] ?? 1);
    $limit = (int)($params['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    $search = $params['search'] ?? '';
    $status = $params['status'] ?? '';
    
    $whereConditions = [];
    $queryParams = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(first_name ILIKE ? OR last_name ILIKE ? OR email ILIKE ? OR subject ILIKE ?)";
        $searchTerm = "%$search%";
        $queryParams[] = $searchTerm;
        $queryParams[] = $searchTerm;
        $queryParams[] = $searchTerm;
        $queryParams[] = $searchTerm;
    }
    
    if (!empty($status) && $status !== 'all') {
        $whereConditions[] = "status = ?";
        $queryParams[] = $status;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get submissions
    $sql = "SELECT * FROM contact_submissions $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $queryParams[] = $limit;
    $queryParams[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM contact_submissions $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute(array_slice($queryParams, 0, -2));
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'submissions' => $submissions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]
    ]);
}

function getContactSubmission($pdo, $id) {
    $sql = "SELECT * FROM contact_submissions WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Submission not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $submission
    ]);
}

function updateContactSubmission($pdo, $id, $rawData) {
    $data = json_decode($rawData, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        return;
    }
    
    $allowedFields = ['status', 'first_name', 'last_name', 'email', 'phone', 'company', 'subject', 'message'];
    $updateFields = [];
    $queryParams = [];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $queryParams[] = $data[$field];
        }
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No valid fields to update']);
        return;
    }
    
    $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
    $queryParams[] = $id;
    
    $sql = "UPDATE contact_submissions SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Submission not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Submission updated successfully'
    ]);
}

function deleteContactSubmission($pdo, $id) {
    $sql = "DELETE FROM contact_submissions WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Submission not found']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Submission deleted successfully'
    ]);
}
?>
