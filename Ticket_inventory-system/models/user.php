<?php
// models/User.php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = getDB();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, name, email, role, is_verified, is_active, created_at FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (name, email, password, role) VALUES (:name, :email, :password, :role)";
        $stmt = $this->db->prepare($sql);
        
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        return $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'role' => $data['role'] ?? 'requester'
        ]);
    }

    public function updateLoginAttempts($email, $failed = true) {
        $user = $this->findByEmail($email);
        if (!$user) return false;

        if ($failed) {
            $attempts = $user['failed_login_attempts'] + 1;
            $lockUntil = null;
            
            if ($attempts >= env('MAX_LOGIN_ATTEMPTS', 5)) {
                $lockMinutes = env('LOCKOUT_DURATION', 15);
                $lockUntil = date('Y-m-d H:i:s', strtotime("+{$lockMinutes} minutes"));
                $attempts = 0;
            }

            $stmt = $this->db->prepare("UPDATE {$this->table} SET failed_login_attempts = :attempts, lock_until = :lock_until WHERE email = :email");
            return $stmt->execute([
                'attempts' => $attempts,
                'lock_until' => $lockUntil,
                'email' => $email
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET failed_login_attempts = 0, lock_until = NULL WHERE email = :email");
            return $stmt->execute(['email' => $email]);
        }
    }

    public function isLocked($email) {
        $user = $this->findByEmail($email);
        if (!$user || !$user['lock_until']) return false;
        return strtotime($user['lock_until']) > time();
    }

    public function logLoginAttempt($email, $success, $ip, $userAgent) {
        $user = $this->findByEmail($email);
        $userId = $user ? $user['id'] : null;

        $stmt = $this->db->prepare("INSERT INTO login_logs (user_id, email, ip_address, user_agent, success) VALUES (:user_id, :email, :ip, :ua, :success)");
        return $stmt->execute([
            'user_id' => $userId,
            'email' => $email,
            'ip' => $ip,
            'ua' => $userAgent,
            'success' => $success ? 1 : 0
        ]);
    }
}