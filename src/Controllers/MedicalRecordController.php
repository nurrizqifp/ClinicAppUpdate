<?php
namespace App\Controllers;

use App\App\Auth;
use App\Services\MedicalRecordService;
use App\Services\QueueService;
use App\Utils\Validator;
use App\Utils\Security;

class MedicalRecordController extends BaseController {

    private MedicalRecordService $medicalRecordService;
    private QueueService $queueService;

    public function __construct() {
        $this->medicalRecordService = new MedicalRecordService();
        $this->queueService         = new QueueService();
    }

    // ─── Doctor: My Queue ─────────────────────────────────────────────────────

    /**
     * GET  ?page=medical-record  → Doctor's consultation queue today
     */
    public function index(): void {
        $this->requireRole(['doctor', 'admin']);
        // Admin sees all queues; doctor sees only their own
        $doctorId = Auth::hasRole('admin') ? null : Auth::id();
        $queue    = $this->medicalRecordService->getDoctorQueue($doctorId);

        $this->view('medical_record.index', [
            'queue' => $queue,
            'user'  => Auth::user(),
        ]);
    }

    // ─── Doctor: Create EHR ───────────────────────────────────────────────────

    /**
     * GET  ?page=medical-record&action=create&appointment_id=X  → Form
     * POST ?page=medical-record&action=create                    → Save
     */
    public function create(): void {
        $this->requireRole(['doctor']);
        $appointmentId = $this->inputInt('appointment_id');

        if ($this->isPost()) {
            Security::verifyCsrfOrFail();
            $this->store($appointmentId);
            return;
        }

        // GET: show form
        if (!$appointmentId) {
            $this->redirect('medical-record', ['error' => 'ID appointment tidak valid.']);
        }

        // Prefetch medicines for the prescription form
        $medicines = (new \App\Services\InventoryService())->all();

        $this->view('medical_record.create', [
            'appointment_id' => $appointmentId,
            'medicines'      => $medicines,
            'user'           => Auth::user(),
        ]);
    }

    private function store(int $appointmentId): void {
        $diagnosis = $this->inputStr('diagnosis');
        $notes     = $this->inputStr('notes');

        $validator = Validator::make(
            ['appointment_id' => $appointmentId, 'diagnosis' => $diagnosis, 'notes' => $notes],
            [
                'appointment_id' => 'required|numeric|min:1',
                'diagnosis'      => 'required|min:3|max:2000',
                'notes'          => 'required|min:3|max:5000',
            ]
        );

        if ($validator->fails()) {
            $this->redirect('medical-record', [
                'action'         => 'create',
                'appointment_id' => $appointmentId,
                'error'          => urlencode($validator->firstError()),
            ]);
            return;
        }

        // Parse prescription items from POST: medicine_id[], dosage[], quantity[]
        $prescriptionItems = [];
        $medicineIds = $_POST['medicine_id'] ?? [];
        $dosages     = $_POST['dosage']      ?? [];
        $quantities  = $_POST['quantity']    ?? [];

        foreach ($medicineIds as $idx => $medId) {
            if (empty($medId)) continue;
            $prescriptionItems[] = [
                'medicine_id' => (int)$medId,
                'dosage'      => htmlspecialchars(trim($dosages[$idx] ?? ''), ENT_QUOTES, 'UTF-8'),
                'quantity'    => (int)($quantities[$idx] ?? 1),
            ];
        }

        try {
            $result = $this->medicalRecordService->createRecord(
                $appointmentId,
                Auth::id(),
                $diagnosis,
                $notes,
                $prescriptionItems
            );
            $this->redirect('medical-record', ['success' => urlencode('Rekam medis berhasil disimpan.')]);
        } catch (\Throwable $e) {
            \App\Logger::error("EHR create error: " . $e->getMessage());
            $this->redirect('medical-record', [
                'action'         => 'create',
                'appointment_id' => $appointmentId,
                'error'          => urlencode($e->getMessage()),
            ]);
        }
    }

    // ─── View Record ──────────────────────────────────────────────────────────

    /**
     * GET  ?page=medical-record&action=view&appointment_id=X
     */
    public function view_record(): void {
        $this->requireLogin();
        $appointmentId = $this->inputInt('appointment_id');

        $record = $this->medicalRecordService->getByAppointment($appointmentId);
        if (!$record) {
            $this->redirect('medical-record', ['error' => urlencode('Rekam medis tidak ditemukan.')]);
        }

        // Access check: Only doctors, admins, or the patient themselves can view it
        $role = Auth::role();
        if (!in_array($role, ['doctor', 'admin', 'patient', 'receptionist'], true)) {
            $this->redirect('dashboard', ['error' => urlencode('Akses ditolak.')]);
        }
        if ($role === 'patient' && (int)$record['patient_id'] !== Auth::id()) {
            $this->redirect('dashboard', ['error' => urlencode('Akses ditolak.')]);
        }

        $this->view('medical_record.view', [
            'record' => $record,
            'user'   => Auth::user(),
        ]);
    }

    // ─── Patient: History ─────────────────────────────────────────────────────

    /**
     * GET  ?page=medical-record&action=history  → Patient's own EHR history
     */
    public function history(): void {
        $this->requireRole(['patient']);
        $records = $this->medicalRecordService->getPatientHistory(Auth::id());
        $this->view('medical_record.history', [
            'records' => $records,
            'user'    => Auth::user(),
        ]);
    }
}
