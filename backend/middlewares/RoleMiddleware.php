<?php
// backend/middlewares/RoleMiddleware.php
session_start();

class RoleMiddleware {
    public static function requireRole(array $roles) {
        if (empty($_SESSION['user']) || !in_array($_SESSION['user']['role'], $roles, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
    }
}