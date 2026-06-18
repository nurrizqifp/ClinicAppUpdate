<?php
/**
 * AJAX / API Handler — Root
 */
require_once __DIR__ . '/config.php';

use App\Config\Env;
use App\App\Auth;
use App\App\Middleware;
use App\Services\QueueService;
use App\Services\AppointmentService;
use App\Services\InventoryService;
use App\Services\PharmacyService;
use App\Utils\Security;

header('Content-Type: application/json; charset=utf-8');
Env::load(BASE_PATH . '/.env');

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

function apiSuccess(array $data = [], string $message = 'OK'): void {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE); exit;
}
function apiError(string $error, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $error], JSON_UNESCAPED_UNICODE); exit;
}

try {
    switch ($action) {

        case 'public_queue':
            $doctorId = (int) filter_input(INPUT_GET, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
            if (!$doctorId) apiError('doctor_id diperlukan.');
            apiSuccess((new QueueService())->getTodayQueue($doctorId));

        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Method not allowed.', 405);
            $user = Auth::attempt(
                filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL) ?? '',
                filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW)     ?? ''
            );
            if ($user) apiSuccess(['name' => $user['name'], 'role' => $user['role'], 'force_password_change' => (bool)$user['force_password_change']], 'Login berhasil.');
            apiError('Email atau password salah, atau akun terkunci.', 401);

        case 'book_appointment':
            Middleware::apiGuard(['patient','receptionist','admin']);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Method not allowed.', 405);
            $patientId = Auth::role() === 'patient' ? Auth::id() : (int)($_POST['patient_id'] ?? 0);
            $doctorId  = (int)($_POST['doctor_id']  ?? 0);
            $complaint = Security::sanitizeString($_POST['complaint'] ?? '');
            $priority  = in_array($_POST['priority'] ?? '', ['normal','emergency'], true) ? $_POST['priority'] : 'normal';
            if (!$patientId || !$doctorId || empty($complaint)) apiError('Parameter tidak lengkap.');
            apiSuccess((new AppointmentService())->book($patientId, $doctorId, $complaint, $priority), 'Antrian berhasil dibuat.');

        case 'call_next':
            Middleware::apiGuard(['receptionist','admin']);
            $doctorId = (int) filter_input(INPUT_GET, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
            if (!$doctorId) apiError('doctor_id diperlukan.');
            $called = (new QueueService())->callNext($doctorId);
            if ($called) { Auth::logAudit('queue.call_next', 'appointments', $called['id']); apiSuccess($called, "Memanggil nomor {$called['queue_number']}."); }
            apiSuccess([], 'Tidak ada pasien menunggu.');

        case 'get_ewt':
            $doctorId    = (int) filter_input(INPUT_GET, 'doctor_id',    FILTER_SANITIZE_NUMBER_INT);
            $queueNumber = (int) filter_input(INPUT_GET, 'queue_number', FILTER_SANITIZE_NUMBER_INT);
            if (!$doctorId || !$queueNumber) apiError('doctor_id dan queue_number diperlukan.');
            apiSuccess(['ewt_minutes' => (new QueueService())->calculateEWT($doctorId, $queueNumber)]);

        case 'get_medicines':
            Middleware::apiGuard(['apoteker','doctor','admin']);
            apiSuccess((new InventoryService())->all());

        case 'low_stock':
            Middleware::apiGuard(['apoteker','admin']);
            apiSuccess((new InventoryService())->getLowStockItems());

        case 'pending_prescriptions':
            Middleware::apiGuard(['apoteker','admin']);
            apiSuccess((new PharmacyService())->getPendingPrescriptions());

        case 'dispense':
            Middleware::apiGuard(['apoteker','admin']);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Method not allowed.', 405);
            $prescriptionId = (int)($_POST['prescription_id'] ?? 0);
            if (!$prescriptionId) apiError('prescription_id diperlukan.');
            apiSuccess((new PharmacyService())->dispense($prescriptionId, Auth::id()), 'Obat berhasil diberikan.');

        case 'start_consultation':
            Middleware::apiGuard(['doctor','receptionist','admin']);
            $id = (int)($_POST['appointment_id'] ?? 0);
            if (!$id) apiError('appointment_id diperlukan.');
            (new QueueService())->startConsultation($id); apiSuccess([], 'Konsultasi dimulai.');

        case 'complete_appointment':
            Middleware::apiGuard(['doctor','receptionist','admin']);
            $id = (int)($_POST['appointment_id'] ?? 0);
            if (!$id) apiError('appointment_id diperlukan.');
            (new QueueService())->completeAppointment($id); apiSuccess([], 'Antrian selesai.');

        case 'get_all_queues':
            Middleware::apiGuard(['receptionist','admin','doctor']);
            apiSuccess((new QueueService())->getAllActiveQueues());

        default:
            apiError("Action '{$action}' tidak ditemukan.", 404);
    }
} catch (\Throwable $e) {
    \App\Logger::error("API Error [{$action}]: " . $e->getMessage());
    $isDev = Env::get('APP_ENV') === 'development';
    apiError($isDev ? $e->getMessage() : 'Terjadi kesalahan server.', 500);
}
