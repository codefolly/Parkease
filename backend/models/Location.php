<?php
require_once __DIR__ . '/../config/db.php';

class Location {
    private $conn;
    private $table_name = "locations";

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function create($vendor_id, $data) {
        $query = "INSERT INTO " . $this->table_name . " 
                SET vendor_id=:vendor_id, name=:name, address=:address, description=:description,
                    price_per_hour=:price_per_hour, total_slots=:total_slots, 
                    latitude=:latitude, longitude=:longitude,
                    image_url=:image_url, qr_code_url=:qr_code_url,
                    status='pending', created_at=NOW()";
        
        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($data['name']));
        $address = htmlspecialchars(strip_tags($data['address']));
        $desc = isset($data['description']) ? htmlspecialchars(strip_tags($data['description'])) : '';
        $lat = isset($data['latitude']) ? $data['latitude'] : 0.0;
        $long = isset($data['longitude']) ? $data['longitude'] : 0.0;

        $stmt->bindParam(":vendor_id", $vendor_id);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":address", $address);
        $stmt->bindParam(":description", $desc);
        $stmt->bindParam(":price_per_hour", $data['price_per_hour']);
        $stmt->bindParam(":total_slots", $data['total_slots']);
        $stmt->bindParam(":latitude", $lat);
        $stmt->bindParam(":longitude", $long);
        
        $img = isset($data['image_url']) ? $data['image_url'] : null;
        $qr = isset($data['qr_code_url']) ? $data['qr_code_url'] : null;
        
        $stmt->bindParam(":image_url", $img);
        $stmt->bindParam(":qr_code_url", $qr);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $vendor_id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                SET name=:name, address=:address, price_per_hour=:price_per_hour, total_slots=:total_slots
                WHERE id=:id AND vendor_id=:vendor_id";
        
        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($data['name']));
        $address = htmlspecialchars(strip_tags($data['address']));

        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":address", $address);
        $stmt->bindParam(":price_per_hour", $data['price_per_hour']);
        $stmt->bindParam(":total_slots", $data['total_slots']);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":vendor_id", $vendor_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id, $vendor_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id=:id AND vendor_id=:vendor_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":vendor_id", $vendor_id);
        
        return $stmt->execute();
    }

    public function findByVendor($vendor_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE vendor_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $vendor_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllApproved($queryStr = null) {
        // Subquery to count active bookings (confirmed or pending) that are currently active
        // For simplicity in this "live" view, we'll just subtract active bookings from total.
        // A more complex check would involve time ranges, but for "now", this is sufficient.
        $now = date('Y-m-d H:i:s');
        
        $query = "SELECT l.*, 
                  (l.total_slots - (
                      SELECT COUNT(*) FROM bookings b 
                      WHERE b.location_id = l.id 
                      AND b.status IN ('confirmed', 'pending')
                      AND b.start_time <= :now AND b.end_time > :now
                  )) as available_slots
                  FROM " . $this->table_name . " l 
                  WHERE l.status = 'approved'";
        
        if($queryStr) {
            $query .= " AND (l.name LIKE :q OR l.address LIKE :q)";
        }
        
        $query .= " ORDER BY l.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":now", $now);
        
        if($queryStr) {
            $term = "%{$queryStr}%";
            $stmt->bindParam(":q", $term);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function checkAvailability($location_id, $start, $end) {
        // Get total slots
        $loc = $this->findById($location_id);
        if (!$loc) return false;
        $total = $loc['total_slots'];

        // Count overlapping bookings
        // Overlap logic: (StartA <= EndB) and (EndA >= StartB)
        $query = "SELECT COUNT(*) as booked FROM bookings 
                  WHERE location_id = :lid 
                  AND status IN ('confirmed', 'pending')
                  AND (start_time < :end AND end_time > :start)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":lid", $location_id);
        $stmt->bindParam(":start", $start);
        $stmt->bindParam(":end", $end);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($total - $row['booked']) > 0;
    }
    
    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Admin
    public function getPendingApprovals() {
        $query = "SELECT l.*, u.name as vendor_name, u.email as vendor_email 
                  FROM " . $this->table_name . " l
                  JOIN users u ON l.vendor_id = u.id
                  WHERE l.status = 'pending' ORDER BY l.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approveLocation($id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'approved' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function rejectLocation($id) {
        $query = "UPDATE " . $this->table_name . " SET status = 'rejected' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function findAll() {
        $query = "SELECT l.*, u.name as vendor_name 
                  FROM " . $this->table_name . " l
                  LEFT JOIN users u ON l.vendor_id = u.id
                  ORDER BY l.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }





    public function countApproved() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function countPending() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>