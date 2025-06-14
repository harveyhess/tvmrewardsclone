<?php
class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            // Support DATABASE_URL (e.g., mysql://user:pass@host:port/dbname)
            $databaseUrl = defined('DATABASE_URL') ? constant('DATABASE_URL') : getenv('DATABASE_URL');
            if ($databaseUrl) {
                $parts = parse_url($databaseUrl);
                $host = $parts['host'];
                $port = isset($parts['port']) ? $parts['port'] : 3306;
                $user = $parts['user'];
                $pass = $parts['pass'];
                $dbname = ltrim($parts['path'], '/');
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
                $this->connection = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } else {
                // Fallback to legacy env vars, but show a clear error if not set
                if (!defined('DB_HOST') || !defined('DB_PORT') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME')) {
                    die("Database connection error: No DATABASE_URL set and legacy DB_* constants are not defined. Please set DATABASE_URL in your .env file.");
                }
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
                $this->connection = new PDO(
                    $dsn,
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            }
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function getPdo() {
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }

    public function execute($sql, $params = []) {
        return $this->query($sql, $params);
    }

    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $values) . ")";
        
        $this->query($sql, array_values($data));
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_map(function($field) {
            return "{$field} = ?";
        }, array_keys($data));
        
        $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        return $this->query($sql, $params);
    }
}