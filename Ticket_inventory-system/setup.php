<?php
// setup.php

require_once __DIR__ . '/config/config.php';

echo "Starting Multi-Role Database Setup...\n";

$host = env('DB_HOST', '127.0.0.1');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$dbName = env('DB_NAME', 'ticket_inventory');

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    // Drop existing tables for a clean slate
    $pdo->exec("DROP TABLE IF EXISTS login_logs, item_requests, tickets, inventory, users");

    $queries = [
        "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('employee', 'inventory_manager', 'support_agent') DEFAULT 'employee',
            failed_login_attempts INT DEFAULT 0,
            lock_until DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE login_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            email VARCHAR(100),
            ip_address VARCHAR(45),
            user_agent TEXT,
            success BOOLEAN,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        "CREATE TABLE tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            requester_id INT NULL,
            assigned_to INT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            priority ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
            status ENUM('Open', 'Resolved', 'Refused', 'Closed') DEFAULT 'Open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
        )",
        "CREATE TABLE inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            stock INT DEFAULT 0,
            entering INT DEFAULT 0,
            outgoing INT DEFAULT 0,
            quality VARCHAR(50) DEFAULT 'Good',
            threshold INT DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE item_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NULL,
            item_id INT NULL,
            quantity INT DEFAULT 1,
            status ENUM('Pending', 'Validated', 'Refused') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (item_id) REFERENCES inventory(id) ON DELETE CASCADE
        )"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }

    // Seed Data
    $pwd = password_hash('password123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (name, email, password, role) VALUES 
        ('Alice Employee', 'alice@enterprise.com', '$pwd', 'employee'),
        ('Bob Manager', 'bob@enterprise.com', '$pwd', 'inventory_manager'),
        ('Charlie Support', 'charlie@enterprise.com', '$pwd', 'support_agent')");

    $pdo->exec("INSERT INTO inventory (name, stock, entering, outgoing, quality, threshold) VALUES 
        ('Laptops', 10, 50, 40, 'Excellent', 5),
        ('Monitors', 15, 20, 5, 'Good', 10)");

    // Get seeded IDs
    $employeeId = $pdo->query("SELECT id FROM users WHERE email='alice@enterprise.com'")->fetchColumn();
    $agentId = $pdo->query("SELECT id FROM users WHERE email='charlie@enterprise.com'")->fetchColumn();
    $laptopId = $pdo->query("SELECT id FROM inventory WHERE name='Laptops'")->fetchColumn();

    $pdo->exec("INSERT INTO tickets (requester_id, assigned_to, title, description, priority, status) VALUES 
        ($employeeId, $agentId, 'Need software install', 'Please install Photoshop.', 'Medium', 'Open')");

    $pdo->exec("INSERT INTO item_requests (employee_id, item_id, quantity, status) VALUES 
        ($employeeId, $laptopId, 1, 'Pending')");

    echo "Database setup completely updated for multi-role support!\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
