<?php
// Prevent any output before JSON
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
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

try {
    // Database connection
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_PORT'] ?? '5432',
        $_ENV['DB_NAME'] ?? 'defaultdb'
    );

    $pdo = new PDO(
        $dsn,
        $_ENV['DB_USERNAME'] ?? $_ENV['DB_USER'] ?? 'postgres',
        $_ENV['DB_PASSWORD'] ?? $_ENV['DB_PASS'] ?? 'password',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Get knowledgebase categories with article counts
    $stmt = $pdo->query("
        SELECT 
            kc.id,
            kc.name,
            kc.slug,
            kc.description,
            kc.created_at,
            kc.updated_at,
            COUNT(k.id) as article_count
        FROM knowledgebase_categories kc
        LEFT JOIN knowledgebase k ON kc.id = k.category_id AND k.status = 'published'
        GROUP BY kc.id, kc.name, kc.slug, kc.description, kc.created_at, kc.updated_at
        ORDER BY kc.name ASC
    ");
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format categories for frontend
    $formattedCategories = [];
    foreach ($categories as $category) {
        $formattedCategories[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'],
            'article_count' => (int)$category['article_count'],
            'created_at' => $category['created_at'],
            'updated_at' => $category['updated_at']
        ];
    }

    $response = [
        'success' => true,
        'data' => $formattedCategories
    ];

    // Clear any output buffer and ensure proper JSON output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Support portal categories error: " . $e->getMessage());

    // Clear any output buffer and ensure proper JSON output for errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch categories: ' . $e->getMessage(),
        'data' => []
    ], JSON_PRETTY_PRINT);
}
?>
