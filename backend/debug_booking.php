<?php
require_once __DIR__ . '/controllers/BookingController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/LocationController.php';
require_once __DIR__ . '/config/db.php';

// mock session
if (session_status() === PHP_SESSION_NONE) session_start();

function logMsg($msg) { echo "[DEBUG] $msg\n"; }

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Ensure we have a normal USER
    $email = 'debug_user_'.time().'@test.com';
    $password = 'password123';
    $auth = new AuthController();
    $regResult = $auth->register([
        'email' => $email,
        'password' => $password,
        'name' => 'Debug User',
        'phone' => '9800000000',
        'role' => 'user'
    ]);
    
    if (!$regResult['success']) {
        die("Failed to create test user: " . $regResult['message']);
    }
    logMsg("Created User: $email");
    $userId = $regResult['user']['id'];

    // 2. Ensure we have a VENDOR and LOCATION
    $vendorEmail = 'debug_vendor_'.time().'@test.com';
    $vendorReg = $auth->register([
        'email' => $vendorEmail,
        'password' => $password,
        'name' => 'Debug Vendor',
        'phone' => '9800000001',
        'role' => 'vendor'
    ]);
    $vendorId = $vendorReg['user']['id'];
    logMsg("Created Vendor: $vendorEmail ($vendorId)");

    // Create Location directly via Model to skip Auth check for simplicity or login as vendor?
    // Let's interact via LocationController but we need to be logged in as vendor.
    // Hack: Manually insert location or login as vendor.
    // Let's manually insert for speed.
    $stmt = $db->prepare("INSERT INTO locations (vendor_id, name, address, price_per_hour, total_slots, latitude, longitude, status) VALUES (?, 'Debug Spot', '123 Test St', 100, 5, 27.0, 85.0, 'approved')");
    $stmt->execute([$vendorId]);
    $locationId = $db->lastInsertId();
    logMsg("Created Location ID: $locationId");

    // 3. Login as USER again to book
    $auth->login(['email' => $email, 'password' => $password]);
    logMsg("Logged in as User");

    // 4. Attempt Booking
    $booking = new BookingController();
    $startTime = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $endTime = date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    $bookingData = [
        'location_id' => $locationId,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'total_price' => 100
    ];

    logMsg("Attempting Booking for Location $locationId from $startTime to $endTime...");
    $result = $booking->create($bookingData);

    if ($result['success']) {
        logMsg("SUCCESS: Booking Created. ID: " . $result['booking_id']);
    } else {
        logMsg("FAILURE: " . $result['message']);
    }

} catch (Exception $e) {
    logMsg("EXCEPTION: " . $e->getMessage());
}
?>
