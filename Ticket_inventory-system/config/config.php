<?php
// config/config.php

class Config {
    private static $instance = null;
    private $config = [];

    private function __construct() {
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                list($name, $value) = explode('=', $line, 2);
                $this->config[trim($name)] = trim($value);
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
}

function env($key, $default = null) {
    return Config::getInstance()->get($key, $default);
}