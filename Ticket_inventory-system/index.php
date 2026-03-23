<?php
// index.php

// Enable error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . env('FRONTEND_URL', '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Load routes
require_once __DIR__ . '/routes/api.php';

// Handle request
try {
    $api = new API();
    $api->handleRequest();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Backend Error: ' . $e->getMessage()]);
}