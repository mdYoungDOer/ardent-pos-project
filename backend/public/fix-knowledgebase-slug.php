<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load environment variables
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '5432';
$dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
$dbUser = $_ENV['DB_USERNAME'] ?? '';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Validate database credentials
    if (empty($dbUser) || empty($dbPass)) {
        throw new Exception('Database credentials not configured');
    }

    // Connect to database
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $results = [];
    $errors = [];

    // Check if slug column exists
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'knowledgebase' AND column_name = 'slug'");
    $slugExists = $stmt->fetch();

    if (!$slugExists) {
        // Add slug column
        try {
            $pdo->exec("ALTER TABLE knowledgebase ADD COLUMN slug VARCHAR(255)");
            $results[] = "Added slug column to knowledgebase table";
        } catch (PDOException $e) {
            $errors[] = "Error adding slug column: " . $e->getMessage();
        }
    } else {
        $results[] = "Slug column already exists";
    }

    // Function to generate slug from title
    function generateSlug($title) {
        // Convert to lowercase
        $slug = strtolower($title);
        // Replace spaces and special characters with hyphens
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        // Remove leading/trailing hyphens
        $slug = trim($slug, '-');
        return $slug;
    }

    // Update existing articles with slugs
    try {
        $stmt = $pdo->query("SELECT id, title FROM knowledgebase WHERE slug IS NULL OR slug = ''");
        $articles = $stmt->fetchAll();

        foreach ($articles as $article) {
            $slug = generateSlug($article['title']);
            
            // Ensure slug is unique
            $counter = 1;
            $originalSlug = $slug;
            while (true) {
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM knowledgebase WHERE slug = ? AND id != ?");
                $checkStmt->execute([$slug, $article['id']]);
                if ($checkStmt->fetchColumn() == 0) {
                    break;
                }
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $updateStmt = $pdo->prepare("UPDATE knowledgebase SET slug = ? WHERE id = ?");
            $updateStmt->execute([$slug, $article['id']]);
            $results[] = "Updated article '{$article['title']}' with slug: $slug";
        }
    } catch (PDOException $e) {
        $errors[] = "Error updating article slugs: " . $e->getMessage();
    }

    // Add unique constraint to slug column
    try {
        $pdo->exec("ALTER TABLE knowledgebase ADD CONSTRAINT knowledgebase_slug_unique UNIQUE (slug)");
        $results[] = "Added unique constraint to slug column";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            $results[] = "Unique constraint on slug column already exists";
        } else {
            $errors[] = "Error adding unique constraint: " . $e->getMessage();
        }
    }

    // Verify the fix
    $verification = [];
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(slug) as with_slug FROM knowledgebase");
        $counts = $stmt->fetch();
        $verification['total_articles'] = $counts['total'];
        $verification['articles_with_slug'] = $counts['with_slug'];
        $verification['missing_slugs'] = $counts['total'] - $counts['with_slug'];
    } catch (PDOException $e) {
        $verification['error'] = $e->getMessage();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Knowledgebase slug column fix completed',
        'results' => $results,
        'errors' => $errors,
        'verification' => $verification,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
