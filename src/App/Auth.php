<?php
namespace App\App;

use App\Config\Env;
use App\Database\Connection;
use App\Utils\Security;
use App\Logger;
use PDO;

class Auth {
    private static string $sessionKey = '_auth_user';

    // ─── Login ───────────────────────────────────────────────────────────────

    public static function attempt(string $email, string $password): array|false {
        $db = Connection::getConnection();
        $ip = Security::getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // If email input is a 16-digit numeric string (NIK), resolve it to user email
        if (ctype_digit($email) && strlen($email) === 16) {
            $stmtNik = $db->prepare("SELECT u.email FROM patients p JOIN users u ON p.user_id = u.id WHERE p.nik = ? LIMIT 1");
            $stmtNik->execute([$email]);
            $resolvedEmail = $stmtNik->fetchColumn();
            if ($resolvedEmail) {
                $email = $resolvedEmail;
            }
        }

        // Check account lockout
        $stmt = $db->prepare("SELECT id, locked_until FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            self::logAttempt($email, $ip, $ua, false);
            return false;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && Security::verifyPassword($password, $user['password_hash'])) {
            // Reset failed attempts on success
            $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$user['id']]);
            self::logAttempt($email, $ip, $ua, true);
            self::createSession($user);
            Logger::info("Login success: {$email} [{$ip}]");
            return $user;
        }

        // Increment failed attempts and maybe lock
        if ($user) {
            $attempts = $user['failed_login_attempts'] + 1;
            $lockedUntil = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 900) : null; // 15 min lockout
            $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?")
               ->execute([$attempts, $lockedUntil, $user['id']]);
        }

        self::logAttempt($email, $ip, $ua, false);
        Logger::info("Login failed: {$email} [{$ip}]");
        return false;
    }

    private static function logAttempt(string $email, string $ip, string $ua, bool $success): void {
        try {
            $db = Connection::getConnection();
            $db->prepare("INSERT INTO login_attempts (email_attempted, ip_address, user_agent, success) VALUES (?,?,?,?)")
               ->execute([$email, $ip, substr($ua, 0, 255), $success ? 1 : 0]);
        } catch (\Throwable) {}
    }

    private static function createSession(array $user): void {
        session_regenerate_id(true);
        $_SESSION[self::$sessionKey] = [
            'id'                    => $user['id'],
            'public_id'             => $user['public_id'],
            'name'                  => $user['name'],
            'email'                 => $user['email'],
            'role'                  => $user['role'],
            'force_password_change' => (bool)$user['force_password_change'],
        ];
    }

    // ─── Logout ──────────────────────────────────────────────────────────────

    public static function logout(): void {
        $user = self::user();
        if ($user) {
            Logger::info("Logout: {$user['email']}");
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ─── Session Accessors ───────────────────────────────────────────────────

    public static function check(): bool {
        return !empty($_SESSION[self::$sessionKey]);
    }

    public static function user(): ?array {
        return $_SESSION[self::$sessionKey] ?? null;
    }

    public static function id(): ?int {
        return $_SESSION[self::$sessionKey]['id'] ?? null;
    }

    public static function role(): ?string {
        return $_SESSION[self::$sessionKey]['role'] ?? null;
    }

    public static function mustForcePasswordChange(): bool {
        return $_SESSION[self::$sessionKey]['force_password_change'] ?? false;
    }

    // ─── Role Guards ─────────────────────────────────────────────────────────

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: /index.php?page=login&error=unauthenticated');
            exit;
        }
    }

    public static function requireRole(string|array $roles): void {
        self::requireLogin();
        $allowed = is_string($roles) ? [$roles] : $roles;
        if (!in_array(self::role(), $allowed, true)) {
            header('Location: /index.php?page=dashboard&error=forbidden');
            exit;
        }
    }

    public static function hasRole(string|array $roles): bool {
        $allowed = is_string($roles) ? [$roles] : $roles;
        return in_array(self::role(), $allowed, true);
    }

    // ─── Audit Helper ────────────────────────────────────────────────────────

    public static function logAudit(string $action, string $entityTable, ?int $entityId = null): void {
        try {
            $db = Connection::getConnection();
            $db->prepare("INSERT INTO audit_logs (user_id, action, entity_table, entity_id, ip_address) VALUES (?,?,?,?,?)")
               ->execute([self::id(), $action, $entityTable, $entityId, Security::getClientIp()]);
        } catch (\Throwable) {}
    }
}
