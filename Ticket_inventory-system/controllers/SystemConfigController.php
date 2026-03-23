<?php
// controllers/SystemConfigController.php
require_once __DIR__ . '/../config/database.php';

class SystemConfigController {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    // ─── Roles ───────────────────────────────────────────────────
    public function getRoles($caller) {
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY name ASC");
        return ['success' => true, 'roles' => $stmt->fetchAll()];
    }

    public function createRole($caller, $data) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];
        
        if (empty($data['name']))
            return ['success' => false, 'message' => 'Role name required'];

        $stmt = $this->db->prepare("INSERT INTO roles (name, description) VALUES (:name, :desc)");
        try {
            $stmt->execute(['name' => $data['name'], 'desc' => $data['description'] ?? '']);
            return ['success' => true, 'message' => "Role '{$data['name']}' created"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Role already exists or DB error'];
        }
    }

    public function deleteRole($caller, $name) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];
        
        $protected = ['system_admin', 'employee', 'it_technician'];
        if (in_array($name, $protected))
            return ['success' => false, 'message' => 'Cannot delete core system roles'];

        $stmt = $this->db->prepare("DELETE FROM roles WHERE name = :name");
        $stmt->execute(['name' => $name]);
        return ['success' => true, 'message' => "Role '$name' removed"];
    }

    // ─── Privileges ──────────────────────────────────────────────
    public function getAvailablePrivileges($caller) {
        $stmt = $this->db->query("SELECT * FROM available_privileges ORDER BY name ASC");
        return ['success' => true, 'privileges' => $stmt->fetchAll()];
    }

    public function createPrivilege($caller, $data) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];
        
        if (empty($data['name']))
            return ['success' => false, 'message' => 'Privilege name required'];

        $stmt = $this->db->prepare("INSERT INTO available_privileges (name, description) VALUES (:name, :desc)");
        try {
            $stmt->execute(['name' => $data['name'], 'desc' => $data['description'] ?? '']);
            return ['success' => true, 'message' => "Privilege '{$data['name']}' created"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Privilege already exists or DB error'];
        }
    }

    public function deletePrivilege($caller, $name) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];

        if ($name === 'can_add_user')
            return ['success' => false, 'message' => 'Cannot delete core system privileges'];

        $stmt = $this->db->prepare("DELETE FROM available_privileges WHERE name = :name");
        $stmt->execute(['name' => $name]);
        return ['success' => true, 'message' => "Privilege '$name' removed"];
    }
}
