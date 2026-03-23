<?php
// controllers/TicketController.php
require_once __DIR__ . '/../models/Ticket.php';

class TicketController {
    private $ticketModel;

    public function __construct() {
        $this->ticketModel = new Ticket();
    }

    public function index($user) {
        if ($user['role'] === 'it_technician') {
            // Technicians only see tickets assigned to them
            $tickets = $this->ticketModel->getAllWithDetails();
            $tickets = array_filter($tickets, function($t) use ($user) {
                return $t['technician_id'] == $user['id'];
            });
            return ['success' => true, 'tickets' => array_values($tickets)];
        } elseif ($user['role'] === 'support_agent' || $user['role'] === 'system_admin') {
            $tickets = $this->ticketModel->getAllWithDetails();
        } else {
            $tickets = $this->ticketModel->getByRequester($user['id']);
        }
        return ['success' => true, 'tickets' => $tickets];
    }

    public function store($user, $data) {
        if (empty($data['title']))
            return ['success' => false, 'message' => 'Title is required'];
        $this->ticketModel->create($user['id'], $data);
        return ['success' => true, 'message' => 'Ticket submitted'];
    }

    public function update($user, $id, $data) {
        if ($user['role'] !== 'employee')
            return ['success' => false, 'message' => 'Unauthorized'];
        if ($this->ticketModel->update($id, $user['id'], $data))
            return ['success' => true, 'message' => 'Ticket updated'];
        return ['success' => false, 'message' => 'Update failed or ticket is not open'];
    }

    public function resolve($user, $id) {
        if ($user['role'] !== 'support_agent' && $user['role'] !== 'it_technician')
            return ['success' => false, 'message' => 'Unauthorized'];
        
        $ticket = $this->ticketModel->getById($id);
        if ($this->isTerminalStatus($ticket['status']))
            return ['success' => false, 'message' => 'This ticket is finalized and cannot be modified.'];

        $this->ticketModel->updateStatus($id, 'Resolved');
        return ['success' => true, 'message' => 'Ticket marked as Resolved'];
    }

    public function refuse($user, $id) {
        if ($user['role'] !== 'support_agent')
            return ['success' => false, 'message' => 'Unauthorized'];

        $ticket = $this->ticketModel->getById($id);
        if ($this->isTerminalStatus($ticket['status']))
            return ['success' => false, 'message' => 'This ticket is finalized and cannot be modified.'];

        $this->ticketModel->updateStatus($id, 'Refused');
        return ['success' => true, 'message' => 'Ticket marked as Refused'];
    }

    public function assign($user, $id, $tech_id) {
        if ($user['role'] !== 'support_agent')
            return ['success' => false, 'message' => 'Unauthorized'];
        if (empty($tech_id))
            return ['success' => false, 'message' => 'Please select a technician'];

        $ticket = $this->ticketModel->getById($id);
        if ($this->isTerminalStatus($ticket['status']))
            return ['success' => false, 'message' => 'Cannot re-assign a finalized ticket.'];

        $this->ticketModel->assignTechnician($id, (int)$tech_id);
        return ['success' => true, 'message' => 'Technician assigned and status set to In Progress'];
    }

    // New: Progress tracking
    public function addProgressUpdate($user, $id, $data) {
        if (empty($data['update_text'])) return ['success' => false, 'message' => 'Update text is required'];
        
        $ticket = $this->ticketModel->getById($id);
        if (!$ticket) return ['success' => false, 'message' => 'Ticket not found'];

        if ($this->isTerminalStatus($ticket['status']))
            return ['success' => false, 'message' => 'This ticket is finalized. No further updates allowed.'];

        // Only requester or assigned technician can add progress updates
        $isRequester = ($ticket['requester_id'] == $user['id']);
        $isAssignedTech = ($ticket['technician_id'] == $user['id']);
        $isAdmin = ($user['role'] === 'system_admin' || $user['role'] === 'support_agent');

        if (!$isRequester && !$isAssignedTech && !$isAdmin) {
            return ['success' => false, 'message' => 'Unauthorized to update this ticket'];
        }

        $this->ticketModel->addUpdate($id, $user['id'], $data['update_text']);
        
        // If tech updates, auto-set to In Progress if it was Open
        if ($isAssignedTech && $ticket['status'] === 'Open') {
            $this->ticketModel->updateStatus($id, 'In Progress');
        }

        return ['success' => true, 'message' => 'Progress update added'];
    }

    public function getUpdates($user, $id) {
        // Anyone associated with the ticket can see the updates
        $updates = $this->ticketModel->getUpdates($id);
        return ['success' => true, 'updates' => $updates];
    }

    public function setStatus($user, $id, $data) {
        if ($user['role'] !== 'it_technician' && $user['role'] !== 'support_agent')
            return ['success' => false, 'message' => 'Unauthorized'];
        
        if (empty($data['status'])) return ['success' => false, 'message' => 'Status required'];
        
        $ticket = $this->ticketModel->getById($id);
        if ($this->isTerminalStatus($ticket['status']))
            return ['success' => false, 'message' => 'Cannot change status of a finalized ticket.'];

        $this->ticketModel->updateStatus($id, $data['status']);
        return ['success' => true, 'message' => 'Ticket status updated to ' . $data['status']];
    }

    private function isTerminalStatus($status) {
        return in_array($status, ['Resolved', 'Refused', 'Closed']);
    }

    public function technicians($user) {
        // Any support agent or admin can list technicians
        if ($user['role'] !== 'support_agent' && $user['role'] !== 'system_admin')
            return ['success' => false, 'message' => 'Unauthorized'];

        $userModel = new User();
        return ['success' => true, 'technicians' => $userModel->findTechnicians()];
    }
}
