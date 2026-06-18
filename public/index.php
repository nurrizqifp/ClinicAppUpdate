<?php
/**
 * Application Entry Point
 * All web requests are routed through this file.
 */

require_once __DIR__ . '/../config.php';

use App\Config\Env;
use App\App\Auth;
use App\App\Router;
use App\App\Middleware;
use App\Controllers\AppointmentController;
use App\Controllers\MedicalRecordController;
use App\Controllers\InventoryController;
use App\Controllers\QueueController;
use App\Controllers\AdminController;

// ─── Boot Env ────────────────────────────────────────────────────────────────
Env::load(BASE_PATH . '/.env');

// ─── Router Definition ────────────────────────────────────────────────────────
$router = new Router();

// --- Auth pages ---
$router->get('login',  function () { include BASE_PATH . '/src/Views/auth/login.php'; });
$router->post('login', function () {
    App\Utils\Security::verifyCsrfOrFail();
    $email    = filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL)        ?? '';
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW)            ?? '';

    $user = App\App\Auth::attempt($email, $password);
    if ($user) {
        if ($user['force_password_change']) {
            header('Location: /index.php?page=change-password&notice=force_change');
        } else {
            header('Location: /index.php?page=dashboard');
        }
        exit;
    }
    header('Location: /index.php?page=login&error=' . urlencode('Email atau password salah, atau akun terkunci.'));
    exit;
});

$router->any('logout', function () {
    App\App\Auth::logout();
    header('Location: /index.php?page=login');
    exit;
});

$router->get('change-password', function () {
    App\App\Auth::requireLogin();
    include BASE_PATH . '/src/Views/auth/change_password.php';
});
$router->post('change-password', function () {
    App\App\Auth::requireLogin();
    App\Utils\Security::verifyCsrfOrFail();

    $newPassword  = $_POST['new_password']  ?? '';
    $confirmation = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        header('Location: /index.php?page=change-password&error=' . urlencode('Password minimal 8 karakter.'));
        exit;
    }
    if ($newPassword !== $confirmation) {
        header('Location: /index.php?page=change-password&error=' . urlencode('Konfirmasi password tidak cocok.'));
        exit;
    }
    $hash = App\Utils\Security::hashPassword($newPassword);
    $db   = App\Database\Connection::getConnection();
    $db->prepare("UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?")->execute([$hash, App\App\Auth::id()]);
    // Update session flag
    $_SESSION['_auth_user']['force_password_change'] = false;
    header('Location: /index.php?page=dashboard&success=' . urlencode('Password berhasil diubah.'));
    exit;
});

// --- Dashboard ---
$router->get('dashboard', function () {
    App\App\Auth::requireLogin();
    $role = App\App\Auth::role();

    // Role-based dashboard redirect
    $view = match($role) {
        'admin'        => 'admin/dashboard',
        'doctor'       => 'doctor/dashboard',
        'receptionist' => 'receptionist/dashboard',
        'apoteker'     => 'apoteker/dashboard',
        'patient'      => 'patient/dashboard',
        default        => 'dashboard',
    };
    include BASE_PATH . "/src/Views/{$view}.php";
});

// --- Appointments ---
$router->any('appointment', function () {
    $controller = new AppointmentController();
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $view   = filter_input(INPUT_GET, 'view',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

    if ($action === 'cancel' && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
        $controller->cancel();
    } elseif ($view === 'list') {
        $controller->list();
    } elseif ($view === 'history') {
        $controller->history();
    } else {
        $controller->index();
    }
});

$router->get('queue-status', [AppointmentController::class, 'queueStatus']);

// --- Queue ---
$router->any('queue', function () {
    $controller = new QueueController();
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

    match(true) {
        $action === 'call'     => $controller->callNext(),
        $action === 'start'    => $controller->start(),
        $action === 'complete' => $controller->complete(),
        $action === 'my'       => $controller->myQueue(),
        default                => $controller->index(),
    };
});

$router->get('public-queue', [QueueController::class, 'publicBoard']);

// --- Medical Records (EHR) ---
$router->any('medical-record', function () {
    $controller = new MedicalRecordController();
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

    match($action) {
        'create'  => $controller->create(),
        'view'    => $controller->view_record(),
        'history' => $controller->history(),
        default   => $controller->index(),
    };
});

// --- Inventory ---
$router->any('inventory', function () {
    $controller = new InventoryController();
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

    match($action) {
        'create'  => $controller->create(),
        'update'  => $controller->update(),
        'stock'   => $controller->stock(),
        'delete'  => $controller->delete(),
        default   => $controller->index(),
    };
});

// --- Pharmacy ---
$router->any('pharmacy', function () {
    $controller = new InventoryController();
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

    match($action) {
        'dispense' => $controller->dispense(),
        default    => $controller->pharmacy(),
    };
});

// --- Admin ---
$router->any('admin', function () {
    $controller = new AdminController();
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $method = strtoupper($_SERVER['REQUEST_METHOD']);

    match(true) {
        $action === 'users'                           => $controller->users(),
        $action === 'create-user' && $method === 'GET'  => $controller->createUserForm(),
        $action === 'create-user' && $method === 'POST' => $controller->createUser(),
        $action === 'edit-user'   && $method === 'GET'  => $controller->editUserForm(),
        $action === 'edit-user'   && $method === 'POST' => $controller->editUser(),
        $action === 'delete-user'                     => $controller->deleteUser(),
        $action === 'toggle-user'                     => $controller->toggleUserActive(),
        $action === 'settings'                        => $controller->settings(),
        $action === 'save-setting'                    => $controller->saveSetting(),
        $action === 'audit-logs'                      => $controller->auditLogs(),
        default                                       => $controller->dashboard(),
    };
});

// ─── Dispatch ────────────────────────────────────────────────────────────────
$router->dispatch();
