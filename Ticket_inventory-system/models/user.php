<?php
// models/User.php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = getDB();
    }

    // ─── Finders ───────────────────────────────────────────────
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT id, name, email, password, role, is_active, phone, specialty FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findByNameAndEmail($name, $email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email AND name = :name");
        $stmt->execute(['email' => $email, 'name' => $name]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, name, email, role, phone, specialty, created_at FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    // ─── Admin: List all users (with their privileges) ─────────
    public function getAllUsers() {
        // Fetch users and their privileges (if any)
        $sql = "SELECT u.id, u.name, u.email, u.role, u.phone, u.specialty, u.created_at, 
                       GROUP_CONCAT(p.privilege) as privileges
                FROM {$this->table} u
                LEFT JOIN privileges p ON u.id = p.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        $stmt = $this->db->query($sql);
        $users = $stmt->fetchAll();
        
        foreach ($users as &$user) {
            $user['privileges'] = $user['privileges'] ? explode(',', $user['privileges']) : [];
        }
        return $users;
    }

    public function findTechnicians() {
        $stmt = $this->db->prepare("SELECT id, name, specialty, phone, email FROM {$this->table} WHERE role = 'it_technician' AND is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ─── Admin: Create user ─────────────────────────────────────
    public function addUser($data) {
        $sql = "INSERT INTO {$this->table} (name, email, password, role, phone, specialty) 
                VALUES (:name, :email, :password, :role, :phone, :specialty)";
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data['password'] ?? 'password123', PASSWORD_BCRYPT);
        $stmt->execute([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $hashedPassword,
            'role'      => $data['role'] ?? 'employee',
            'phone'     => $data['phone'] ?? null,
            'specialty' => $data['specialty'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    // ─── Admin: Remove user ─────────────────────────────────────
    public function deleteUser($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    // ─── Admin: Update role ─────────────────────────────────────
    public function updateUserRole($id, $role) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET role = :role WHERE id = :id");
        return $stmt->execute(['role' => $role, 'id' => $id]);
    }

    // ─── Privileges ─────────────────────────────────────────────
    public function getUserPrivileges($userId) {
        $stmt = $this->db->prepare(
            "SELECT p.privilege, u.name AS granted_by_name
             FROM privileges p
             JOIN users u ON u.id = p.granted_by
             WHERE p.user_id = :uid"
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function grantPrivilege($userId, $privilege, $grantedBy) {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO privileges (user_id, privilege, granted_by) VALUES (:uid, :priv, :by)"
        );
        return $stmt->execute(['uid' => $userId, 'priv' => $privilege, 'by' => $grantedBy]);
    }

    public function revokePrivilege($userId, $privilege) {
        $stmt = $this->db->prepare(
            "DELETE FROM privileges WHERE user_id = :uid AND privilege = :priv"
        );
        return $stmt->execute(['uid' => $userId, 'priv' => $privilege]);
    }

    public function hasPrivilege($userId, $privilege) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM privileges WHERE user_id = :uid AND privilege = :priv"
        );
        $stmt->execute(['uid' => $userId, 'priv' => $privilege]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ─── Auth helpers ────────────────────────────────────────────
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
            $stmt = $this->db->prepare("UPDATE {$this->table} SET failed_login_attempts=:a, lock_until=:l WHERE email=:e");
            return $stmt->execute(['a' => $attempts, 'l' => $lockUntil, 'e' => $email]);
        } else {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET failed_login_attempts=0, lock_until=NULL WHERE email=:e");
            return $stmt->execute(['e' => $email]);
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
        $stmt = $this->db->prepare(
            "INSERT INTO login_logs (user_id, email, ip_address, user_agent, success)
             VALUES (:uid, :email, :ip, :ua, :success)"
        );
        return $stmt->execute(['uid' => $userId, 'email' => $email, 'ip' => $ip, 'ua' => $userAgent, 'success' => $success ? 1 : 0]);
    }
}