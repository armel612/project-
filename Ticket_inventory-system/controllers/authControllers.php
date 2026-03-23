<?php
// controllers/AuthController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/jwt.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login($data) {
        if (empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Email and Password required'];
        }

        $email = $data['email'];
        $password = $data['password'];

        $user = $this->userModel->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $token = JWT::encode([
            'id'    => (int)$user['id'],
            'email' => $user['email'],
            'role'  => $user['role']
        ]);

        return [
            'success' => true,
            'message' => "Welcome to Eneo, {$user['name']}!",
            'token'   => $token,
            'user'    => [
                'id'    => (int)$user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role']
            ]
        ];
    }
}