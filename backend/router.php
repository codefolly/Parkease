<?php
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
header("Access-Control-Allow-Origin: $origin");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'utils/CSRF.php';
include_once 'controllers/AuthController.php';
include_once 'controllers/LocationController.php'; 
include_once 'controllers/BookingController.php';
include_once 'controllers/AdminController.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

$auth = new AuthController();
$location = new LocationController();
$booking = new BookingController();
$admin = new AdminController();

$response = ['status' => 'error', 'message' => 'Invalid endpoint'];

// Basic Routing
try {
    switch ($action) {
        // ... Auth routes ...
        case 'register':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $response = $auth->register($data);
            }
            break;
        case 'login':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $response = $auth->login($data);
            }
            break;
        case 'logout':
            $response = $auth->logout();
            break;
        case 'check_auth':
            $response = $auth->checkAuth();
            break;

        // Location Routes
        case 'add_location':
            if ($method === 'POST') {
                // Handle Multipart
                $data = $_POST;
                $response = $location->create($data);
            }
            break;
        case 'update_location':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $id = $data['id'] ?? null;
                $response = $location->update($id, $data);
            }
            break;
        case 'delete_location':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $response = $location->delete($data);
            }
            break;
        case 'get_my_locations':
            $response = $location->getMyLocations();
            break;
        case 'get_approved_locations':
            $response = $location->getAllApproved();
            break;
        case 'get_all_locations': // Admin
            $response = $location->getAll();
            break;
        case 'approve_location': // Admin
            if ($method === 'POST') {
                 $data = json_decode(file_get_contents("php://input"), true);
                 $response = $location->approve($data);
            }
            break;
        case 'reject_location': // Admin
            if ($method === 'POST') {
                 $data = json_decode(file_get_contents("php://input"), true);
                 $response = $location->reject($data);
            }
            break;

        // Booking Routes
        case 'book_slot':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $response = $booking->create($data);
            }
            break;
        case 'get_my_bookings':
            $response = $booking->getMyBookings();
            break;
        case 'cancel_booking':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents("php://input"), true);
                $response = $booking->cancel($data);
            }
            break;

        // Admin Routes
        case 'get_admin_stats':
            $response = $admin->getStats();
            break;
        case 'get_all_users':
            $response = $admin->getUsers();
            break;
        case 'get_all_bookings':
            $response = $booking->getAllBookings();
            break;
            
        default:
            // Keep default error
            break;
    }
} catch (Exception $e) {
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

echo json_encode($response);
?>