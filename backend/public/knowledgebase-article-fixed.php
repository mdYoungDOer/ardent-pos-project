<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
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
    'database' => $_ENV['DB_NAME'] ?? 'defaultdb',
    'username' => $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
    'password' => $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
];

try {
    // Get article ID from query parameter or URL path
    $articleId = $_GET['id'] ?? null;
    
    // If not in query params, try to extract from URL path
    if (!$articleId) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $pathParts = explode('/', trim($path, '/'));
        $articleId = end($pathParts);
    }
    
    if (!$articleId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Article ID required']);
        exit;
    }
    
    // Create database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );
    
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Get the article (support both UUID and numeric IDs)
    $sql = "
        SELECT kb.*, c.name as category_name 
        FROM knowledgebase kb
        LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id
        WHERE (kb.id = :id OR kb.id::text = :id_text) AND kb.published = true
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id' => $articleId,
        'id_text' => $articleId
    ]);
    $article = $stmt->fetch();
    
    if (!$article) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Article not found'
        ]);
        exit;
    }
    
    // Increment view count
    $updateSql = "UPDATE knowledgebase SET view_count = COALESCE(view_count, 0) + 1 WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute(['id' => $article['id']]);
    
    echo json_encode([
        'success' => true,
        'data' => $article
    ]);
    
} catch (Exception $e) {
    error_log("Knowledgebase article error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch article',
        'debug' => $e->getMessage()
    ]);
}
