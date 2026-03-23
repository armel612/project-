<?php
// controllers/InventoryController.php
require_once __DIR__ . '/../models/Inventory.php';

class InventoryController {
    private $inventoryModel;

    public function __construct() {
        $this->inventoryModel = new Inventory();
    }

    public function index($user) {
        // Both employee and manager can see items, though the frontend will display them differently
        $inventory = $this->inventoryModel->getAll();
        return ['success' => true, 'inventory' => $inventory];
    }

    public function store($user, $data) {
        if ($user['role'] !== 'inventory_manager') return ['success' => false, 'message' => 'Unauthorized'];
        if (empty($data['name'])) {
            return ['success' => false, 'message' => 'Name is required'];
        }
        $id = $this->inventoryModel->create($data);
        return ['success' => true, 'message' => 'Item created'];
    }

    public function updateStock($user, $id, $data) {
        if ($user['role'] !== 'inventory_manager') return ['success' => false, 'message' => 'Unauthorized'];
        $this->inventoryModel->updateStockFields($id, $data['stock'] ?? 0, $data['entering'] ?? 0, $data['outgoing'] ?? 0);
        return ['success' => true, 'message' => 'Stock updated'];
    }
}
