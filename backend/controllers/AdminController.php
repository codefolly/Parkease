<?php
// backend/controllers/AdminController.php

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/../models/Booking.php';

class AdminController {
    private $auth;
    private $userModel;
    private $locationModel;
    private $bookingModel;

    public function __construct() {
        $this->auth = new AuthController();
        // Assuming models exist or using generic count queries if methods don't exist
        // For now, I'll instantiate them. If methods are missing, I'll add them to models in next step or use raw query here if needed (but better in model).
        $this->userModel = new User(); 
        $this->locationModel = new Location(); 
        $this->bookingModel = new Booking();
    }

    public function getUsers() {
        try {
            $this->auth->requireAuth('admin');
            $users = $this->userModel->findAll();
            return ['success' => true, 'data' => $users];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getStats() {
        try {
            $this->auth->requireAuth('admin');

            // We need to implement count methods in models or just fetch all and count (inefficient but works for small scale)
            // Or add specific count methods. Let's assume we fetch all for now or add methods.
            // Actually, for simplicity in this "hackathon" style task, I will just query DB or add methods.
            // Let's check if models have "getAll" or similar.
            
            // I'll add simple count queries to models in a bit. For now let's try to call methods I plan to add or existing ones.
            
            // Mocking data if models don't support it yet, but best to implement.
            // Let's implement basic counters in this controller using direct DB access via the models' connection if possible?
            // Models extend nothing but usually have a db connection.
            // Let's check User model.
            
            // Since I can't check User model right this second inside this `write_to_file`, 
            // I will assume I need to add methods to Models.
            // But to make this file valid, I will call methods I am ABOUT to add.

            $stats = [
                'total_users' => $this->userModel->countUsers(),
                'total_vendors' => $this->userModel->countVendors(),
                'active_locations' => $this->locationModel->countApproved(),
                'pending_approvals' => $this->locationModel->countPending(),
                'total_bookings' => $this->bookingModel->countAll(),
                'revenue' => $this->bookingModel->sumRevenue()
            ];

            return ['success' => true, 'data' => $stats];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>
