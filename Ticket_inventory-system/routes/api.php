<?php
// routes/api.php

require_once __DIR__ . '/../controllers/authControllers.php';
require_once __DIR__ . '/../controllers/TicketController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/RequestController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/SystemConfigController.php';
require_once __DIR__ . '/../utils/jwt.php';

class API {
    private $authController;
    private $ticketController;
    private $inventoryController;
    private $requestController;
    private $userController;
    private $systemConfigController;

    public function __construct() {
        $this->authController      = new AuthController();
        $this->ticketController    = new TicketController();
        $this->inventoryController = new InventoryController();
        $this->requestController   = new RequestController();
        $this->userController      = new UserController();
        $this->systemConfigController = new SystemConfigController();
    }

    private function getAuthenticatedUser() {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
            $payload = JWT::decode($m[1]);
            // Support both 'id' (new) and 'user_id' (legacy) JWT field
            if ($payload) {
                if (!isset($payload['id']) && isset($payload['user_id'])) {
                    $payload['id'] = $payload['user_id'];
                }
                return $payload;
            }
        }
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized or token expired']);
        exit;
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        // Support both PATH_INFO (mod_rewrite) and ?path= fallback
        $path = $_SERVER['PATH_INFO']
            ?? (isset($_GET['path']) ? '/' . ltrim($_GET['path'], '/') : '/');
        $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $response = ['success' => false, 'message' => 'Endpoint not found'];

        try {
            // ── Public ─────────────────────────────────────────────
            if (preg_match('/^\/auth\/login\/?$/', $path) && $method === 'POST') {
                $response = $this->authController->login($input);

            // ── Protected ──────────────────────────────────────────
            } else {
                $user = $this->getAuthenticatedUser();

                // ── USER MANAGEMENT ─────────────────────────────────
                if (preg_match('/^\/users\/?$/', $path)) {
                    if ($method === 'GET')  $response = $this->userController->index($user);
                    if ($method === 'POST') $response = $this->userController->create($user, $input);

                } elseif (preg_match('/^\/users\/(\d+)\/?$/', $path, $m)) {
                    if ($method === 'DELETE') $response = $this->userController->destroy($user, $m[1]);

                } elseif (preg_match('/^\/users\/(\d+)\/role\/?$/', $path, $m) && $method === 'POST') {
                    $response = $this->userController->updateRole($user, $m[1], $input);

                } elseif (preg_match('/^\/users\/(\d+)\/privileges\/?$/', $path, $m)) {
                    if ($method === 'POST')   $response = $this->userController->grantPrivilege($user, $m[1], $input);

                } elseif (preg_match('/^\/users\/(\d+)\/privileges\/([a-z_]+)\/?$/', $path, $m)) {
                    if ($method === 'DELETE') $response = $this->userController->revokePrivilege($user, $m[1], $m[2]);

                } elseif (preg_match('/^\/users\/audit\/?$/', $path) && $method === 'GET') {
                    $response = $this->userController->audit($user);

                // ── TICKETS ─────────────────────────────────────────
                } elseif (preg_match('/^\/tickets\/?$/', $path)) {
                    if ($method === 'GET')  $response = $this->ticketController->index($user);
                    if ($method === 'POST') $response = $this->ticketController->store($user, $input);

                } elseif (preg_match('/^\/tickets\/(\d+)\/?$/', $path, $m) && $method === 'PUT') {
                    $response = $this->ticketController->update($user, $m[1], $input);

                } elseif (preg_match('/^\/tickets\/(\d+)\/resolve\/?$/', $path, $m) && $method === 'POST') {
                    $response = $this->ticketController->resolve($user, $m[1]);

                } elseif (preg_match('/^\/tickets\/(\d+)\/refuse\/?$/', $path, $m) && $method === 'POST') {
                    $response = $this->ticketController->refuse($user, $m[1]);

                } elseif (preg_match('/^\/tickets\/(\d+)\/assign\/?$/', $path, $m) && $method === 'POST') {
                    $response = $this->ticketController->assign($user, $m[1], $input['tech_id'] ?? '');

                } elseif (preg_match('/^\/tickets\/(\d+)\/updates\/?$/', $path, $m)) {
                    if ($method === 'GET')  $response = $this->ticketController->getUpdates($user, $m[1]);
                    if ($method === 'POST') $response = $this->ticketController->addProgressUpdate($user, $m[1], $input);

                } elseif (preg_match('/^\/tickets\/(\d+)\/status\/?$/', $path, $m) && $method === 'POST') {
                    $response = $this->ticketController->setStatus($user, $m[1], $input);

                // ── TECHNICIANS ─────────────────────────────────────
                } elseif (preg_match('/^\/technicians\/?$/', $path) && $method === 'GET') {
                    $response = $this->ticketController->technicians($user);

                // ── INVENTORY ───────────────────────────────────────
                } elseif (preg_match('/^\/inventory\/?$/', $path)) {
                    if ($method === 'GET')  $response = $this->inventoryController->index($user);
                    if ($method === 'POST') $response = $this->inventoryController->store($user, $input);

                } elseif (preg_match('/^\/inventory\/(\d+)\/stock\/?$/', $path, $m) && $method === 'POST') {
                    $response = $this->inventoryController->updateStock($user, $m[1], $input);

                // ── ITEM REQUESTS ────────────────────────────────────
                } elseif (preg_match('/^\/requests\/?$/', $path)) {
                    if ($method === 'GET')  $response = $this->requestController->index($user);
                    if ($method === 'POST') $response = $this->requestController->store($user, $input);

                } elseif (preg_match('/^\/requests\/(\d+)\/validate\/?$/', $path, $m) && $method === 'POST') {
                    $response = $this->requestController->validate($user, $m[1]);

                } elseif (preg_match('/^\/requests\/(\d+)\/refuse\/?$/', $path, $m) && $method === 'POST') {
                    $response = $this->requestController->refuse($user, $m[1]);
                
                // ── SYSTEM CONFIG (ROLES & PRIVS) ───────────────────
                } elseif (preg_match('/^\/roles\/?$/', $path)) {
                    if ($method === 'GET')  $response = $this->systemConfigController->getRoles($user);
                    if ($method === 'POST') $response = $this->systemConfigController->createRole($user, $input);

                } elseif (preg_match('/^\/roles\/([a-z_0-9]+)\/?$/', $path, $m) && $method === 'DELETE') {
                    $response = $this->systemConfigController->deleteRole($user, $m[1]);

                } elseif (preg_match('/^\/available_privileges\/?$/', $path)) {
                    if ($method === 'GET')  $response = $this->systemConfigController->getAvailablePrivileges($user);
                    if ($method === 'POST') $response = $this->systemConfigController->createPrivilege($user, $input);

                } elseif (preg_match('/^\/available_privileges\/([a-z_0-9]+)\/?$/', $path, $m) && $method === 'DELETE') {
                    $response = $this->systemConfigController->deletePrivilege($user, $m[1]);
                }
            }
        } catch (Exception $e) {
            http_response_code(500);
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        echo json_encode($response);
    }
}
