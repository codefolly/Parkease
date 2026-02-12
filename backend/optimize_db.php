<?php
require_once __DIR__ . '/config/db.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Starting Database Optimization...\n";
    
    // Helper to check if index exists
    function indexExists($conn, $table, $indexName) {
        $stmt = $conn->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
        $stmt->execute([$indexName]);
        return $stmt->rowCount() > 0;
    }
    
    // 1. Optimize Locations Table
    if (!indexExists($conn, 'locations', 'idx_status')) {
        echo "Adding index 'idx_status' to locations table...\n";
        $conn->exec("ALTER TABLE locations ADD INDEX idx_status (status)");
        echo "Success.\n";
    } else {
        echo "Index 'idx_status' already exists on locations.\n";
    }
    
    // 2. Optimize Bookings Table
    // Query: WHERE location_id = ? AND status IN (...) AND (time overlaps)
    if (!indexExists($conn, 'bookings', 'idx_availability')) {
        echo "Adding index 'idx_availability' to bookings table...\n";
        $conn->exec("ALTER TABLE bookings ADD INDEX idx_availability (location_id, status, start_time, end_time)");
        echo "Success.\n";
    } else {
        echo "Index 'idx_availability' already exists on bookings.\n";
    }
    
    // 3. User Lookup Optimization
    if (!indexExists($conn, 'users', 'idx_email')) {
        // Email is already UNIQUE so it has an index, but checking just in case primary/unique logic differs
        // Usually UNIQUE constraint creates an index named 'email'
        // Let's check for 'email' index which might be the unique one.
        if (!indexExists($conn, 'users', 'email')) {
             echo "Adding index 'idx_email' to users table...\n";
             $conn->exec("ALTER TABLE users ADD INDEX idx_email (email)");
             echo "Success.\n";
        } else {
             echo "Unique Index 'email' already exists on users.\n";
        }
    }

    echo "Database Optimization Completed Successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
