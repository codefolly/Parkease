<?php
// tests/system_test.php

// Mock Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include Controllers
require_once __DIR__ . '/../backend/controllers/AuthController.php';
require_once __DIR__ . '/../backend/controllers/LocationController.php';
require_once __DIR__ . '/../backend/controllers/AdminController.php';
require_once __DIR__ . '/../backend/controllers/BookingController.php';
require_once __DIR__ . '/../backend/config/db.php';

function test_step($name, $callback) {
    echo "Testing: $name ... ";
    try {
        $result = $callback();
        if ($result['success']) {
            echo "PASSED\n";
            return $result;
        } else {
            echo "FAILED (" . ($result['message'] ?? 'Unknown error') . ")\n";
            exit(1);
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// 0. Cleanup (Optional, but good for repeatability)
$db = Database::getInstance()->getConnection();
$db->exec("DELETE FROM users WHERE email LIKE 'test_%@example.com'");

// 1. Create Vendor
$vendorEmail = 'test_vendor_' . time() . '@example.com';
test_step("Register Vendor", function() use ($vendorEmail) {
    $auth = new AuthController();
    return $auth->register([
        'name' => 'Test Vendor',
        'email' => $vendorEmail,
        'password' => 'password123',
        'phone' => '9800000000',
        'role' => 'vendor'
    ]);
});

// Login Vendor
test_step("Login Vendor", function() use ($vendorEmail) {
    $auth = new AuthController();
    return $auth->login([
        'email' => $vendorEmail,
        'password' => 'password123'
    ]);
});

// 2. Vendor Add Location
$locationId = 0;
test_step("Add Location", function() use (&$locationId) {
    $loc = new LocationController();
    // Simulate $_POST for form data if needed, but controller takes $data in create($data)? 
    // Wait, router passes $_POST to create($data) for multipart.
    // LocationController::create use $data for fields, but checks $_FILES.
    // We pass array as $data.
    $result = $loc->create([
        'name' => 'Test Parking Spot',
        'address' => 'Test Address',
        'description' => 'A test spot',
        'price_per_hour' => 50,
        'total_slots' => 5,
        'latitude' => 27.42,
        'longitude' => 85.03
    ]);
    
    if ($result['success']) {
        $locationId = $result['location_id'];
    }
    return $result;
});

// 3. Admin Approve
// Login Admin
test_step("Login Admin", function() {
    $auth = new AuthController();
    return $auth->login([
        'email' => 'admin@parkease.com',
        'password' => 'admin123' 
    ]);
});

test_step("Approve Location", function() use ($locationId) {
    $loc = new LocationController();
    return $loc->approve(['id' => $locationId]);
});

// 4. Create User
$userEmail = 'test_user_' . time() . '@example.com';
test_step("Register User", function() use ($userEmail) {
    $auth = new AuthController();
    return $auth->register([
        'name' => 'Test User',
        'email' => $userEmail,
        'password' => 'password123',
        'phone' => '9811111111',
        'role' => 'user'
    ]);
});

// Login User
test_step("Login User", function() use ($userEmail) {
    $auth = new AuthController();
    return $auth->login([
        'email' => $userEmail,
        'password' => 'password123'
    ]);
});

// 5. User Book Slot
test_step("Book Slot", function() use ($locationId) {
    $booking = new BookingController();
    return $booking->create([
        'location_id' => $locationId,
        'start_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+3 hours')),
        'total_price' => 100
    ]);
});

echo "\nAll System Tests Passed!\n";
?>
