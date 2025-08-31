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

    // Get knowledgebase articles
    $stmt = $pdo->query("
        SELECT 
            k.id,
            k.title,
            k.content,
            k.slug,
            k.view_count,
            k.created_at,
            k.updated_at,
            kc.name as category_name,
            kc.slug as category_slug
        FROM knowledgebase k
        LEFT JOIN knowledgebase_categories kc ON k.category_id = kc.id
        WHERE k.status = 'published'
        ORDER BY k.created_at DESC
        LIMIT 50
    ");
    
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format articles for frontend
    $formattedArticles = [];
    foreach ($articles as $article) {
        $formattedArticles[] = [
            'id' => $article['id'],
            'title' => $article['title'],
            'content' => $article['content'],
            'slug' => $article['slug'],
            'view_count' => (int)$article['view_count'],
            'created_at' => $article['created_at'],
            'updated_at' => $article['updated_at'],
            'category' => [
                'name' => $article['category_name'],
                'slug' => $article['category_slug']
            ]
        ];
    }

    $response = [
        'success' => true,
        'data' => [
            'articles' => $formattedArticles,
            'total' => count($formattedArticles)
        ]
    ];

    // Clear any output buffer and ensure proper JSON output
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Support portal knowledgebase error: " . $e->getMessage());

    // Clear any output buffer and ensure proper JSON output for errors
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch knowledgebase articles: ' . $e->getMessage(),
        'data' => [
            'articles' => [],
            'total' => 0
        ]
    ], JSON_PRETTY_PRINT);
}
?>
