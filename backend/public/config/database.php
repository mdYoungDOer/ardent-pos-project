<?php
class Database {
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $connection;

    public function __construct() {
        $this->host = $_ENV['DB_HOST'] ?? 'db-postgresql-nyc3-77594-ardent-pos-do-user-24545475-0.g.db.ondigitalocean.com';
        $this->port = $_ENV['DB_PORT'] ?? '25060';
        $this->dbname = $_ENV['DB_NAME'] ?? 'defaultdb';
        $this->username = $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? 'doadmin';
        $this->password = $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? '';
    }

    public function getConnection() {
        if ($this->connection === null) {
            try {
                if (empty($this->password)) {
                    throw new Exception('Database password not configured');
                }

                $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname};sslmode=require";
                $this->connection = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (Exception $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw $e;
            }
        }
        return $this->connection;
    }
}
?>
