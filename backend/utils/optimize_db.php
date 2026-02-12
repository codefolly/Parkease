<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Index definitions
    $indexes = [
        ['table' => 'locations', 'name' => 'idx_locations_status', 'col' => 'status'],
        ['table' => 'locations', 'name' => 'idx_locations_geo', 'col' => 'latitude, longitude'],
        ['table' => 'locations', 'name' => 'idx_locations_vendor', 'col' => 'vendor_id'],
        ['table' => 'bookings', 'name' => 'idx_bookings_user', 'col' => 'user_id'],
        ['table' => 'bookings', 'name' => 'idx_bookings_location', 'col' => 'location_id'],
        ['table' => 'bookings', 'name' => 'idx_bookings_status', 'col' => 'status'],
        ['table' => 'bookings', 'name' => 'idx_bookings_dates', 'col' => 'start_time, end_time'],
        ['table' => 'users', 'name' => 'idx_users_role', 'col' => 'role']
    ];
    
    $results = [];

    foreach ($indexes as $idx) {
        $tableName = $idx['table'];
        $colName = $idx['col'];
        $indexName = $idx['name'];
        
        // Check if index exists by querying information_schema (MySQL/MariaDB) or show index
        try {
            // "CREATE INDEX IF NOT EXISTS" is MariaDB 10.0.2+ / MySQL 8.0+
            // Let's just try to create it. If it fails, we catch it.
            $sql = "CREATE INDEX " . $indexName . " ON " . $tableName . " (" . $colName . ")";
            $db->exec($sql);
            $results[] = ["index" => $indexName, "status" => "created"];
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            // 42000 is syntax error, but usually for "already exists" it's 42000 with specific message or 1061
            $results[] = ["index" => $indexName, "status" => "exists_or_error", "msg" => $msg];
        }
    }
    
    echo json_encode(["success" => true, "message" => "Database optimization complete.", "details" => $results]);

} catch (Exception $e) {
    // Catch generic errors to see what's wrong outside the loop
    echo json_encode(["success" => false, "message" => "Critical Error: " . $e->getMessage()]);
}
