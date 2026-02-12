<?php
require_once __DIR__ . '/../config/db.php';

class Booking {
    private $conn;
    private $table_name = "bookings";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                SET user_id=:user_id, location_id=:location_id, start_time=:start_time, 
                    end_time=:end_time, total_price=:total_price, status='pending', created_at=NOW()";
        
        // Handle optional payment proof if passed
        if(isset($data['payment_proof'])) {
            $query .= ", payment_proof=:payment_proof";
        }

        $stmt = $this->conn->prepare($query);

        $locId = isset($data['parking_id']) ? $data['parking_id'] : $data['location_id'];

        $stmt->bindParam(":user_id", $data['user_id']);
        $stmt->bindParam(":location_id", $locId);
        $stmt->bindParam(":start_time", $data['start_time']);
        $stmt->bindParam(":end_time", $data['end_time']);
        $stmt->bindParam(":total_price", $data['total_price']);
        
        if(isset($data['payment_proof'])) {
            $stmt->bindParam(":payment_proof", $data['payment_proof']);
        }

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function findByUser($user_id) {
        $query = "SELECT b.*, l.name as location_name, l.address, l.latitude, l.longitude 
                  FROM " . $this->table_name . " b
                  JOIN locations l ON b.location_id = l.id
                  WHERE b.user_id = ? ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByVendor($vendor_id) {
        $query = "SELECT b.*, l.name as location_name, u.name as user_name, u.email as user_email
                  FROM " . $this->table_name . " b
                  JOIN locations l ON b.location_id = l.id
                  JOIN users u ON b.user_id = u.id
                  WHERE l.vendor_id = ? ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $vendor_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        // Return booking with parking_id alias for controller compatibility if needed, 
        // but controller mostly checks user_id.
        $query = "SELECT *, location_id as parking_id FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function confirmPayment($id, $transaction_id) {
        // We might store transaction_id in payment_proof or a new column?
        // Existing schema has payment_proof varchar.
        $query = "UPDATE " . $this->table_name . " SET status = 'confirmed', payment_proof = :proof WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":proof", $transaction_id);
        return $stmt->execute();
    }

    public function cancel($id, $user_id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'cancelled' WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":user_id", $user_id);
        return $stmt->execute();
    }

    public function getStats($userId = null) {
        // Simple stats
        $stats = [
            'total_bookings' => 0,
            'spent' => 0,
            'earned' => 0
        ];
        
        if($userId) {
            // Check if user or vendor? 
            // Assuming this is for dashboard stats.
            // Let's just return empty stats for now as it's not critical for login/register fix.
        }
        return $stats;
    }

    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function sumRevenue() {
        $query = "SELECT SUM(total_price) as total FROM " . $this->table_name . " WHERE status IN ('confirmed', 'completed')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ? $row['total'] : 0;
    }

    public function getAllBookings() {
        $query = "SELECT b.*, l.name as location_name, u.name as user_name, u.email as user_email 
                  FROM " . $this->table_name . " b
                  LEFT JOIN locations l ON b.location_id = l.id
                  LEFT JOIN users u ON b.user_id = u.id
                  ORDER BY b.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'data' => $bookings];
    }
}
?>