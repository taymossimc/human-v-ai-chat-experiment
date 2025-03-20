<?php
require_once __DIR__ . '/../config/config.php';

class Database {
    private $conn;
    private static $instance = null;

    // Private constructor - singleton pattern
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, 
                DB_USER, 
                DB_PASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch (PDOException $e) {
            if (DEBUG) {
                die("Connection failed: " . $e->getMessage());
            } else {
                die("Database connection error. Please try again later.");
            }
        }
    }

    // Get instance of the database connection
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Get PDO connection
    public function getConnection() {
        return $this->conn;
    }

    // Execute a prepared statement with parameters
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (DEBUG) {
                die("Query failed: " . $e->getMessage());
            } else {
                die("An error occurred with the database. Please try again later.");
            }
        }
    }

    // Get a single row
    public function fetchOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get multiple rows
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insert data and return the last insert ID
    public function insert($sql, $params = []) {
        $this->execute($sql, $params);
        return $this->conn->lastInsertId();
    }

    // Update data and return number of affected rows
    public function update($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    // Delete data and return number of affected rows
    public function delete($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    // Begin a transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    // Commit a transaction
    public function commit() {
        return $this->conn->commit();
    }

    // Rollback a transaction
    public function rollback() {
        return $this->conn->rollBack();
    }
}
?> 