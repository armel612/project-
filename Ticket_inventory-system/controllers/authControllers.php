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
        if (empty($data['email']) || empty($data['name'])) {
            return ['success' => false, 'message' => 'Name and Email required'];
        }

        $email = $data['email'];
        $name = $data['name'];

        $user = $this->userModel->findByNameAndEmail($name, $email);
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        $token = JWT::encode(['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);

        return [
            'success' => true,
            'message' => "Welcome, {$user['role']}!",
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }
}