<?php
// backend/middlewares/AuthMiddleware.php
session_start();

class AuthMiddleware {
    public static function requireLogin() {
        if (empty($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit;
        }
    }
}