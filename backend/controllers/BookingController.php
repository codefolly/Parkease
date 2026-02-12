<?php
// backend/controllers/BookingController.php

require_once __DIR__ . '/../models/Booking.php';
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/AuthController.php';

class BookingController {
    private $bookingModel;
    private $locationModel;
    private $authController;
    
    public function __construct() {
        $this->bookingModel = new Booking();
        $this->locationModel = new Location();
        $this->authController = new AuthController();
    }
    
    public function create($data) {
        try {
            $user = $this->authController->requireAuth('user');
            
            // Validate input
            $errors = $this->validateBookingData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => reset($errors)];
            }
            
            // Format datetime (remove T from datetime-local)
            $data['start_time'] = str_replace('T', ' ', $data['start_time']);
            $data['end_time'] = str_replace('T', ' ', $data['end_time']);
            
            // Add user ID to data
            $data['user_id'] = $user['id'];
            
            // Create booking
            // If internal logic uses parking_id, model expects it.
            if(empty($data['parking_id']) && !empty($data['location_id'])) {
                $data['parking_id'] = $data['location_id'];
            }

            // Verify availability
            if (!$this->locationModel->checkAvailability($data['parking_id'], $data['start_time'], $data['end_time'])) {
                // Debug response for now
                error_log("Unavailable: Slot ID " . $data['parking_id'] . " Time: " . $data['start_time'] . " to " . $data['end_time']);
                return ['success' => false, 'message' => 'Slot unavailable for selected time. (Already booked or invalid time)'];
            }

            $bookingId = $this->bookingModel->create($data);
            
            if($bookingId) {
                return [
                    'success' => true, 
                    'message' => 'Booking created successfully! Please complete payment.',
                    'booking_id' => $bookingId
                ];
            }
             
             return ['success' => false, 'message' => 'Booking failed.'];

        } catch (Exception $e) {
            error_log("Create Booking Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getMyBookings() {
        try {
            $user = $this->authController->requireAuth();
            if($user['role'] === 'vendor') {
                 $bookings = $this->bookingModel->findByVendor($user['id']);
            } else {
                 $bookings = $this->bookingModel->findByUser($user['id']);
            }
            return ['success' => true, 'data' => $bookings];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function cancel($data) {
        try {
            $user = $this->authController->requireAuth('user');
            
            $bookingId = $data['booking_id'] ?? null;
            if (!$bookingId) return ['success' => false, 'message' => 'Booking ID is required'];
            
            $success = $this->bookingModel->cancel($bookingId, $user['id']);
            
            if ($success) {
                return ['success' => true, 'message' => 'Booking cancelled successfully!'];
            }
            return ['success' => false, 'message' => 'Failed to cancel booking'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Admin methods
    public function getAllBookings() {
        try {
             $result = $this->bookingModel->getAllBookings(); // Returns ['success'=>true, 'data'=>...]
             return $result;
        } catch(Exception $e) { return ['success' => false, 'message' => $e->getMessage()]; }
    }
    
    private function validateBookingData($data) {
        $errors = [];
        
        // Allow parking_id OR location_id
        if (empty($data['parking_id']) && empty($data['location_id'])) $errors['parking_id'] = 'Parking location is required';
        if (empty($data['start_time'])) $errors['start_time'] = 'Start time is required';
        if (empty($data['end_time'])) $errors['end_time'] = 'End time is required';
        
        // Relaxed slot/vehicle validation
        // if (empty($data['slot_number'])) $errors['slot_number'] = 'Slot number is required';
        
        return $errors;
    }
}
?>
