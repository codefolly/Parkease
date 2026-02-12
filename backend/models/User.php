<?php
require_once __DIR__ . '/../config/db.php';

class User {
    private $conn;
    private $table_name = "users";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                SET name=:name, email=:email, password=:password, phone=:phone, role=:role, created_at=NOW()";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $name = htmlspecialchars(strip_tags($data['full_name']));
        $email = htmlspecialchars(strip_tags($data['email']));
        $phone = htmlspecialchars(strip_tags($data['phone']));
        $role = htmlspecialchars(strip_tags($data['role']));
        $password = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":role", $role);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function isEmailTaken($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function validateCredentials($email, $password) {
        $query = "SELECT id, name, email, password, role, phone FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                // Return user array but map 'name' to 'full_name' for consistency
                $row['full_name'] = $row['name'];
                unset($row['password']);
                return $row;
            }
        }
        return false;
    }

    public function findById($id) {
        $query = "SELECT id, name as full_name, email, role, phone FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function countUsers() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE role = 'user'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function countVendors() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE role = 'vendor'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
    public function findAll() {
        $query = "SELECT id, name as full_name, email, role, phone, created_at FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>