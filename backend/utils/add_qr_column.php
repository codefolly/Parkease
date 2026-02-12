<?php
require_once __DIR__ . '/../config/db.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $check = $db->query("SHOW COLUMNS FROM locations LIKE 'qr_code_url'");
    if($check->rowCount() == 0) {
        $sql = "ALTER TABLE locations ADD COLUMN qr_code_url VARCHAR(255) DEFAULT NULL AFTER image_url";
        $db->exec($sql);
        echo "Column 'qr_code_url' added successfully.";
    } else {
        echo "Column 'qr_code_url' already exists.";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
