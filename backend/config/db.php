<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private $host = "localhost";
    private $db_name = "parkease";
    private $username = "root";
    private $password = "";

    private function __construct() {
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $exception->getMessage()]);
            exit;
        }
    }

    public static function getInstance() {
        if(!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
    
    // Helper to validate email (used by AuthController)
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
?>