<?php
// routes/api.php

require_once __DIR__ . '/../controllers/authControllers.php';
require_once __DIR__ . '/../controllers/TicketController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/RequestController.php';
require_once __DIR__ . '/../utils/jwt.php';

class API {
    private $authController;
    private $ticketController;
    private $inventoryController;
    private $requestController;

    public function __construct() {
        $this->authController = new AuthController();
        $this->ticketController = new TicketController();
        $this->inventoryController = new InventoryController();
        $this->requestController = new RequestController();
    }

    private function getAuthenticatedUser() {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $payload = JWT::decode($matches[1]);
            if ($payload) return $payload;
        }
        
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized or missing token']);
        exit;
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $response = ['success' => false, 'message' => 'Not found'];

        try {
            // Public Route
            if (preg_match('/^\/auth\/login\/?$/', $path) && $method === 'POST') {
                $response = $this->authController->login($input);
            } 
            // Protected Routes
            else {
                $user = $this->getAuthenticatedUser();

                // TICKETS
                if (preg_match('/^\/tickets\/?$/', $path)) {
                    if ($method === 'GET') $response = $this->ticketController->index($user);
                    elseif ($method === 'POST') $response = $this->ticketController->store($user, $input);
                } elseif (preg_match('/^\/tickets\/(\d+)\/?$/', $path, $matches)) {
                    if ($method === 'PUT') $response = $this->ticketController->update($user, $matches[1], $input);
                } elseif (preg_match('/^\/tickets\/(\d+)\/resolve\/?$/', $path, $matches) && $method === 'POST') {
                    $response = $this->ticketController->resolve($user, $matches[1]);
                } elseif (preg_match('/^\/tickets\/(\d+)\/refuse\/?$/', $path, $matches) && $method === 'POST') {
                    $response = $this->ticketController->refuse($user, $matches[1]);
                } elseif (preg_match('/^\/tickets\/(\d+)\/assign\/?$/', $path, $matches) && $method === 'POST') {
                    $response = $this->ticketController->assign($user, $matches[1], $input['tech_id']);
                } 
                // INVENTORY
                elseif (preg_match('/^\/inventory\/?$/', $path)) {
                    if ($method === 'GET') $response = $this->inventoryController->index($user);
                    elseif ($method === 'POST') $response = $this->inventoryController->store($user, $input);
                } elseif (preg_match('/^\/inventory\/(\d+)\/stock\/?$/', $path, $matches) && $method === 'POST') {
                    $response = $this->inventoryController->updateStock($user, $matches[1], $input);
                }
                // ITEM REQUESTS
                elseif (preg_match('/^\/requests\/?$/', $path)) {
                    if ($method === 'GET') $response = $this->requestController->index($user);
                    elseif ($method === 'POST') $response = $this->requestController->store($user, $input);
                } elseif (preg_match('/^\/requests\/(\d+)\/validate\/?$/', $path, $matches) && $method === 'POST') {
                    $response = $this->requestController->validate($user, $matches[1]);
                } elseif (preg_match('/^\/requests\/(\d+)\/refuse\/?$/', $path, $matches) && $method === 'POST') {
                    $response = $this->requestController->refuse($user, $matches[1]);
                }
            }
        } catch (Exception $e) {
            http_response_code(500);
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        echo json_encode($response);
    }
}
