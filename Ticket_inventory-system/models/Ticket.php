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
        $sql = "SELECT t.id, t.requester_id, t.technician_id, t.title, t.description,
                       t.priority, t.status, t.created_at,
                       u.name  AS requester_name,
                       tech.id AS tech_id,
                       tech.name AS tech_name,
                       tech.specialty AS tech_specialty,
                       tech.phone AS tech_phone
                FROM {$this->table} t
                LEFT JOIN users u     ON t.requester_id = u.id
                LEFT JOIN users tech  ON t.technician_id = tech.id
                ORDER BY t.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getByRequester($requester_id) {
        $sql = "SELECT t.id, t.title, t.description, t.priority, t.status, t.created_at,
                       tech.name AS tech_name, tech.specialty AS tech_specialty
                FROM {$this->table} t
                LEFT JOIN users tech ON t.technician_id = tech.id
                WHERE t.requester_id = :req_id
                ORDER BY t.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['req_id' => $requester_id]);
        return $stmt->fetchAll();
    }

    public function create($requester_id, $data) {
        $sql = "INSERT INTO {$this->table} (requester_id, title, description, priority, status)
                VALUES (:req, :title, :desc, :pri, 'Open')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'req'   => $requester_id,
            'title' => $data['title'],
            'desc'  => $data['description'] ?? '',
            'pri'   => $data['priority'] ?? 'Low'
        ]);
        return $this->db->lastInsertId();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status=:status WHERE id=:id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    // Assign or re-assign a technician (who is a User)
    public function assignTechnician($id, $tech_id) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET technician_id=:tech, status='In Progress' WHERE id=:id");
        return $stmt->execute(['tech' => $tech_id ?: null, 'id' => $id]);
    }

    public function addUpdate($ticketId, $userId, $text) {
        $stmt = $this->db->prepare("INSERT INTO ticket_updates (ticket_id, user_id, update_text) VALUES (:tid, :uid, :txt)");
        return $stmt->execute(['tid' => $ticketId, 'uid' => $userId, 'txt' => $text]);
    }

    public function getUpdates($ticketId) {
        $sql = "SELECT tu.*, u.name as user_name, u.role as user_role 
                FROM ticket_updates tu 
                JOIN users u ON tu.user_id = u.id 
                WHERE tu.ticket_id = :tid 
                ORDER BY tu.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['tid' => $ticketId]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function update($id, $requester_id, $data) {
        $sql = "UPDATE {$this->table} SET title=:title, description=:desc, priority=:pri
                WHERE id=:id AND requester_id=:req AND status='Open'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'title' => $data['title'],
            'desc'  => $data['description'],
            'pri'   => $data['priority'],
            'id'    => $id,
            'req'   => $requester_id
        ]);
    }
}
