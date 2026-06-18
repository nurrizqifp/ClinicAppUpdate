<?php
namespace App\Controllers;

use App\App\Auth;
use App\App\Middleware;
use App\Services\AppointmentService;
use App\Services\QueueService;
use App\Utils\Validator;
use App\Utils\Security;

class AppointmentController extends BaseController {

    private AppointmentService $appointmentService;
    private QueueService $queueService;

    public function __construct() {
        $this->appointmentService = new AppointmentService();
        $this->queueService       = new QueueService();
    }

    /**
     * GET  ?page=appointment          â†’ Show booking form
     * POST ?page=appointment          â†’ Submit booking
     */
    public function index(): void {
        $view = trim((string)($_GET['view'] ?? ''));
        if ($view === 'history') {
            $this->history();
            return;
        }
        if ($view === 'list') {
            $this->list();
            return;
        }

        $this->requireRole(['patient', 'receptionist', 'admin']);

        if ($this->isPost()) {
            Security::verifyCsrfOrFail();
            $this->store();
            return;
        }

        $doctors = $this->appointmentService->getAvailableDoctors();
        $this->view('appointment.index', [
            'doctors' => $doctors,
            'user'    => Auth::user(),
        ]);
    }

    private function store(): void {
        // Determine patient ID
        $role      = Auth::role();
        $patientId = Auth::id(); // default: logged-in patient

        if (in_array($role, ['receptionist', 'admin'], true)) {
            $patientNik = trim($_POST['patient_nik'] ?? '');
            if (empty($patientNik) || strlen($patientNik) !== 16 || !ctype_digit($patientNik)) {
                $this->redirect('appointment', ['error' => urlencode('NIK Pasien wajib berupa 16 digit angka.')]);
                return;
            }
            
            $db = \App\Database\Connection::getConnection();
            $stmt = $db->prepare("SELECT user_id FROM patients WHERE nik = ? LIMIT 1");
            $stmt->execute([$patientNik]);
            $pRow = $stmt->fetch();
            if (!$pRow) {
                $this->redirect('appointment', ['error' => urlencode('Pasien dengan NIK tersebut tidak terdaftar.')]);
                return;
            }
            $patientId = (int)$pRow['user_id'];
        }

        $data = [
            'patient_id' => $patientId,
            'doctor_id'  => $this->inputInt('doctor_id'),
            'complaint'  => $this->inputStr('complaint'),
            'priority'   => $this->inputStr('priority') ?: 'normal',
        ];

        $validator = Validator::make($data, [
            'patient_id' => 'required|numeric|min:1',
            'doctor_id'  => 'required|numeric|min:1',
            'complaint'  => 'required|min:5|max:1000',
            'priority'   => 'in:normal,emergency',
        ]);

        if ($validator->fails()) {
            $this->redirect('appointment', ['error' => urlencode($validator->firstError())]);
            return;
        }

        try {
            $result = $this->appointmentService->book(
                $data['patient_id'],
                $data['doctor_id'],
                $data['complaint'],
                $data['priority']
            );
            $this->redirect('queue-status', [
                'public_id' => $result['public_id'],
                'success'   => '1',
                'queue'     => $result['queue_number'],
                'ewt'       => $result['ewt_minutes'],
            ]);
        } catch (\Throwable $e) {
            \App\Logger::error("Appointment booking error: " . $e->getMessage());
            $this->redirect('appointment', ['error' => urlencode('Gagal mendaftar antrian. Silakan coba lagi.')]);
        }
    }

    // â”€â”€â”€ Queue Status (Public) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * GET  ?page=queue-status&public_id=xxx  â†’ Show ticket status (no auth required)
     */
    /**
     * GET  ?page=queue-status&queue_number=A-001  -> Show ticket status (no auth required)
     * Searches by queue_number (e.g. 'A-001') instead of internal ID.
     */
    public function queueStatus(): void {
        $queueNumber = strtoupper(trim(Security::sanitizeString($_GET['queue_number'] ?? '')));
        $publicId    = trim(Security::sanitizeString($_GET['public_id'] ?? ''));
        $appointment = null;
        $ewt         = null;

        if ($queueNumber !== '') {
            $appointment = $this->appointmentService->getByQueueNumber($queueNumber);
        } elseif ($publicId !== '') {
            $appointment = $this->appointmentService->getByPublicId($publicId);
        }

        if ($appointment) {
            $ewt = $this->queueService->calculateEWT(
                $appointment['doctor_id'],
                $appointment['queue_number']
            );
        }

        $this->view('appointment.queue_status', [
            'appointment'  => $appointment,
            'ewt'          => $ewt,
            'queue_number' => $queueNumber ?: ($appointment['queue_number'] ?? ''),
            'public_id'    => $publicId ?: ($appointment['public_id'] ?? ''),
        ]);
    }

    // â”€â”€â”€ Receptionist / Admin: List â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * GET  ?page=appointment&view=list  â†’ All today's appointments
     */
    public function list(): void {
        $this->requireRole(['receptionist', 'admin']);
        $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS);
        $appointments = $this->appointmentService->getAllToday($search);
        $this->view('appointment.list', [
            'appointments' => $appointments,
            'user'         => Auth::user(),
        ]);
    }

    // â”€â”€â”€ Cancel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * POST ?page=appointment&action=cancel
     */
    public function cancel(): void {
        $this->requireLogin();
        Security::verifyCsrfOrFail();

        $appointmentId = $this->inputInt('appointment_id');
        if (!$appointmentId) {
            $this->redirect('appointment', ['error' => 'ID tidak valid.']);
        }

        $success = $this->appointmentService->cancel(
            $appointmentId,
            Auth::id(),
            Auth::role()
        );

        $msg = $success ? 'Antrian berhasil dibatalkan.' : 'Gagal membatalkan antrian.';
        $param = $success ? ['success' => urlencode($msg)] : ['error' => urlencode($msg)];
        $this->redirect('appointment', $param);
    }

    // â”€â”€â”€ Patient: History â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * GET  ?page=appointment&view=history
     */
    public function history(): void {
        $this->requireRole(['patient']);
        $history = $this->appointmentService->getPatientHistory(Auth::id());
        $this->view('appointment.history', [
            'history' => $history,
            'user'    => Auth::user(),
        ]);
    }
}
