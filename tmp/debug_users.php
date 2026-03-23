<?php
require_once __DIR__ . '/../Ticket_inventory-system/models/User.php';
require_once __DIR__ . '/../Ticket_inventory-system/config/database.php';

try {
    $userModel = new User();
    $users = $userModel->getAllUsers();
    echo "COUNT: " . count($users) . "\n";
    foreach($users as $u) {
        echo "ID: {$u['id']} | NAME: {$u['name']} | ROLE: {$u['role']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
