<?php
// models/Inventory.php
require_once __DIR__ . '/../config/database.php';

class Inventory {
    private $db;
    private $table = 'inventory';

    public function __construct() {
        $this->db = getDB();
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (name, stock, entering, outgoing, quality, threshold) 
                VALUES (:name, :stock, :ent, :out, :qual, :thresh)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'stock' => $data['stock'] ?? 0,
            'ent' => $data['entering'] ?? 0,
            'out' => $data['outgoing'] ?? 0,
            'qual' => $data['quality'] ?? 'Good',
            'thresh' => $data['threshold'] ?? 5
        ]);
        return $this->db->lastInsertId();
    }

    public function updateStockFields($id, $stock, $entering, $outgoing) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET stock = :stock, entering = :ent, outgoing = :out WHERE id = :id");
        return $stmt->execute([
            'stock' => $stock,
            'ent' => $entering,
            'out' => $outgoing,
            'id' => $id
        ]);
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
}
