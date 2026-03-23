<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock the environment needed for api.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '/auth/login';
$_POST = [];
// simulate input
$json = '{"name":"Alice Employee","email":"alice@enterprise.com"}';
file_put_contents('php://memory', $json);
// Wait, we can't easily mock file_get_contents('php://input'). Better to just require index.php with mocked $_POST ? No, it reads php://input using file_get_contents.

// Let's just run an HTTP request
$ch = curl_init('http://localhost/project-/Ticket_inventory-system/index.php/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['name' => 'Alice Employee', 'email' => 'alice@enterprise.com']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
echo curl_exec($ch);
