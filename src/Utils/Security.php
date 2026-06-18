<?php
namespace App\Utils;

class Security {
    private static string $csrfSessionKey = '_csrf_token';

    // ─── CSRF ────────────────────────────────────────────────────────────────

    public static function generateCsrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION[self::$csrfSessionKey])) {
            $_SESSION[self::$csrfSessionKey] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::$csrfSessionKey];
    }

    public static function validateCsrfToken(string $token): bool {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $stored = $_SESSION[self::$csrfSessionKey] ?? '';
        return hash_equals($stored, $token);
    }

    public static function csrfField(): string {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verifyCsrfOrFail(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf_token'] ?? '';
            if (!self::validateCsrfToken($token)) {
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'CSRF token mismatch.']));
            }
        }
    }

    // ─── XSS / Output Encoding ───────────────────────────────────────────────

    public static function e(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function sanitizeString(string $value): string {
        return filter_var(trim($value), FILTER_SANITIZE_SPECIAL_CHARS);
    }

    public static function sanitizeInt(mixed $value): int {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function sanitizeEmail(string $value): string|false {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }

    // ─── Password ────────────────────────────────────────────────────────────

    public static function hashPassword(string $password): string {
        // PHP 8.4 compatible — try Argon2id with fallback to bcrypt
        $hash = @password_hash($password, PASSWORD_ARGON2ID);
        if ($hash === false || $hash === null) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
        }
        return $hash;
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    // ─── UUID ─────────────────────────────────────────────────────────────────

    public static function uuid4(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // ─── NIK Encryption / Hash ───────────────────────────────────────────────

    public static function encryptNik(string $nik): string {
        $keyHex = \App\Config\Env::get('APP_ENCRYPTION_KEY');
        $key = hex2bin($keyHex);
        $ivLength = openssl_cipher_iv_length('aes-256-gcm');
        $iv = random_bytes($ivLength);
        $encrypted = openssl_encrypt($nik, 'aes-256-gcm', $key, 0, $iv, $tag);
        return base64_encode($iv . $tag . $encrypted);
    }

    public static function decryptNik(string $ciphertext): string {
        $keyHex = \App\Config\Env::get('APP_ENCRYPTION_KEY');
        $key = hex2bin($keyHex);
        $data = base64_decode($ciphertext);
        $ivLength = openssl_cipher_iv_length('aes-256-gcm');
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $encrypted = substr($data, $ivLength + 16);
        return openssl_decrypt($encrypted, 'aes-256-gcm', $key, 0, $iv, $tag);
    }

    public static function hashNik(string $nik): string {
        $keyHex = \App\Config\Env::get('APP_HMAC_KEY');
        return hash_hmac('sha256', $nik, hex2bin($keyHex));
    }

    // ─── IP & User Agent ─────────────────────────────────────────────────────

    public static function getClientIp(): string {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                return explode(',', $_SERVER[$h])[0];
            }
        }
        return '0.0.0.0';
    }
}
