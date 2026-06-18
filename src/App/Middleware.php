<?php
namespace App\App;

use App\App\Auth;

class Middleware {
    // Role permission map: role => array of allowed pages/controllers
    private static array $rolePermissions = [
        'patient'      => ['dashboard', 'appointment', 'profile', 'queue-status', 'medical-record'],
        'receptionist' => ['dashboard', 'appointment', 'queue', 'profile'],
        'doctor'       => ['dashboard', 'queue', 'medical-record', 'profile'],
        'apoteker'     => ['dashboard', 'pharmacy', 'inventory', 'profile'],
        'admin'        => ['dashboard', 'appointment', 'queue', 'medical-record', 'pharmacy', 'inventory', 'admin', 'profile'],
    ];

    /**
     * Run all middleware checks for a given page request.
     * Returns true if request is allowed to proceed.
     */
    public static function handle(string $page): bool {
        // Public pages — no auth required
        $publicPages = ['login', 'register', 'public-queue', 'forgot-password'];
        if (in_array($page, $publicPages, true)) {
            return true;
        }

        // Must be logged in
        if (!Auth::check()) {
            $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            if ($page === 'dashboard') {
                header("Location: {$baseUrl}/login");
            } else {
                header("Location: {$baseUrl}/login?error=unauthenticated");
            }
            exit;
        }

        // Pages allowed for any authenticated user
        $authCommonPages = ['change-password', 'logout'];
        if (in_array($page, $authCommonPages, true)) {
            return true;
        }

        // Force password change intercept
        if (Auth::mustForcePasswordChange() && $page !== 'change-password') {
            header('Location: /index.php?page=change-password&notice=force_change');
            exit;
        }

        // Role-based access check
        $role = Auth::role();
        $allowed = self::$rolePermissions[$role] ?? [];

        if (!in_array($page, $allowed, true)) {
            // Check if it's an admin-only page
            if ($role !== 'admin') {
                header('Location: /index.php?page=dashboard&error=forbidden');
                exit;
            }
        }

        return true;
    }

    /**
     * Alias: guard specific pages for specific roles — use in controllers directly.
     */
    public static function onlyRoles(string|array $roles): void {
        Auth::requireRole($roles);
    }

    /**
     * API middleware: for AJAX calls that return JSON errors instead of redirects.
     */
    public static function apiGuard(string|array $roles = []): void {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthenticated.']);
            exit;
        }

        if (!empty($roles)) {
            $allowed = is_string($roles) ? [$roles] : $roles;
            if (!in_array(Auth::role(), $allowed, true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden. Insufficient permissions.']);
                exit;
            }
        }
    }
}
