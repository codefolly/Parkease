<?php
// backend/controllers/AuthController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/CSRF.php';
require_once __DIR__ . '/../utils/Validator.php';

class AuthController {
    private $userModel;
    private $validator;
    
    public function __construct() {
        $this->userModel = new User();
        $this->validator = new Validator();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function register($data) {
        try {
            // CSRF protection - Weakened for now as frontend might not send it yet
            // if (!CSRF::validate($data['csrf_token'] ?? '')) {
            //     return ['success' => false, 'message' => 'Invalid CSRF token'];
            // }
            
            // Validate input
            $validation = $this->validator->validateRegistration($data);
            if (!$validation['valid']) {
                // Return first error message
                $msg = reset($validation['errors']);
                return ['success' => false, 'message' => $msg];
            }
            
            // Check if email exists
            if ($this->userModel->isEmailTaken($data['email'])) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Create user
            $userId = $this->userModel->create([
                'email' => $data['email'],
                'password' => $data['password'],
                'full_name' => $data['name'], // Frontend sends 'name'
                'phone' => $data['phone'],
                'role' => $data['role']
            ]);
            
            if ($userId) {
                // Set session
                $user = $this->userModel->findById($userId);
                $this->setSession($user);
                
                return [
                    'success' => true,
                    'message' => ($data['role'] === 'vendor') ? 
                        'Registration successful! Your account is pending admin approval.' : 
                        'Registration successful!',
                    'user' => $user
                ];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
            
        } catch (Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function login($data) {
        try {
            // CSRF protection
            // if (!CSRF::validate($data['csrf_token'] ?? '')) {
            //     return ['success' => false, 'message' => 'Invalid CSRF token'];
            // }
            
            // Validate input
            if (empty($data['email']) || empty($data['password'])) {
                return ['success' => false, 'message' => 'Email and password are required'];
            }
            
            if (!Database::validateEmail($data['email'])) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            // Authenticate
            $user = $this->userModel->validateCredentials($data['email'], $data['password']);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Set session based on role
            $this->setSession($user);
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function checkAuth() {
        if (isset($_SESSION['user_id'])) {
            $user = $this->userModel->findById($_SESSION['user_id']);
            if ($user) {
                return [
                    'success' => true,
                    'authenticated' => true,
                    'user' => $user
                ];
            }
        }
        return ['success' => false, 'authenticated' => false];
    }
    
    public function requireAuth($role = null) {
        $auth = $this->checkAuth();
        
        if (!$auth['authenticated']) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
        
        if ($role && $auth['user']['role'] !== $role) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }
        
        return $auth['user'];
    }
    
    private function setSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        //$_SESSION['csrf_token'] = CSRF::generate();
        
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
    }
}
?>
