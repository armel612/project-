<?php
// test_api2.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PATH_INFO'] = '/auth/login';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'CLI';

// Mock php://input
class MockStream {
    public function stream_open($path, $mode, $options, &$opened_path) { return true; }
    public function stream_read($count) {
        $data = $this->data;
        $this->data = '';
        return $data;
    }
    public function stream_eof() { return true; }
    public function stream_stat() { return []; }
    private $data = '{"name":"Alice Employee","email":"alice@enterprise.com"}';
}
// Actually, it's easier to just $_POST inside index.php if json_decode fails. Wait, api.php uses ?? $_POST.
$_POST = ['name' => 'Alice Employee', 'email' => 'alice@enterprise.com'];

try {
    require 'index.php';
} catch (Throwable $e) {
    echo "ERROR CAUGHT: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
}
