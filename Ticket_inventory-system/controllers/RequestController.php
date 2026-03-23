<?php
// controllers/RequestController.php
require_once __DIR__ . '/../models/ItemRequest.php';
require_once __DIR__ . '/../models/Inventory.php';

class RequestController {
    private $requestModel;
    private $inventoryModel;

    public function __construct() {
        $this->requestModel = new ItemRequest();
        $this->inventoryModel = new Inventory();
    }

    public function index($user) {
        if ($user['role'] === 'employee') {
            return ['success' => true, 'requests' => $this->requestModel->getByEmployee($user['id'])];
        } else if ($user['role'] === 'inventory_manager') {
            return ['success' => true, 'requests' => $this->requestModel->getAllWithDetails()];
        }
        return ['success' => false, 'message' => 'Unauthorized'];
    }

    public function store($user, $data) {
        if ($user['role'] !== 'employee') return ['success' => false, 'message' => 'Only employees can request items'];
        if (empty($data['item_id']) || empty($data['quantity'])) {
            return ['success' => false, 'message' => 'Item ID and quantity required'];
        }
        $id = $this->requestModel->create($user['id'], $data['item_id'], $data['quantity']);
        return ['success' => true, 'message' => 'Request submitted successfully'];
    }

    public function validate($user, $id) {
        if ($user['role'] !== 'inventory_manager') return ['success' => false, 'message' => 'Unauthorized'];
        
        $req = $this->requestModel->getById($id);
        if (!$req || $req['status'] !== 'Pending') return ['success' => false, 'message' => 'Invalid request'];

        // Deduct from inventory
        $item = $this->inventoryModel->getById($req['item_id']);
        if ($item && $item['stock'] >= $req['quantity']) {
            $this->inventoryModel->updateStockFields($req['item_id'], $item['stock'] - $req['quantity'], $item['entering'], $item['outgoing'] + $req['quantity']);
            $this->requestModel->updateStatus($id, 'Validated');
            return ['success' => true, 'message' => 'Request validated and stock updated'];
        }
        
        return ['success' => false, 'message' => 'Insufficient stock'];
    }

    public function refuse($user, $id) {
        if ($user['role'] !== 'inventory_manager') return ['success' => false, 'message' => 'Unauthorized'];
        $this->requestModel->updateStatus($id, 'Refused');
        return ['success' => true, 'message' => 'Request refused'];
    }
}
