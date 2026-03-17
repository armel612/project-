<?php
// middleware/AuthMiddleware.php
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    public static function verify($requiredRoles = null) {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            http_response_code(401);
            echo json_encode(['error' => 'Authorization token required']);
            exit;
        }

        $token = substr($authHeader, 7);
        $payload = JWT::decode($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }

        // Check if token is blacklisted
        $db = getDB();
        $tokenHash = hash('sha256', $token);
        $stmt = $db->prepare("SELECT id FROM token_blacklist WHERE token_hash = :hash");
        $stmt->execute(['hash' => $tokenHash]);
        if ($stmt->fetch()) {
            http_response_code(401);
            echo json_encode(['error' => 'Token has been revoked']);
            exit;
        }

        $user = (new User())->findById($payload['user_id']);
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        if ($requiredRoles && !in_array($user['role'], $requiredRoles)) {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions']);
            exit;
        }

        return $user;
    }
}

function getallheaders() {
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}