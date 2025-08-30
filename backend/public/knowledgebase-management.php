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

// Simple authentication check (you may want to enhance this)
function checkSuperAdminAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    // For now, we'll accept any Bearer token - in production, validate the JWT
    return true;
}

try {
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
    
    // Check authentication
    checkSuperAdminAuth();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    $endpoint = end($pathParts);
    
    // Handle different endpoints
    switch ($method) {
        case 'GET':
            handleGetRequest($pdo, $endpoint, $_GET);
            break;
        case 'POST':
            handlePostRequest($pdo, $endpoint, $_POST, file_get_contents('php://input'));
            break;
        case 'PUT':
            handlePutRequest($pdo, $endpoint, file_get_contents('php://input'));
            break;
        case 'DELETE':
            handleDeleteRequest($pdo, $endpoint, $_GET);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Knowledgebase Management Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}

function handleGetRequest($pdo, $endpoint, $params) {
    switch ($endpoint) {
        case 'categories':
            getCategories($pdo, $params);
            break;
        case 'articles':
            getArticles($pdo, $params);
            break;
        case 'category':
            getCategory($pdo, $params);
            break;
        case 'article':
            getArticle($pdo, $params);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePostRequest($pdo, $endpoint, $postData, $rawData) {
    $data = json_decode($rawData, true) ?: $postData;
    
    switch ($endpoint) {
        case 'categories':
            createCategory($pdo, $data);
            break;
        case 'articles':
            createArticle($pdo, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handlePutRequest($pdo, $endpoint, $rawData) {
    $data = json_decode($rawData, true);
    
    switch ($endpoint) {
        case 'categories':
            updateCategory($pdo, $data);
            break;
        case 'articles':
            updateArticle($pdo, $data);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

function handleDeleteRequest($pdo, $endpoint, $params) {
    switch ($endpoint) {
        case 'categories':
            deleteCategory($pdo, $params);
            break;
        case 'articles':
            deleteArticle($pdo, $params);
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
    }
}

// Category Management Functions
function getCategories($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM knowledgebase_categories ORDER BY sort_order, name LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        // Get total count
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM knowledgebase_categories");
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch categories']);
    }
}

function getCategory($pdo, $params) {
    try {
        $id = $params['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category ID required']);
            return;
        }
        
        $sql = "SELECT * FROM knowledgebase_categories WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch();
        
        if (!$category) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Category not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'data' => $category]);
    } catch (Exception $e) {
        error_log("Get category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch category']);
    }
}

function createCategory($pdo, $data) {
    try {
        $requiredFields = ['name', 'slug', 'description'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                return;
            }
        }
        
        $sql = "INSERT INTO knowledgebase_categories (name, slug, description, icon, sort_order, created_at, updated_at) 
                VALUES (:name, :slug, :description, :icon, :sort_order, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'icon' => $data['icon'] ?? 'help-circle',
            'sort_order' => $data['sort_order'] ?? 1
        ]);
        
        $categoryId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => ['id' => $categoryId]
        ]);
    } catch (Exception $e) {
        error_log("Create category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create category']);
    }
}

function updateCategory($pdo, $data) {
    try {
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category ID required']);
            return;
        }
        
        $sql = "UPDATE knowledgebase_categories SET 
                name = :name, 
                slug = :slug, 
                description = :description, 
                icon = :icon, 
                sort_order = :sort_order, 
                updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $data['id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'],
            'icon' => $data['icon'] ?? 'help-circle',
            'sort_order' => $data['sort_order'] ?? 1
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Category updated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Update category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update category']);
    }
}

function deleteCategory($pdo, $params) {
    try {
        $id = $params['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Category ID required']);
            return;
        }
        
        // Check if category has articles
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM knowledgebase WHERE category_id = :id");
        $checkStmt->execute(['id' => $id]);
        $articleCount = $checkStmt->fetch()['count'];
        
        if ($articleCount > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'error' => "Cannot delete category with $articleCount articles. Please move or delete the articles first."
            ]);
            return;
        }
        
        $sql = "DELETE FROM knowledgebase_categories WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete category']);
    }
}

// Article Management Functions
function getArticles($pdo, $params) {
    try {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
        $offset = ($page - 1) * $limit;
        $categoryId = $params['category_id'] ?? null;
        $search = $params['search'] ?? null;
        
        $whereConditions = [];
        $bindParams = [];
        
        if ($categoryId) {
            $whereConditions[] = "kb.category_id = :category_id";
            $bindParams['category_id'] = $categoryId;
        }
        
        if ($search) {
            $whereConditions[] = "(kb.title ILIKE :search OR kb.content ILIKE :search)";
            $bindParams['search'] = "%$search%";
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT kb.*, c.name as category_name 
                FROM knowledgebase kb 
                LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id 
                $whereClause 
                ORDER BY kb.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        foreach ($bindParams as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $articles = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM knowledgebase kb $whereClause";
        $countStmt = $pdo->prepare($countSql);
        foreach ($bindParams as $key => $value) {
            $countStmt->bindValue(":$key", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'articles' => $articles,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]
        ]);
    } catch (Exception $e) {
        error_log("Get articles error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch articles']);
    }
}

function getArticle($pdo, $params) {
    try {
        $id = $params['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Article ID required']);
            return;
        }
        
        $sql = "SELECT kb.*, c.name as category_name 
                FROM knowledgebase kb 
                LEFT JOIN knowledgebase_categories c ON kb.category_id = c.id 
                WHERE kb.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $article = $stmt->fetch();
        
        if (!$article) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Article not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'data' => $article]);
    } catch (Exception $e) {
        error_log("Get article error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to fetch article']);
    }
}

function createArticle($pdo, $data) {
    try {
        $requiredFields = ['title', 'slug', 'content', 'category_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                return;
            }
        }
        
        $sql = "INSERT INTO knowledgebase (category_id, title, slug, content, excerpt, tags, published, featured, created_at, updated_at) 
                VALUES (:category_id, :title, :slug, :content, :excerpt, :tags, :published, :featured, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?? '',
            'tags' => $data['tags'] ?? '',
            'published' => $data['published'] ?? true,
            'featured' => $data['featured'] ?? false
        ]);
        
        $articleId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Article created successfully',
            'data' => ['id' => $articleId]
        ]);
    } catch (Exception $e) {
        error_log("Create article error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create article']);
    }
}

function updateArticle($pdo, $data) {
    try {
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Article ID required']);
            return;
        }
        
        $sql = "UPDATE knowledgebase SET 
                category_id = :category_id,
                title = :title, 
                slug = :slug, 
                content = :content, 
                excerpt = :excerpt, 
                tags = :tags, 
                published = :published, 
                featured = :featured, 
                updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id' => $data['id'],
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?? '',
            'tags' => $data['tags'] ?? '',
            'published' => $data['published'] ?? true,
            'featured' => $data['featured'] ?? false
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Article updated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Update article error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update article']);
    }
}

function deleteArticle($pdo, $params) {
    try {
        $id = $params['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Article ID required']);
            return;
        }
        
        $sql = "DELETE FROM knowledgebase WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Article deleted successfully'
        ]);
    } catch (Exception $e) {
        error_log("Delete article error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete article']);
    }
}
