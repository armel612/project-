<?php
// setup.php

require_once __DIR__ . '/config/config.php';

$host = env('DB_HOST', '127.0.0.1');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$dbName = env('DB_NAME', 'ticket_inventory');

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    // Drop tables in dependency order
    $pdo->exec("DROP TABLE IF EXISTS login_logs, item_requests, ticket_updates, tickets, privileges, inventory, it_technicians, available_privileges, roles, users");

    $queries = [
        "CREATE TABLE roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE available_privileges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'employee',
            phone VARCHAR(30) DEFAULT NULL,
            specialty VARCHAR(100) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            failed_login_attempts INT DEFAULT 0,
            lock_until DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE it_technicians (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            specialty VARCHAR(100) DEFAULT NULL,
            phone VARCHAR(30) DEFAULT NULL,
            email VARCHAR(100) DEFAULT NULL,
            is_available TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE privileges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            privilege VARCHAR(50) NOT NULL,
            granted_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_priv (user_id, privilege),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE CASCADE
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
            technician_id INT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            priority ENUM('Low','Medium','High') DEFAULT 'Low',
            status ENUM('Open','In Progress','Resolved','Refused','Closed') DEFAULT 'Open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        "CREATE TABLE ticket_updates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            update_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
            status ENUM('Pending','Validated','Refused') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (item_id) REFERENCES inventory(id) ON DELETE CASCADE
        )"
    ];

    foreach ($queries as $sql) {
        $pdo->exec($sql);
    }

    // Seed Roles
    $pdo->exec("INSERT INTO roles (name, description) VALUES 
        ('employee', 'Standard staff member'),
        ('inventory_manager', 'Inventory & Stock control'),
        ('support_agent', 'Ticket triage and helpdesk'),
        ('system_admin', 'Full system control'),
        ('it_technician', 'Technical resolution staff')");

    // Seed Available Privileges
    $pdo->exec("INSERT INTO available_privileges (name, description) VALUES 
        ('can_add_user', 'Allows provisioning new user accounts')");

    // Seed Users
    $pwd = password_hash('password123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO users (name, email, password, role, specialty, phone) VALUES 
        ('System Administrator', 'admin@enterprise.com', '$pwd', 'system_admin', NULL, NULL),
        ('Alice Employee',       'alice@enterprise.com', '$pwd', 'employee', NULL, NULL),
        ('Bob Manager',          'bob@enterprise.com',   '$pwd', 'inventory_manager', NULL, NULL),
        ('Charlie Support',      'charlie@enterprise.com','$pwd', 'support_agent', NULL, NULL),
        ('Jean-Paul Nguyen',     'tech@enterprise.com',  '$pwd', 'it_technician', 'Network & Infrastructure', '+1-555-0101'),
        ('Maria Santos',         'maria@enterprise.com', '$pwd', 'it_technician', 'Hardware & Device Repair', '+1-555-0102')");

    // Seed Inventory (some items below threshold for alert demo)
    $pdo->exec("INSERT INTO inventory (name, stock, entering, outgoing, quality, threshold) VALUES 
        ('Laptops',     10,  50, 40, 'Excellent', 5),
        ('HDMI Cables',  2,   5,  3, 'Good',      5),
        ('Monitors',     3,  20, 17, 'Good',      5),
        ('USB Hubs',    12,  15,  3, 'Good',      5),
        ('Keyboards',    4,  10,  6, 'Good',      5)");

    // Seed sample ticket (assigned to Jean-Paul)
    $empId  = $pdo->query("SELECT id FROM users WHERE email='alice@enterprise.com'")->fetchColumn();
    $techId = $pdo->query("SELECT id FROM users WHERE email='tech@enterprise.com'")->fetchColumn();
    
    $pdo->exec("INSERT INTO tickets (requester_id, technician_id, title, description, priority, status) VALUES 
        ($empId, $techId, 'Need Adobe suite installed', 'Please install on my workstation.', 'Medium', 'In Progress')");
    $ticketId = $pdo->lastInsertId();

    // Seed progress update
    $pdo->exec("INSERT INTO ticket_updates (ticket_id, user_id, update_text) VALUES 
        ($ticketId, $techId, 'I am downloading the installer now. Will update soon.'),
        ($ticketId, $empId, 'Thank you! Please inform me when done.')");

    // Seed request
    $laptopId = $pdo->query("SELECT id FROM inventory WHERE name='Laptops'")->fetchColumn();
    $pdo->exec("INSERT INTO item_requests (employee_id, item_id, quantity, status) VALUES ($empId, $laptopId, 1, 'Pending')");

    echo json_encode(['success' => true, 'message' => 'Database v3 RBAC setup complete!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
