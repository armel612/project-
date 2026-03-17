<?php
// controllers/AuthController.php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/JWT.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register($data) {
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        if (strlen($data['password']) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        if ($this->userModel->findByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        if ($this->userModel->create($data)) {
            $user = $this->userModel->findByEmail($data['email']);
            $token = JWT::encode(['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
        }

        return ['success' => false, 'message' => 'Registration failed'];
    }

    public function login($data) {
        if (empty($data['email']) || empty($data['password'])) {
            return ['success' => false, 'message' => 'Email and password required'];
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        if ($this->userModel->isLocked($data['email'])) {
            $this->userModel->logLoginAttempt($data['email'], false, $ip, $userAgent);
            return ['success' => false, 'message' => 'Account locked. Try again later'];
        }

        $user = $this->userModel->findByEmail($data['email']);
        if (!$user) {
            $this->userModel->logLoginAttempt($data['email'], false, $ip, $userAgent);
            $this->userModel->updateLoginAttempts($data['email'], true);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if (!password_verify($data['password'], $user['password'])) {
            $this->userModel->logLoginAttempt($data['email'], false, $ip, $userAgent);
            $this->userModel->updateLoginAttempts($data['email'], true);
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        $this->userModel->updateLoginAttempts($data['email'], false);
        $this->userModel->logLoginAttempt($data['email'], true, $ip, $userAgent);

        $token = JWT::encode(['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);

        return [
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }

    public function getMe($user) {
        return ['success' => true, 'user' => $user];
    }
}