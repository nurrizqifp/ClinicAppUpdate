<?php
/**
 * AJAX / API Handler
 * Returns JSON responses for all frontend AJAX calls.
 */

require_once __DIR__ . '/../config.php';

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
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function apiError(string $error, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    switch ($action) {

        // ─── Public: Queue Board (no auth) ───────────────────────────────────
        case 'public_queue':
            $doctorId = filter_input(INPUT_GET, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
            if (!$doctorId) apiError('doctor_id diperlukan.');
            $queueService = new QueueService();
            apiSuccess($queueService->getTodayQueue((int)$doctorId));

        // ─── Auth: Login ──────────────────────────────────────────────────────
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Method not allowed.', 405);
            $email    = filter_input(INPUT_POST, 'email',    FILTER_SANITIZE_EMAIL)   ?? '';
            $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW)       ?? '';

            $user = Auth::attempt($email, $password);
            if ($user) {
                apiSuccess([
                    'name'  => $user['name'],
                    'role'  => $user['role'],
                    'force_password_change' => (bool)$user['force_password_change'],
                ], 'Login berhasil.');
            }
            apiError('Email atau password salah, atau akun terkunci.', 401);

        // ─── Appointment: Book ────────────────────────────────────────────────
        case 'book_appointment':
            Middleware::apiGuard(['patient', 'receptionist', 'admin']);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Method not allowed.', 405);

            $patientId = Auth::role() === 'patient' ? Auth::id() : (int)($_POST['patient_id'] ?? 0);
            $doctorId  = (int)($_POST['doctor_id']  ?? 0);
            $complaint = Security::sanitizeString($_POST['complaint'] ?? '');
            $priority  = in_array($_POST['priority'] ?? 'normal', ['normal','emergency'], true) ? $_POST['priority'] : 'normal';

            if (!$patientId || !$doctorId || empty($complaint)) apiError('Parameter tidak lengkap.');

            $appointmentService = new AppointmentService();
            $result = $appointmentService->book($patientId, $doctorId, $complaint, $priority);
            apiSuccess($result, 'Antrian berhasil dibuat.');

        // ─── Queue: Call Next ─────────────────────────────────────────────────
        case 'call_next':
            Middleware::apiGuard(['receptionist', 'admin']);
            $doctorId = (int)filter_input(INPUT_GET, 'doctor_id', FILTER_SANITIZE_NUMBER_INT);
            if (!$doctorId) apiError('doctor_id diperlukan.');

            $queueService = new QueueService();
            $called = $queueService->callNext($doctorId);
            if ($called) {
                Auth::logAudit('queue.call_next', 'appointments', $called['id']);
                apiSuccess($called, "Memanggil nomor {$called['queue_number']}.");
            }
            apiSuccess([], 'Tidak ada pasien yang menunggu.');

        // ─── Queue: EWT ───────────────────────────────────────────────────────
        case 'get_ewt':
            $doctorId    = (int)filter_input(INPUT_GET, 'doctor_id',    FILTER_SANITIZE_NUMBER_INT);
            $queueNumber = (int)filter_input(INPUT_GET, 'queue_number', FILTER_SANITIZE_NUMBER_INT);
            if (!$doctorId || !$queueNumber) apiError('Paramater dokter dan nomor antrian diperlukan.');

            $queueService = new QueueService();
            $ewt = $queueService->calculateEWT($doctorId, $queueNumber);
            apiSuccess(['ewt_minutes' => $ewt]);

        // ─── Inventory: List ──────────────────────────────────────────────────
        case 'get_medicines':
            Middleware::apiGuard(['apoteker', 'doctor', 'admin']);
            $inventoryService = new InventoryService();
            apiSuccess($inventoryService->all());

        // ─── Inventory: Low Stock ─────────────────────────────────────────────
        case 'low_stock':
            Middleware::apiGuard(['apoteker', 'admin']);
            $inventoryService = new InventoryService();
            apiSuccess($inventoryService->getLowStockItems());

        // ─── Pharmacy: Pending Prescriptions ──────────────────────────────────
        case 'pending_prescriptions':
            Middleware::apiGuard(['apoteker', 'admin']);
            $pharmacyService = new PharmacyService();
            apiSuccess($pharmacyService->getPendingPrescriptions());

        // ─── Pharmacy: Dispense ───────────────────────────────────────────────
        case 'dispense':
            Middleware::apiGuard(['apoteker', 'admin']);
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiError('Method not allowed.', 405);

            $prescriptionId = (int)($_POST['prescription_id'] ?? 0);
            if (!$prescriptionId) apiError('prescription_id diperlukan.');

            $pharmacyService = new PharmacyService();
            $result = $pharmacyService->dispense($prescriptionId, Auth::id());
            apiSuccess($result, 'Obat berhasil diberikan.');

        // ─── Queue: Status Transitions ────────────────────────────────────────
        case 'start_consultation':
            Middleware::apiGuard(['doctor', 'receptionist', 'admin']);
            $appointmentId = (int)($_POST['appointment_id'] ?? 0);
            if (!$appointmentId) apiError('appointment_id diperlukan.');
            (new QueueService())->startConsultation($appointmentId);
            apiSuccess([], 'Konsultasi dimulai.');

        case 'complete_appointment':
            Middleware::apiGuard(['doctor', 'receptionist', 'admin']);
            $appointmentId = (int)($_POST['appointment_id'] ?? 0);
            if (!$appointmentId) apiError('appointment_id diperlukan.');
            (new QueueService())->completeAppointment($appointmentId);
            apiSuccess([], 'Antrian selesai.');

        default:
            apiError("Action '{$action}' tidak ditemukan.", 404);
    }
} catch (\Throwable $e) {
    \App\Logger::error("API Error [{$action}]: " . $e->getMessage());
    $isDev = Env::get('APP_ENV') === 'development';
    apiError($isDev ? $e->getMessage() : 'Terjadi kesalahan server. Silakan coba lagi.', 500);
}
