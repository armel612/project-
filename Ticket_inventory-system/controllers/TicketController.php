<?php
// controllers/TicketController.php
require_once __DIR__ . '/../models/Ticket.php';

class TicketController {
    private $ticketModel;

    public function __construct() {
        $this->ticketModel = new Ticket();
    }

    public function index($user) {
        if ($user['role'] === 'support_agent') {
            $tickets = $this->ticketModel->getAllWithDetails();
        } else {
            $tickets = $this->ticketModel->getByRequester($user['id']);
        }
        return ['success' => true, 'tickets' => $tickets];
    }

    public function store($user, $data) {
        if (empty($data['title'])) {
            return ['success' => false, 'message' => 'Title is required'];
        }
        $id = $this->ticketModel->create($user['id'], $data);
        return ['success' => true, 'message' => 'Ticket submitted'];
    }

    public function update($user, $id, $data) {
        if ($user['role'] !== 'employee') return ['success' => false, 'message' => 'Unauthorized'];
        if ($this->ticketModel->update($id, $user['id'], $data)) {
            return ['success' => true, 'message' => 'Ticket updated'];
        }
        return ['success' => false, 'message' => 'Update failed or ticket not open'];
    }

    public function resolve($user, $id) {
        if ($user['role'] !== 'support_agent') return ['success' => false, 'message' => 'Unauthorized'];
        $this->ticketModel->updateStatus($id, 'Resolved');
        return ['success' => true, 'message' => 'Ticket resolved'];
    }

    public function refuse($user, $id) {
        if ($user['role'] !== 'support_agent') return ['success' => false, 'message' => 'Unauthorized'];
        $this->ticketModel->updateStatus($id, 'Refused');
        return ['success' => true, 'message' => 'Ticket refused'];
    }

    public function assign($user, $id, $tech_id) {
        if ($user['role'] !== 'support_agent') return ['success' => false, 'message' => 'Unauthorized'];
        $this->ticketModel->assignTechnician($id, $tech_id);
        return ['success' => true, 'message' => 'Technician assigned'];
    }
}
