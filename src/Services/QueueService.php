<?php
namespace App\Services;

use App\Database\Connection;
use App\Config\Env;
use PDO;

class QueueService {
    private PDO $db;
    private int $avgConsultationMinutes;

    public function __construct() {
        $this->db = Connection::getConnection();
        // Fetch avg consultation duration from system_settings, fall back to env/default
        $this->avgConsultationMinutes = $this->fetchSetting('avg_consultation_minutes', 10);
    }

    private function fetchSetting(string $key, mixed $default = null): mixed {
        $stmt = $this->db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    }

    // ─── Queue Number Generation ─────────────────────────────────────────────

    /**
     * Generate the next queue number for a doctor on today's date.
     * Increment is per-doctor per-day, resets each new calendar day.
     */
    public function generateQueueNumber(int $doctorId): int {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT MAX(queue_number) AS max_queue
            FROM appointments
            WHERE doctor_id = ? AND DATE(created_at) = ?
        ");
        $stmt->execute([$doctorId, $today]);
        $row = $stmt->fetch();
        return ($row['max_queue'] ?? 0) + 1;
    }

    // ─── Estimated Waiting Time ──────────────────────────────────────────────

    /**
     * Calculate estimated waiting time in minutes for a given doctor.
     * Formula: (patientsWaitingAhead + 1) * avgConsultationDuration
     *
     * @param  int $doctorId
     * @param  int $currentQueueNumber  The queue number of the patient asking for EWT.
     * @return int  Minutes
     */
    public function calculateEWT(int $doctorId, int $currentQueueNumber): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS waiting_ahead
            FROM appointments
            WHERE doctor_id = ?
              AND DATE(created_at) = CURDATE()
              AND status IN ('waiting', 'called')
              AND queue_number < ?
        ");
        $stmt->execute([$doctorId, $currentQueueNumber]);
        $row = $stmt->fetch();
        $waitingAhead = (int)($row['waiting_ahead'] ?? 0);

        return ($waitingAhead + 1) * (int)$this->avgConsultationMinutes;
    }

    // ─── Status Transitions ──────────────────────────────────────────────────

    /**
     * Call next patient in queue for a given doctor.
     * Transitions: waiting -> called
     */
    public function callNext(int $doctorId): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name AS patient_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u ON p.user_id = u.id
            WHERE a.doctor_id = ?
              AND a.status = 'waiting'
              AND DATE(a.created_at) = CURDATE()
            ORDER BY a.priority DESC, a.queue_number ASC
            LIMIT 1
        ");
        $stmt->execute([$doctorId]);
        $appointment = $stmt->fetch();

        if (!$appointment) return null;

        $this->db->prepare("
            UPDATE appointments SET status = 'called', called_at = NOW() WHERE id = ?
        ")->execute([$appointment['id']]);

        return $appointment;
    }

    /**
     * Start consultation: called -> in_consultation
     */
    public function startConsultation(int $appointmentId): bool {
        return $this->db->prepare("
            UPDATE appointments SET status = 'in_consultation', started_at = NOW()
            WHERE id = ? AND status = 'called'
        ")->execute([$appointmentId]);
    }

    /**
     * Complete appointment: in_consultation -> done
     */
    public function completeAppointment(int $appointmentId): bool {
        return $this->db->prepare("
            UPDATE appointments SET status = 'done', completed_at = NOW()
            WHERE id = ? AND status = 'in_consultation'
        ")->execute([$appointmentId]);
    }

    /**
     * Cancel appointment.
     */
    public function cancelAppointment(int $appointmentId): bool {
        return $this->db->prepare("
            UPDATE appointments SET status = 'cancelled', updated_at = NOW()
            WHERE id = ? AND status IN ('waiting', 'called')
        ")->execute([$appointmentId]);
    }

    // ─── Queue List ──────────────────────────────────────────────────────────

    /**
     * Get today's active queue for a doctor (public & staff view).
     */
    public function getTodayQueue(int $doctorId): array {
        $stmt = $this->db->prepare("
            SELECT a.id, a.public_id, a.queue_number, a.priority, a.status,
                   a.called_at, a.started_at, a.complaint,
                   u.name AS patient_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u ON p.user_id = u.id
            WHERE a.doctor_id = ?
              AND DATE(a.created_at) = CURDATE()
              AND a.status NOT IN ('cancelled')
            ORDER BY a.priority DESC, a.queue_number ASC
        ");
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all active queues across all doctors (receptionist dashboard).
     */
    public function getAllActiveQueues(): array {
        $stmt = $this->db->prepare("
            SELECT a.id, a.public_id, a.queue_number, a.priority, a.status,
                   a.called_at, a.started_at, a.complaint,
                   u_patient.name AS patient_name,
                   u_doctor.name AS doctor_name,
                   doc.specialization
            FROM appointments a
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u_patient ON p.user_id = u_patient.id
            JOIN doctors doc ON a.doctor_id = doc.user_id
            JOIN users u_doctor ON doc.user_id = u_doctor.id
            WHERE DATE(a.created_at) = CURDATE()
              AND a.status IN ('waiting', 'called', 'in_consultation')
            ORDER BY a.priority DESC, a.queue_number ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Fetch public queue board data for all doctors operating today (have scheduled slots).
     */
    public function getPublicQueueBoardData(): array {
        $stmt = $this->db->prepare("
            SELECT DISTINCT d.user_id AS doctor_id, u.name AS doctor_name, d.specialization, ds.room
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            JOIN doctor_schedules ds ON d.user_id = ds.doctor_id
            WHERE u.is_active = 1 
              AND u.deleted_at IS NULL
              AND ds.is_active = 1
              AND (ds.schedule_date = CURDATE() OR (ds.schedule_date IS NULL AND ds.day_of_week = DAYNAME(CURDATE())))
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        $doctors = $stmt->fetchAll();

        // Fallback: If no doctors have specific schedules registered, fetch all active doctors
        if (empty($doctors)) {
            $stmt = $this->db->prepare("
                SELECT d.user_id AS doctor_id, u.name AS doctor_name, d.specialization, 'Loket Umum' AS room
                FROM doctors d
                JOIN users u ON d.user_id = u.id
                WHERE u.is_active = 1 AND u.deleted_at IS NULL
                ORDER BY u.name ASC
            ");
            $stmt->execute();
            $doctors = $stmt->fetchAll();
        }

        $boardData = [];
        foreach ($doctors as $doc) {
            // Get current active (called / in_consultation) appointment
            $stmtActive = $this->db->prepare("
                SELECT a.queue_number, u.name AS patient_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.user_id
                JOIN users u ON p.user_id = u.id
                WHERE a.doctor_id = ?
                  AND DATE(a.created_at) = CURDATE()
                  AND a.status IN ('called', 'in_consultation')
                ORDER BY a.started_at DESC, a.called_at DESC, a.id DESC
                LIMIT 1
            ");
            $stmtActive->execute([$doc['doctor_id']]);
            $activeApp = $stmtActive->fetch() ?: null;

            // Get next waiting appointment
            $stmtNext = $this->db->prepare("
                SELECT a.queue_number, u.name AS patient_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.user_id
                JOIN users u ON p.user_id = u.id
                WHERE a.doctor_id = ?
                  AND DATE(a.created_at) = CURDATE()
                  AND a.status = 'waiting'
                ORDER BY a.priority DESC, a.queue_number ASC
                LIMIT 1
            ");
            $stmtNext->execute([$doc['doctor_id']]);
            $nextApp = $stmtNext->fetch() ?: null;

            $boardData[] = [
                'doctor_id'      => $doc['doctor_id'],
                'doctor_name'    => $doc['doctor_name'],
                'specialization' => $doc['specialization'],
                'room'           => $doc['room'] ?? 'Loket Umum',
                'now_serving'    => $activeApp,
                'next_waiting'   => $nextApp
            ];
        }

        return $boardData;
    }
}
