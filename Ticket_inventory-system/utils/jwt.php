<?php
// utils/JWT.php

class JWT {
    private static $secret;
    private static $expire;

    public static function init() {
        self::$secret = env('JWT_SECRET');
        self::$expire = env('JWT_EXPIRE', 7) * 24 * 60 * 60;
    }

    public static function encode($payload) {
        self::init();
        
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload['iat'] = time();
        $payload['exp'] = time() + self::$expire;
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", self::$secret, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    public static function decode($token) {
        self::init();
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;
        
        $header = json_decode(self::base64UrlDecode($parts[0]), true);
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        
        if (!$header || !$payload) return false;
        if ($payload['exp'] < time()) return false;
        
        $signature = hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", self::$secret, true);
        $validSignature = self::base64UrlEncode($signature);
        
        if ($parts[2] !== $validSignature) return false;
        
        return $payload;
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}