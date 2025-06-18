<?php
class Database {
    private static $instance = null;
    private $pdo = null;
    private $queryCache = [];
    private $cacheTimeout = 300; // 5 minutes cache

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true, // Enable connection pooling
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            error_log("Attempting to connect to database at " . DB_HOST . ":" . DB_PORT);
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            error_log("Successfully connected to database");
        } catch (PDOException $e) {
            $errorMessage = "Database connection failed: " . $e->getMessage();
            error_log($errorMessage);
            throw new Exception($errorMessage);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function getCacheKey($query, $params) {
        return md5($query . serialize($params));
    }

    private function getFromCache($key) {
        if (isset($this->queryCache[$key]) && 
            (time() - $this->queryCache[$key]['time']) < $this->cacheTimeout) {
            return $this->queryCache[$key]['data'];
        }
        return null;
    }

    private function setCache($key, $data) {
        $this->queryCache[$key] = [
            'time' => time(),
            'data' => $data
        ];
    }

    public function fetch($query, $params = []) {
        // Skip cache for FOR UPDATE queries
        if (stripos($query, 'FOR UPDATE') !== false) {
            try {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                return $stmt->fetch();
            } catch (PDOException $e) {
                error_log("Query error: " . $e->getMessage());
                throw new Exception("Database query failed");
            }
        }

        $cacheKey = $this->getCacheKey($query, $params);
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetch();
            $this->setCache($cacheKey, $result);
            return $result;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }

    public function fetchAll($query, $params = []) {
        $cacheKey = $this->getCacheKey($query, $params);
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            $this->setCache($cacheKey, $result);
            return $result;
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }

    public function execute($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(array_values($data));
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Insert error: " . $e->getMessage());
            throw new Exception("Database insert failed");
        }
    }

    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));
        
        $query = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $fields),
            $where
        );
        
        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute(array_merge(array_values($data), $whereParams));
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            throw new Exception("Database update failed");
        }
    }

    public function clearCache() {
        $this->queryCache = [];
    }

    public function beginTransaction() {
        try {
            $this->clearCache(); // Clear cache at start of transaction
            return $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            error_log("Begin transaction failed: " . $e->getMessage());
            throw new Exception("Failed to begin transaction");
        }
    }

    public function commit() {
        try {
            $result = $this->pdo->commit();
            $this->clearCache(); // Clear cache after commit
            return $result;
        } catch (PDOException $e) {
            error_log("Commit failed: " . $e->getMessage());
            throw new Exception("Failed to commit transaction");
        }
    }

    public function rollback() {
        try {
            $result = $this->pdo->rollBack();
            $this->clearCache(); // Clear cache after rollback
            return $result;
        } catch (PDOException $e) {
            error_log("Rollback failed: " . $e->getMessage());
            throw new Exception("Failed to rollback transaction");
        }
    }
}