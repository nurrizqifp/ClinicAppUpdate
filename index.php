<?php
/**
 * Application Front Controller (Root Entry Point)
 * Served directly by Laravel Herd / Apache at clinicappupdate.test
 */

require_once __DIR__ . '/config.php';

// Start output buffering to automatically rewrite all /index.php?page= links into Clean URLs
ob_start(function ($buffer) {
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $pattern = '/(href|action)="\/?index\.php\?page=([^"&?]+)(?:\?|&amp;|&)?([^"]*)"/i';
    return preg_replace_callback($pattern, function($matches) use ($baseUrl) {
        $attribute = $matches[1];
        $page = $matches[2];
        $query = $matches[3];
        if ($query !== '') {
            $query = str_replace('&amp;', '&', $query);
            $query = '?' . $query;
        }
        return $attribute . '="' . $baseUrl . '/' . $page . $query . '"';
    }, $buffer);
});

use App\Config\Env;
use App\App\Auth;
use App\App\Router;
use App\Controllers\AppointmentController;
use App\Controllers\MedicalRecordController;
use App\Controllers\InventoryController;
use App\Controllers\QueueController;
use App\Controllers\AdminController;
use App\Utils\Security;

Env::load(BASE_PATH . '/.env');

// ─── Router ──────────────────────────────────────────────────────────────────
$router = new Router();

// ── Auth: Login ──────────────────────────────────────────────────────────────
$router->get('login', fn() => include BASE_PATH . '/src/Views/auth/login.php');

$router->post('login', function () {
    Security::verifyCsrfOrFail();
    $email    = filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL)  ?? '';
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW)      ?? '';
    $user = Auth::attempt($email, $password);
    if ($user) {
        $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $next = $user['force_password_change'] ? 'change-password?notice=force_change' : 'dashboard';
        header("Location: {$baseUrl}/{$next}"); exit;
    }
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    header("Location: {$baseUrl}/login?error=" . urlencode('Email atau password salah, atau akun terkunci.')); exit;
});

$router->any('logout', function () {
    Auth::logout();
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    header("Location: {$baseUrl}/login"); exit;
});

// ── Auth: Change Password ─────────────────────────────────────────────────────
$router->get('change-password', function () {
    Auth::requireLogin();
    include BASE_PATH . '/src/Views/auth/change_password.php';
});
$router->post('change-password', function () {
    Auth::requireLogin();
    Security::verifyCsrfOrFail();
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if (strlen($new) < 8) { header("Location: {$baseUrl}/change-password?error=" . urlencode('Password minimal 8 karakter.')); exit; }
    if ($new !== $confirm) { header("Location: {$baseUrl}/change-password?error=" . urlencode('Konfirmasi password tidak cocok.')); exit; }
    $hash = Security::hashPassword($new);
    $db   = \App\Database\Connection::getConnection();
    $db->prepare("UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?")->execute([$hash, Auth::id()]);
    $_SESSION['_auth_user']['force_password_change'] = false;
    header("Location: {$baseUrl}/dashboard?success=" . urlencode('Password berhasil diubah.')); exit;
});

// ── Dashboard ─────────────────────────────────────────────────────────────────
$router->get('dashboard', function () {
    Auth::requireLogin();
    $roleViews = [
        'admin'        => 'admin/dashboard',
        'doctor'       => 'doctor/dashboard',
        'receptionist' => 'receptionist/dashboard',
        'apoteker'     => 'apoteker/dashboard',
        'patient'      => 'patient/dashboard',
    ];
    $view = $roleViews[Auth::role()] ?? 'dashboard';
    include BASE_PATH . "/src/Views/{$view}.php";
});

// ── Profile ───────────────────────────────────────────────────────────────────
$router->get('profile', function () {
    Auth::requireLogin();
    include BASE_PATH . '/src/Views/profile/index.php';
});

// ── Appointments ──────────────────────────────────────────────────────────────
$router->any('appointment', function () {
    $ctrl   = new AppointmentController();
    $action = trim((string)($_GET['action'] ?? ''));
    $view   = trim((string)($_GET['view'] ?? ''));
    match(true) {
        $_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'cancel' => $ctrl->cancel(),
        $view === 'list'    => $ctrl->list(),
        $view === 'history' => $ctrl->history(),
        default             => $ctrl->index(),
    };
});
$router->get('queue-status', [AppointmentController::class, 'queueStatus']);

// ── Queue ─────────────────────────────────────────────────────────────────────
$router->any('queue', function () {
    $ctrl   = new QueueController();
    $action = trim((string)($_GET['action'] ?? ''));
    match($action) {
        'call'     => $ctrl->callNext(),
        'start'    => $ctrl->start(),
        'complete' => $ctrl->complete(),
        'my'       => $ctrl->myQueue(),
        default    => $ctrl->index(),
    };
});
$router->get('public-queue', [QueueController::class, 'publicBoard']);

// ── Medical Records ────────────────────────────────────────────────────────────
$router->any('medical-record', function () {
    $ctrl   = new MedicalRecordController();
    $action = trim((string)($_GET['action'] ?? ''));
    match($action) {
        'create'  => $ctrl->create(),
        'view'    => $ctrl->view_record(),
        'history' => $ctrl->history(),
        default   => $ctrl->index(),
    };
});

// ── Inventory ─────────────────────────────────────────────────────────────────
$router->any('inventory', function () {
    $ctrl   = new InventoryController();
    $action = trim((string)($_GET['action'] ?? ''));
    match($action) {
        'create'  => $ctrl->create(),
        'update'  => $ctrl->update(),
        'stock'   => $ctrl->stock(),
        'delete'  => $ctrl->delete(),
        default   => $ctrl->index(),
    };
});

// ── Pharmacy ──────────────────────────────────────────────────────────────────
$router->any('pharmacy', function () {
    $ctrl   = new InventoryController();
    $action = trim((string)($_GET['action'] ?? ''));
    match($action) {
        'dispense' => $ctrl->dispense(),
        default    => $ctrl->pharmacy(),
    };
});

// ── Admin ─────────────────────────────────────────────────────────────────────
$router->any('admin', function () {
    $ctrl   = new AdminController();
    $action = trim((string)($_GET['action'] ?? ''));
    match($action) {
        'users'        => $ctrl->users(),
        'toggle-user'  => $ctrl->toggleUserActive(),
        'settings'     => $ctrl->settings(),
        'save-setting' => $ctrl->saveSetting(),
        'audit-logs'   => $ctrl->auditLogs(),
        default        => $ctrl->dashboard(),
    };
});

// ─── Dispatch ─────────────────────────────────────────────────────────────────
$router->dispatch();
