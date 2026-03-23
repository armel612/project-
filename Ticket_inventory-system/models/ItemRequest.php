<?php
// models/ItemRequest.php
require_once __DIR__ . '/../config/database.php';

class ItemRequest {
    private $db;
    private $table = 'item_requests';

    public function __construct() {
        $this->db = getDB();
    }

    public function getAllWithDetails() {
        $sql = "SELECT r.*, u.name as employee_name, i.name as item_name 
                FROM {$this->table} r
                LEFT JOIN users u ON r.employee_id = u.id
                LEFT JOIN inventory i ON r.item_id = i.id
                ORDER BY r.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getByEmployee($employeeId) {
        $sql = "SELECT r.*, i.name as item_name 
                FROM {$this->table} r
                LEFT JOIN inventory i ON r.item_id = i.id
                WHERE r.employee_id = :emp_id ORDER BY r.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['emp_id' => $employeeId]);
        return $stmt->fetchAll();
    }

    public function create($employeeId, $itemId, $quantity) {
        $sql = "INSERT INTO {$this->table} (employee_id, item_id, quantity) VALUES (:emp, :item, :qty)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['emp' => $employeeId, 'item' => $itemId, 'qty' => $quantity]);
        return $this->db->lastInsertId();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
}
