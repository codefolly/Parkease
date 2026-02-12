<?php
class Validator {
    public static function clean($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    public function validateRegistration($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = "Name is required";
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }
        
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }

        if (empty($data['phone'])) {
            $errors[] = "Phone number is required";
        }

        if (empty($data['role']) || !in_array($data['role'], ['user', 'vendor'])) {
            $errors[] = "Invalid role selection";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>