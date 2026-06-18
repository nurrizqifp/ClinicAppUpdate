<?php
namespace App\Services;

use App\Database\Connection;
use App\App\Auth;
use PDO;

class MedicalRecordService {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getConnection();
    }

    // ─── Create EHR + Prescription ───────────────────────────────────────────

    /**
     * Saves a medical record and automatically creates prescriptions/items.
     *
     * @param  array  $prescriptionItems  Each: ['medicine_id', 'dosage', 'quantity']
     */
    public function createRecord(
        int    $appointmentId,
        int    $doctorId,
        string $diagnosis,
        string $notes,
        array  $prescriptionItems = []
    ): array {
        // Verify appointment exists and belongs to this doctor
        $stmt = $this->db->prepare("SELECT id FROM appointments WHERE id = ? AND doctor_id = ? LIMIT 1");
        $stmt->execute([$appointmentId, $doctorId]);
        if (!$stmt->fetch()) {
            throw new \RuntimeException("Appointment not found or access denied.");
        }

        // Prevent duplicate records
        $stmt = $this->db->prepare("SELECT id FROM medical_records WHERE appointment_id = ? LIMIT 1");
        $stmt->execute([$appointmentId]);
        if ($stmt->fetch()) {
            throw new \RuntimeException("A medical record already exists for this appointment.");
        }

        $this->db->beginTransaction();
        try {
            // 1. Insert medical record
            $this->db->prepare("
                INSERT INTO medical_records (appointment_id, doctor_id, diagnosis, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ")->execute([$appointmentId, $doctorId, $diagnosis, $notes]);
            $recordId = (int)$this->db->lastInsertId();

            // 2. Insert prescription header
            $this->db->prepare("
                INSERT INTO prescriptions (medical_record_id, status, created_at)
                VALUES (?, 'pending', NOW())
            ")->execute([$recordId]);
            $prescriptionId = (int)$this->db->lastInsertId();

            // 3. Insert prescription items
            foreach ($prescriptionItems as $item) {
                $this->db->prepare("
                    INSERT INTO prescription_items (prescription_id, medicine_id, dosage, quantity, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ")->execute([$prescriptionId, $item['medicine_id'], $item['dosage'], $item['quantity']]);
            }

            // 4. Transition appointment to in_consultation if still in waiting state
            $this->db->prepare("
                UPDATE appointments SET status = 'in_consultation', started_at = COALESCE(started_at, NOW()), updated_at = NOW()
                WHERE id = ? AND status NOT IN ('done', 'cancelled')
            ")->execute([$appointmentId]);

            $this->db->commit();
            Auth::logAudit('ehr.create', 'medical_records', $recordId);

            return [
                'record_id'       => $recordId,
                'prescription_id' => $prescriptionId,
                'items_count'     => count($prescriptionItems),
            ];
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ─── Fetch Records ───────────────────────────────────────────────────────

    public function getByAppointment(int $appointmentId): ?array {
        $stmt = $this->db->prepare("
            SELECT mr.*, u.name AS doctor_name, doc.specialization, a.patient_id, p_u.name AS patient_name
            FROM medical_records mr
            JOIN appointments a ON mr.appointment_id = a.id
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users p_u ON p.user_id = p_u.id
            JOIN doctors doc ON mr.doctor_id = doc.user_id
            JOIN users u ON doc.user_id = u.id
            WHERE mr.appointment_id = ?
            LIMIT 1
        ");
        $stmt->execute([$appointmentId]);
        $record = $stmt->fetch();
        if (!$record) return null;

        // Fetch linked prescriptions & items
        $record['prescription'] = $this->getPrescriptionByRecord($record['id']);
        return $record;
    }

    public function getPatientHistory(int $patientUserId): array {
        $stmt = $this->db->prepare("
            SELECT mr.*, a.queue_number, a.created_at AS visit_date,
                   u.name AS doctor_name, doc.specialization
            FROM medical_records mr
            JOIN appointments a ON mr.appointment_id = a.id
            JOIN doctors doc ON mr.doctor_id = doc.user_id
            JOIN users u ON doc.user_id = u.id
            WHERE a.patient_id = ?
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute([$patientUserId]);
        return $stmt->fetchAll();
    }

    // ─── Prescription Fetch ───────────────────────────────────────────────────

    public function getPrescriptionByRecord(int $recordId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM prescriptions WHERE medical_record_id = ? LIMIT 1");
        $stmt->execute([$recordId]);
        $prescription = $stmt->fetch();
        if (!$prescription) return null;

        // Fetch items
        $stmt2 = $this->db->prepare("
            SELECT pi.*, m.name AS medicine_name, m.unit
            FROM prescription_items pi
            JOIN medicines m ON pi.medicine_id = m.id
            WHERE pi.prescription_id = ?
        ");
        $stmt2->execute([$prescription['id']]);
        $prescription['items'] = $stmt2->fetchAll();
        return $prescription;
    }

    // ─── Doctor View ─────────────────────────────────────────────────────────

    public function getDoctorQueue(?int $doctorId): array {
        if ($doctorId !== null) {
            $stmt = $this->db->prepare("
                SELECT a.id, a.queue_number, a.priority, a.status, a.complaint,
                       u.name AS patient_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.user_id
                JOIN users u ON p.user_id = u.id
                WHERE a.doctor_id = ?
                  AND DATE(a.created_at) = CURDATE()
                  AND a.status NOT IN ('cancelled', 'done')
                ORDER BY a.priority DESC, a.queue_number ASC
            ");
            $stmt->execute([$doctorId]);
        } else {
            $stmt = $this->db->prepare("
                SELECT a.id, a.queue_number, a.priority, a.status, a.complaint,
                       u.name AS patient_name
                FROM appointments a
                JOIN patients p ON a.patient_id = p.user_id
                JOIN users u ON p.user_id = u.id
                WHERE DATE(a.created_at) = CURDATE()
                  AND a.status NOT IN ('cancelled', 'done')
                ORDER BY a.priority DESC, a.queue_number ASC
            ");
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
}
