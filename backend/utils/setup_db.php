<?php
include_once __DIR__ . '/../config/db.php';

try {
    $database = new Database();
    // Connect without DB name first to create it
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating database if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS parkease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    echo "Selecting database...\n";
    $pdo->exec("USE parkease");
    
    echo "Importing schema...\n";
    $sql = file_get_contents(__DIR__ . '/../../database.sql');
    
    // Split by semicolon to execute statements individually if needed, 
    // but PDO::exec can handle multiple if driver supports it. 
    // Safer to split for strict drivers.
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }
    
    echo "Database setup completed successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
