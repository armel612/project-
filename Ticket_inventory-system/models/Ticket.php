<?php
// models/Ticket.php
require_once __DIR__ . '/../config/database.php';

class Ticket {
    private $db;
    private $table = 'tickets';

    public function __construct() {
        $this->db = getDB();
    }

    public function getAllWithDetails() {
        $sql = "SELECT t.*, u.name as requester_name, a.name as tech_name 
                FROM {$this->table} t 
                LEFT JOIN users u ON t.requester_id = u.id 
                LEFT JOIN users a ON t.assigned_to = a.id 
                ORDER BY t.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getByRequester($requester_id) {
        $sql = "SELECT t.*, a.name as tech_name 
                FROM {$this->table} t 
                LEFT JOIN users a ON t.assigned_to = a.id 
                WHERE t.requester_id = :req_id ORDER BY t.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['req_id' => $requester_id]);
        return $stmt->fetchAll();
    }

    public function create($requester_id, $data) {
        $sql = "INSERT INTO {$this->table} (requester_id, title, description, priority, status) 
                VALUES (:req, :title, :desc, :pri, 'Open')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'req' => $requester_id,
            'title' => $data['title'],
            'desc' => $data['description'] ?? '',
            'pri' => $data['priority'] ?? 'Low'
        ]);
        return $this->db->lastInsertId();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function assignTechnician($id, $tech_id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET assigned_to = :tech WHERE id = :id");
        return $stmt->execute(['tech' => $tech_id, 'id' => $id]);
    }

    public function update($id, $requester_id, $data) {
        // Only allow updating if status is not resolved/closed
        $sql = "UPDATE {$this->table} SET title = :title, description = :desc, priority = :pri 
                WHERE id = :id AND requester_id = :req AND status = 'Open'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'title' => $data['title'],
            'desc' => $data['description'],
            'pri' => $data['priority'],
            'id' => $id,
            'req' => $requester_id
        ]);
    }
}
