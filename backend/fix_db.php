<?php
require_once 'config/db.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check Locations table for image_url
    $stmt = $db->query("SHOW COLUMNS FROM locations LIKE 'image_url'");
    if($stmt->rowCount() == 0) {
        echo "Adding image_url to locations...\n";
        $db->exec("ALTER TABLE locations ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
    } else {
        echo "image_url exists in locations.\n";
    }

    // Check Users table for phone (just in case)
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'phone'");
    if($stmt->rowCount() == 0) {
        echo "Adding phone to users...\n";
        $db->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
    } else {
        echo "phone exists in users.\n";
    }

    echo "Database schema check complete.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
