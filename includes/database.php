<?php
/**
 * Database connection class for PostgreSQL
 */
class Database {
    private $conn;
    private static $instance = null;
    private $config;
    
    /**
     * Constructor - Load database configuration and establish connection
     */
    private function __construct() {
        $this->config = include __DIR__ . '/../db_config.php';
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Establish database connection
     */
    private function connect() {
        $dsn = "pgsql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']}";
        
        try {
            $this->conn = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            // Set the schema
            if (isset($this->config['schema']) && !empty($this->config['schema'])) {
                $this->conn->exec("SET search_path TO {$this->config['schema']}");
            }
            
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Execute a query with parameters
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch a single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Insert a record and return the ID
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        
        $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ") 
                RETURNING id";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($data as $key => $value) {
            // Properly handle different data types
            if (is_bool($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_BOOL);
            } elseif (is_int($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } elseif (is_null($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Update a record
     */
    public function update($table, $data, $condition, $conditionParams = []) {
        $fields = array_keys($data);
        $setClauses = array_map(function($field) {
            return "$field = :$field";
        }, $fields);
        
        $sql = "UPDATE $table SET " . implode(', ', $setClauses) . " WHERE $condition";
        
        $stmt = $this->conn->prepare($sql);
        
        // Bind data values with proper type handling
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_BOOL);
            } elseif (is_int($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } elseif (is_null($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
            }
        }
        
        // Bind condition parameters
        foreach ($conditionParams as $key => $value) {
            if (is_bool($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_BOOL);
            } elseif (is_int($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } elseif (is_null($value)) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(":$key", $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt->rowCount();
    }
    
    /**
     * Delete a record
     */
    public function delete($table, $condition, $params = []) {
        $sql = "DELETE FROM $table WHERE $condition";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    /**
     * Get the PDO connection object
     */
    public function getConnection() {
        return $this->conn;
    }
}
?>