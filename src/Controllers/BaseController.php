<?php
namespace App\Controllers;

use App\App\Auth;
use App\Utils\Security;

abstract class BaseController {

    // ─── Auth Helpers ─────────────────────────────────────────────────────────

    protected function requireLogin(): void {
        Auth::requireLogin();
    }

    protected function requireRole(string|array $roles): void {
        Auth::requireRole($roles);
    }

    // ─── Response Helpers ─────────────────────────────────────────────────────

    /**
     * Send a JSON response and terminate.
     */
    protected function json(array $data, int $status = 200): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function jsonSuccess(array $data = [], string $message = 'OK'): void {
        $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function jsonError(string $error, int $status = 400): void {
        $this->json(['success' => false, 'error' => $error], $status);
    }

    // ─── Redirect Helpers ─────────────────────────────────────────────────────

    protected function redirect(string $page, array $params = []): void {
        $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        $url = $baseUrl . '/' . ltrim($page, '/') . $queryString;
        header("Location: {$url}");
        exit;
    }

    protected function redirectBack(array $params = []): void {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (empty($referer)) {
            $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $referer = $baseUrl . '/dashboard';
        }
        $url = $referer;
        if (!empty($params)) {
            $separator = str_contains($referer, '?') ? '&' : '?';
            $url .= $separator . http_build_query($params);
        }
        header("Location: {$url}");
        exit;
    }

    // ─── View Rendering ───────────────────────────────────────────────────────

    /**
     * Include a view file from src/Views/, passing variables.
     */
    protected function view(string $viewPath, array $vars = []): void {
        extract($vars, EXTR_SKIP);
        $csrfField = Security::csrfField();
        $file = BASE_PATH . '/src/Views/' . str_replace('.', '/', $viewPath) . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            \App\Logger::error("View not found: {$file}");
            echo "<p>View not found: <code>{$viewPath}</code></p>";
        }
    }

    // ─── Input Helpers ────────────────────────────────────────────────────────

    protected function input(string $key, mixed $default = null): mixed {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function inputInt(string $key, int $default = 0): int {
        $val = $this->input($key);
        return $val !== null ? (int) filter_var($val, FILTER_SANITIZE_NUMBER_INT) : $default;
    }

    protected function inputStr(string $key, string $default = ''): string {
        $val = $this->input($key, $default);
        return htmlspecialchars(trim((string)$val), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected function isPost(): bool {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }

    protected function isGet(): bool {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'GET';
    }
}
