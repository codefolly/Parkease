<?php
require_once __DIR__ . '/config/db.php';

$db = Database::getInstance()->getConnection();

$password = password_hash("admin123", PASSWORD_BCRYPT);
$email = "admin@parkease.com";

try {
    // Check if admin exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if($stmt->rowCount() > 0) {
        // Update
        $update = $db->prepare("UPDATE users SET password = ?, role = 'admin' WHERE email = ?");
        $update->execute([$password, $email]);
        echo "Admin password reset to 'admin123'.\n";
    } else {
        // Create
        $create = $db->prepare("INSERT INTO users (name, email, password, role) VALUES ('System Administrator', ?, ?, 'admin')");
        $create->execute([$email, $password]);
        echo "Admin account created with password 'admin123'.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
