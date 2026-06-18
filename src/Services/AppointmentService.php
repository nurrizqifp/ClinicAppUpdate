<?php
namespace App\Services;

use App\Database\Connection;
use App\App\Auth;
use PDO;

class AppointmentService {
    private PDO $db;
    private QueueService $queueService;

    public function __construct() {
        $this->db = Connection::getConnection();
        $this->queueService = new QueueService();
    }

    // â”€â”€â”€ Create Appointment â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Book a new appointment / register to queue.
     * Returns the created appointment data or throws on error.
     */
    public function book(int $patientUserId, int $doctorId, string $complaint, string $priority = 'normal', ?int $scheduleId = null): array {
        $this->db->beginTransaction();
        try {
            // Generate unique public_id and queue_number
            $publicId    = \App\Utils\Security::uuid4();
            $queueNumber = $this->queueService->generateQueueNumber($doctorId);

            $stmt = $this->db->prepare("
                INSERT INTO appointments
                    (public_id, patient_id, doctor_id, schedule_id, queue_number, complaint, priority, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'waiting', NOW(), NOW())
            ");
            $stmt->execute([$publicId, $patientUserId, $doctorId, $scheduleId, $queueNumber, $complaint, $priority]);
            $appointmentId = (int)$this->db->lastInsertId();

            // Calculate EWT for immediate feedback
            $ewt = $this->queueService->calculateEWT($doctorId, $queueNumber);

            $this->db->commit();

            Auth::logAudit('appointment.book', 'appointments', $appointmentId);

            return [
                'id'           => $appointmentId,
                'public_id'    => $publicId,
                'queue_number' => $queueNumber,
                'ewt_minutes'  => $ewt,
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // â”€â”€â”€ Fetch Appointments â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, u_p.name AS patient_name, u_d.name AS doctor_name, doc.specialization
            FROM appointments a
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u_p ON p.user_id = u_p.id
            JOIN doctors doc ON a.doctor_id = doc.user_id
            JOIN users u_d ON doc.user_id = u_d.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByPublicId(string $publicId): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, u_p.name AS patient_name, u_d.name AS doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u_p ON p.user_id = u_p.id
            JOIN doctors doc ON a.doctor_id = doc.user_id
            JOIN users u_d ON doc.user_id = u_d.id
            WHERE a.public_id = ?
        ");
        $stmt->execute([$publicId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Find today's appointment by queue_number string (e.g. 'A-001').
     * Queue numbers are unique per day, so we scope the query to CURDATE()
     * to avoid returning stale records from previous days.
     */
    public function getByQueueNumber(string $queueNumber): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, u_p.name AS patient_name, u_d.name AS doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u_p ON p.user_id = u_p.id
            JOIN doctors doc ON a.doctor_id = doc.user_id
            JOIN users u_d ON doc.user_id = u_d.id
            WHERE a.queue_number = ?
              AND DATE(a.created_at) = CURDATE()
            LIMIT 1
        ");
        $stmt->execute([strtoupper(trim($queueNumber))]);
        return $stmt->fetch() ?: null;
    }
    public function getPatientHistory(int $patientUserId, int $limit = 20): array {
        $stmt = $this->db->prepare("
            SELECT a.*, u_d.name AS doctor_name, doc.specialization
            FROM appointments a
            JOIN doctors doc ON a.doctor_id = doc.user_id
            JOIN users u_d ON doc.user_id = u_d.id
            WHERE a.patient_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$patientUserId, $limit]);
        return $stmt->fetchAll();
    }

    public function getAllToday(?string $search = null): array {
        $query = "
            SELECT a.*, u_p.name AS patient_name, u_d.name AS doctor_name, doc.specialization
            FROM appointments a
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u_p ON p.user_id = u_p.id
            JOIN doctors doc ON a.doctor_id = doc.user_id
            JOIN users u_d ON doc.user_id = u_d.id
            WHERE DATE(a.created_at) = CURDATE()
        ";
        
        $params = [];
        if (!empty($search)) {
            $searchInt = (int)preg_replace('/[^0-9]/', '', $search);
            if ($searchInt > 0) {
                $query .= " AND a.queue_number = ?";
                $params[] = $searchInt;
            }
        }
        
        $query .= " ORDER BY a.priority DESC, a.queue_number ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // â”€â”€â”€ Cancel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function cancel(int $appointmentId, int $requestingUserId, string $requestingRole): bool {
        $appointment = $this->getById($appointmentId);
        if (!$appointment) return false;

        // Patients may only cancel their own appointments
        if ($requestingRole === 'patient' && $appointment['patient_id'] !== $requestingUserId) {
            return false;
        }

        $result = $this->queueService->cancelAppointment($appointmentId);
        if ($result) {
            Auth::logAudit('appointment.cancel', 'appointments', $appointmentId);
        }
        return $result;
    }

    // â”€â”€â”€ Doctors List â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function getAvailableDoctors(): array {
        $stmt = $this->db->prepare("
            SELECT d.user_id, u.name, d.specialization, d.bio
            FROM doctors d
            JOIN users u ON d.user_id = u.id
            WHERE u.is_active = 1 AND u.deleted_at IS NULL
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
