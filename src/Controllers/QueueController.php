<?php
namespace App\Controllers;

use App\App\Auth;
use App\Services\QueueService;
use App\Utils\Security;

class QueueController extends BaseController {

    private QueueService $queueService;

    public function __construct() {
        $this->queueService = new QueueService();
    }

    // ─── Receptionist: Active Queue Board ─────────────────────────────────────

    /**
     * GET  ?page=queue  → Full active queue across all doctors
     */
    public function index(): void {
        $this->requireRole(['receptionist', 'admin', 'doctor']);
        $queues = $this->queueService->getAllActiveQueues();

        $this->view('queue.index', [
            'queues' => $queues,
            'user'   => Auth::user(),
        ]);
    }

    // ─── Doctor: My Queue Today ───────────────────────────────────────────────

    /**
     * GET  ?page=queue&action=my  → Doctor-specific queue (doctor role only)
     */
    public function myQueue(): void {
        $this->requireRole(['doctor']);
        $queue = $this->queueService->getTodayQueue(Auth::id());

        $this->view('queue.my_queue', [
            'queue' => $queue,
            'user'  => Auth::user(),
        ]);
    }

    // ─── Receptionist: Call Next ──────────────────────────────────────────────

    /**
     * POST ?page=queue&action=call
     */
    public function callNext(): void {
        $this->requireRole(['receptionist', 'admin']);
        Security::verifyCsrfOrFail();

        $doctorId = $this->inputInt('doctor_id');
        if (!$doctorId) {
            $this->redirect('queue', ['error' => 'Doctor ID diperlukan.']);
        }

        $called = $this->queueService->callNext($doctorId);
        if ($called) {
            Auth::logAudit('queue.call_next', 'appointments', $called['id']);
            $this->redirect('queue', ['success' => urlencode("Nomor {$called['queue_number']} — {$called['patient_name']} dipanggil.")]);
        } else {
            $this->redirect('queue', ['info' => urlencode('Tidak ada pasien yang menunggu.')]);
        }
    }

    // ─── Doctor: Start Consultation ────────────────────────────────────────────

    /**
     * POST ?page=queue&action=start
     */
    public function start(): void {
        $this->requireRole(['doctor', 'receptionist', 'admin']);
        Security::verifyCsrfOrFail();

        $appointmentId = $this->inputInt('appointment_id');
        $success = $this->queueService->startConsultation($appointmentId);

        $msg = $success ? 'Konsultasi dimulai.' : 'Gagal memulai konsultasi.';
        $this->redirect('queue', $success ? ['success' => urlencode($msg)] : ['error' => urlencode($msg)]);
    }

    // ─── Doctor: Complete Consultation ────────────────────────────────────────

    /**
     * POST ?page=queue&action=complete
     */
    public function complete(): void {
        $this->requireRole(['doctor', 'receptionist', 'admin']);
        Security::verifyCsrfOrFail();

        $appointmentId = $this->inputInt('appointment_id');
        $success = $this->queueService->completeAppointment($appointmentId);

        $msg = $success ? 'Antrian selesai.' : 'Gagal menyelesaikan antrian.';
        $this->redirect('queue', $success ? ['success' => urlencode($msg)] : ['error' => urlencode($msg)]);
    }

    // ─── Public Queue Board ────────────────────────────────────────────────────

    /**
     * GET  ?page=public-queue&doctor_id=X  → Public-facing display (no auth)
     */
    public function publicBoard(): void {
        $boardData = $this->queueService->getPublicQueueBoardData();
        $this->view('queue.public_board', [
            'boardData' => $boardData,
        ]);
    }
}
