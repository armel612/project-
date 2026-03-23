<?php
// controllers/UserController.php
require_once __DIR__ . '/../models/User.php';

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    private function isValidRole($role) {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM roles WHERE name = :r");
        $stmt->execute(['r' => $role]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ─── Only system_admin may list users ───────────────────────
    public function index($caller) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];
        return ['success' => true, 'users' => $this->userModel->getAllUsers()];
    }

    // ─── Create user: admin OR someone with can_add_user priv ───
    public function create($caller, $data) {
        $isAdmin = $caller['role'] === 'system_admin';
        $hasPriv  = $this->userModel->hasPrivilege($caller['id'], 'can_add_user');

        if (!$isAdmin && !$hasPriv)
            return ['success' => false, 'message' => 'Unauthorized: only admins or privileged users can add accounts'];

        if (empty($data['name']) || empty($data['email']))
            return ['success' => false, 'message' => 'Name and email are required'];

        // Non-admins cannot create system_admin accounts
        $role = $data['role'] ?? 'employee';
        if (!$isAdmin && $role === 'system_admin')
            return ['success' => false, 'message' => 'You cannot create System Admin accounts'];

        if (!$this->isValidRole($role))
            return ['success' => false, 'message' => 'Invalid role designated. If you just created this role, please ensure it exists in System Config.'];

        try {
            $this->userModel->addUser([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => $data['password'] ?? 'password123',
                'role'      => $role,
                'phone'     => $data['phone'] ?? null,
                'specialty' => $data['specialty'] ?? null
            ]);
            return ['success' => true, 'message' => "User {$data['name']} created as $role"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Email already exists or DB error'];
        }
    }

    // ─── Delete user: SYSTEM ADMIN ONLY, always ─────────────────
    public function destroy($caller, $targetId) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized: only the System Admin can remove users'];

        if ((int)$caller['id'] === (int)$targetId)
            return ['success' => false, 'message' => 'You cannot remove your own account'];

        $this->userModel->deleteUser($targetId);
        return ['success' => true, 'message' => 'User removed from the system'];
    }

    // ─── Update role: system_admin only ─────────────────────────
    public function updateRole($caller, $targetId, $data) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized: only System Admin can change roles'];

        $role = $data['role'] ?? '';
        if (!$this->isValidRole($role))
            return ['success' => false, 'message' => 'Invalid role specified'];

        $this->userModel->updateUserRole($targetId, $role);
        return ['success' => true, 'message' => "Role updated to $role"];
    }

    // ─── Grant privilege: system_admin only ─────────────────────
    public function grantPrivilege($caller, $targetId, $data) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];

        $priv = $data['privilege'] ?? '';
        if ($priv !== 'can_add_user')
            return ['success' => false, 'message' => 'Invalid or unknown privilege'];

        $this->userModel->grantPrivilege($targetId, $priv, $caller['id']);
        return ['success' => true, 'message' => "Privilege '$priv' granted"];
    }

    // ─── Revoke privilege: system_admin only ─────────────────────
    public function revokePrivilege($caller, $targetId, $priv) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];

        $this->userModel->revokePrivilege($targetId, $priv);
        return ['success' => true, 'message' => "Privilege '$priv' revoked"];
    }

    // ─── Audit stats ─────────────────────────────────────────────
    public function audit($caller) {
        if ($caller['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];

        $users = $this->userModel->getAllUsers();
        $byRole = [];
        $privileged = [];
        foreach ($users as $u) {
            $byRole[$u['role']] = ($byRole[$u['role']] ?? 0) + 1;
            if (!empty($u['privileges'])) {
                $privileged[] = ['name' => $u['name'], 'email' => $u['email'], 'privileges' => $u['privileges']];
            }
        }
        return ['success' => true, 'by_role' => $byRole, 'privileged_users' => $privileged, 'total' => count($users)];
    }
}
