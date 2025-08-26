<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Load environment variables
$dbHost = $_ENV['DB_HOST'] ?? 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
$dbPort = $_ENV['DB_PORT'] ?? '25060';
$dbName = $_ENV['DB_NAME'] ?? 'defaultdb';
$dbUser = $_ENV['DB_USER'] ?? 'doadmin';
$dbPass = $_ENV['DB_PASS'] ?? '';

try {
    // Validate database credentials
    if (empty($dbPass)) {
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

    // SQL statements from sub_categories.sql
    $sqlStatements = [
        // Add parent_id column to existing categories table
        "ALTER TABLE categories ADD COLUMN IF NOT EXISTS parent_id UUID REFERENCES categories(id) ON DELETE CASCADE" => "Add parent_id to categories",
        "ALTER TABLE categories ADD COLUMN IF NOT EXISTS level INTEGER DEFAULT 1 CHECK (level >= 1)" => "Add level to categories",
        "ALTER TABLE categories ADD COLUMN IF NOT EXISTS path TEXT" => "Add path to categories",
        "ALTER TABLE categories ADD COLUMN IF NOT EXISTS sort_order INTEGER DEFAULT 0" => "Add sort_order to categories",

        // Create sub_categories table
        "CREATE TABLE IF NOT EXISTS sub_categories (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
            category_id UUID NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            color VARCHAR(7) DEFAULT '#e41e5b' CHECK (color ~ '^#[0-9A-Fa-f]{6}$'),
            image_url TEXT,
            sort_order INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
            created_at TIMESTAMP DEFAULT NOW(),
            updated_at TIMESTAMP DEFAULT NOW(),
            deleted_at TIMESTAMP
        )" => "Create sub_categories table",

        // Create category_hierarchy table
        "CREATE TABLE IF NOT EXISTS category_hierarchy (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
            parent_id UUID REFERENCES categories(id) ON DELETE CASCADE,
            child_id UUID NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
            level INTEGER NOT NULL DEFAULT 1 CHECK (level >= 1),
            path TEXT,
            created_at TIMESTAMP DEFAULT NOW(),
            UNIQUE(parent_id, child_id)
        )" => "Create category_hierarchy table",

        // Create indexes
        "CREATE INDEX IF NOT EXISTS idx_categories_parent_id ON categories(parent_id)" => "Create categories parent_id index",
        "CREATE INDEX IF NOT EXISTS idx_categories_level ON categories(level)" => "Create categories level index",
        "CREATE INDEX IF NOT EXISTS idx_categories_path ON categories(path)" => "Create categories path index",
        "CREATE INDEX IF NOT EXISTS idx_categories_sort_order ON categories(sort_order)" => "Create categories sort_order index",

        "CREATE INDEX IF NOT EXISTS idx_sub_categories_tenant_id ON sub_categories(tenant_id)" => "Create sub_categories tenant_id index",
        "CREATE INDEX IF NOT EXISTS idx_sub_categories_category_id ON sub_categories(category_id)" => "Create sub_categories category_id index",
        "CREATE INDEX IF NOT EXISTS idx_sub_categories_status ON sub_categories(status)" => "Create sub_categories status index",
        "CREATE INDEX IF NOT EXISTS idx_sub_categories_sort_order ON sub_categories(sort_order)" => "Create sub_categories sort_order index",

        "CREATE INDEX IF NOT EXISTS idx_category_hierarchy_tenant_id ON category_hierarchy(tenant_id)" => "Create category_hierarchy tenant_id index",
        "CREATE INDEX IF NOT EXISTS idx_category_hierarchy_parent_id ON category_hierarchy(parent_id)" => "Create category_hierarchy parent_id index",
        "CREATE INDEX IF NOT EXISTS idx_category_hierarchy_child_id ON category_hierarchy(child_id)" => "Create category_hierarchy child_id index",
        "CREATE INDEX IF NOT EXISTS idx_category_hierarchy_level ON category_hierarchy(level)" => "Create category_hierarchy level index",
        "CREATE INDEX IF NOT EXISTS idx_category_hierarchy_path ON category_hierarchy(path)" => "Create category_hierarchy path index",

        // Create functions and triggers
        "CREATE OR REPLACE FUNCTION update_sub_categories_updated_at()
        RETURNS TRIGGER AS $$
        BEGIN
            NEW.updated_at = NOW();
            RETURN NEW;
        END;
        $$ language 'plpgsql'" => "Create update_sub_categories_updated_at function",

        "CREATE TRIGGER update_sub_categories_updated_at
            BEFORE UPDATE ON sub_categories
            FOR EACH ROW EXECUTE FUNCTION update_sub_categories_updated_at()" => "Create sub_categories updated_at trigger",

        "CREATE OR REPLACE FUNCTION update_category_path()
        RETURNS TRIGGER AS $$
        DECLARE
            parent_path TEXT;
            new_path TEXT;
        BEGIN
            IF NEW.parent_id IS NULL THEN
                NEW.path = NEW.name;
                NEW.level = 1;
            ELSE
                SELECT path INTO parent_path FROM categories WHERE id = NEW.parent_id;
                IF parent_path IS NOT NULL THEN
                    NEW.path = parent_path || '/' || NEW.name;
                    NEW.level = array_length(string_to_array(NEW.path, '/'), 1);
                ELSE
                    NEW.path = NEW.name;
                    NEW.level = 1;
                END IF;
            END IF;
            RETURN NEW;
        END;
        $$ language 'plpgsql'" => "Create update_category_path function",

        "CREATE TRIGGER update_category_path_trigger
            BEFORE INSERT OR UPDATE ON categories
            FOR EACH ROW EXECUTE FUNCTION update_category_path()" => "Create categories path trigger",

        // Add sub_category_id to products table
        "ALTER TABLE products ADD COLUMN IF NOT EXISTS sub_category_id UUID REFERENCES sub_categories(id) ON DELETE SET NULL" => "Add sub_category_id to products",
        "CREATE INDEX IF NOT EXISTS idx_products_sub_category_id ON products(sub_category_id)" => "Create products sub_category_id index"
    ];

    // Execute each SQL statement
    foreach ($sqlStatements as $sql => $description) {
        try {
            $pdo->exec($sql);
            $results[] = [
                'status' => 'success',
                'description' => $description,
                'message' => 'Executed successfully'
            ];
        } catch (PDOException $e) {
            $errors[] = [
                'status' => 'error',
                'description' => $description,
                'message' => $e->getMessage()
            ];
        }
    }

    // Check if tables exist
    $tableChecks = [
        'categories' => "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = 'categories'",
        'sub_categories' => "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = 'sub_categories'",
        'category_hierarchy' => "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_name = 'category_hierarchy'"
    ];

    foreach ($tableChecks as $table => $sql) {
        try {
            $stmt = $pdo->query($sql);
            $result = $stmt->fetch();
            $results[] = [
                'status' => 'info',
                'description' => "Check $table table",
                'message' => $result['count'] > 0 ? "Table $table exists" : "Table $table does not exist"
            ];
        } catch (PDOException $e) {
            $errors[] = [
                'status' => 'error',
                'description' => "Check $table table",
                'message' => $e->getMessage()
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sub-categories migration completed',
        'results' => $results,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
