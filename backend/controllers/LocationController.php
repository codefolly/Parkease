<?php
// backend/controllers/LocationController.php

require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/AuthController.php';

class LocationController {
    private $locationModel;
    private $authController;
    
    public function __construct() {
        $this->locationModel = new Location();
        $this->authController = new AuthController();
    }
    
    public function create($data) {
        try {
            $user = $this->authController->requireAuth('vendor');
            
            // Validate input
            $errors = $this->validateLocationData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => reset($errors)];
            }

            // Handle Image Upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/uploads/locations/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_img_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = 'assets/uploads/locations/' . $fileName;
                }
            }
            
            // Handle QR Code Upload
            $qrPath = null;
            if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/uploads/qr/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_qr_' . basename($_FILES['qr_code']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $targetPath)) {
                    $qrPath = 'assets/uploads/qr/' . $fileName;
                }
            }
            
            // Add paths to data
            $data['image_url'] = $imagePath;
            $data['qr_code_url'] = $qrPath;
            
            // Create location
            $locationId = $this->locationModel->create($user['id'], $data);
            
            if ($locationId) {
                return [
                    'success' => true,
                    'message' => 'Parking location added successfully! Waiting for admin approval.',
                    'location_id' => $locationId
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to add parking location'];
            
        } catch (Exception $e) {
            error_log("Create Location Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function update($id, $data) {
        try {
            $user = $this->authController->requireAuth('vendor');
            
            // Validate input
            $errors = $this->validateLocationData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => reset($errors)];
            }
            
            // Update location
            $success = $this->locationModel->update($id, $user['id'], $data);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Parking location updated successfully!'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to update parking location'];
            
        } catch (Exception $e) {
            error_log("Update Location Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function delete($data) {
        try {
            $user = $this->authController->requireAuth('vendor');
            
            $id = $data['id'] ?? null;
            if(!$id) return ['success' => false, 'message' => 'ID required'];

            $success = $this->locationModel->delete($id, $user['id']);
            
            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Parking location deleted successfully'
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to delete parking location'];
            
        } catch (Exception $e) {
            error_log("Delete Location Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getMyLocations() { // Renamed from getVendorLocations to match Router
         return $this->getVendorLocations();
    }

    public function getVendorLocations() {
        try {
            $user = $this->authController->requireAuth('vendor');
            $locations = $this->locationModel->findByVendor($user['id']);
            
            return [
                'success' => true,
                'data' => $locations // Changed to 'data' to match generic API pattern or stick to 'locations'? 
                // Router used to echo directly. App.js expects `result.data`.
                // Old app.js `loadLocations`: result.data.map
                // AuthController returns `user`. 
                // Let's stick to `data` for arrays.
            ];
            
        } catch (Exception $e) {
            error_log("Get Vendor Locations Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getAllApproved() { // Matching router
        try {
            $search = isset($_GET['q']) ? $_GET['q'] : null;
            $locations = $this->locationModel->getAllApproved($search);
            
            return [
                'success' => true,
                'data' => $locations
            ];
            
        } catch (Exception $e) {
            error_log("Get Available Locations Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Admin methods
    public function approve($data) { // Router calls approve($data)
        try {
            $this->authController->requireAuth('admin');
            $locationId = $data['id'];
            $success = $this->locationModel->approveLocation($locationId);
            
            return ['success' => $success, 'message' => $success ? 'Approved' : 'Failed'];
        } catch (Exception $e) { return ['success' => false, 'message' => $e->getMessage()]; }
    }

    public function reject($data) {
        try {
            $this->authController->requireAuth('admin');
            $locationId = $data['id'];
            $success = $this->locationModel->rejectLocation($locationId);
            return ['success' => $success, 'message' => $success ? 'Rejected' : 'Failed'];
        } catch (Exception $e) { return ['success' => false, 'message' => $e->getMessage()]; }
    }

    public function getAll() { // Router calls getAll for admin
        try {
             $this->authController->requireAuth('admin');
             $locations = $this->locationModel->findAll();
             return ['success' => true, 'data' => $locations];
        } catch(Exception $e) { return ['success' => false, 'message' => $e->getMessage()]; }
    }

    private function validateLocationData($data) {
        $errors = [];
        
        if (empty($data['name'])) $errors['name'] = 'Name is required';
        if (empty($data['address'])) $errors['address'] = 'Address is required';
        if (empty($data['total_slots'])) $errors['total_slots'] = 'Total slots is required';
        if (empty($data['price_per_hour'])) $errors['price_per_hour'] = 'Price per hour is required';
        
        return $errors;
    }
}
?>
