<?php
namespace App\Services;

use App\Database\Connection;
use App\App\Auth;
use PDO;

class PharmacyService {
    private PDO $db;
    private InventoryService $inventoryService;

    public function __construct() {
        $this->db = Connection::getConnection();
        $this->inventoryService = new InventoryService();
    }

    // ─── Pending Prescriptions ────────────────────────────────────────────────

    /**
     * Get all prescriptions with status 'pending' for the pharmacy queue.
     */
    public function getPendingPrescriptions(): array {
        $stmt = $this->db->prepare("
            SELECT pr.id AS prescription_id, pr.status, pr.created_at,
                   mr.diagnosis,
                   a.queue_number, a.id AS appointment_id,
                   u_p.name AS patient_name, u_d.name AS doctor_name
            FROM prescriptions pr
            JOIN medical_records mr ON pr.medical_record_id = mr.id
            JOIN appointments a ON mr.appointment_id = a.id
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u_p ON p.user_id = u_p.id
            JOIN doctors doc ON mr.doctor_id = doc.user_id
            JOIN users u_d ON doc.user_id = u_d.id
            WHERE pr.status = 'pending'
            ORDER BY pr.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get prescription detail with all items.
     */
    public function getPrescriptionDetail(int $prescriptionId): ?array {
        $stmt = $this->db->prepare("
            SELECT pr.*, mr.diagnosis, mr.notes,
                   a.queue_number, u_p.name AS patient_name, u_d.name AS doctor_name
            FROM prescriptions pr
            JOIN medical_records mr ON pr.medical_record_id = mr.id
            JOIN appointments a ON mr.appointment_id = a.id
            JOIN patients p ON a.patient_id = p.user_id
            JOIN users u_p ON p.user_id = u_p.id
            JOIN doctors doc ON mr.doctor_id = doc.user_id
            JOIN users u_d ON doc.user_id = u_d.id
            WHERE pr.id = ?
            LIMIT 1
        ");
        $stmt->execute([$prescriptionId]);
        $prescription = $stmt->fetch();
        if (!$prescription) return null;

        // Fetch items
        $stmt2 = $this->db->prepare("
            SELECT pi.id, pi.dosage, pi.quantity,
                   m.id AS medicine_id, m.name AS medicine_name, m.unit, m.stock
            FROM prescription_items pi
            JOIN medicines m ON pi.medicine_id = m.id
            WHERE pi.prescription_id = ?
        ");
        $stmt2->execute([$prescriptionId]);
        $prescription['items'] = $stmt2->fetchAll();

        return $prescription;
    }

    // ─── Dispense ─────────────────────────────────────────────────────────────

    /**
     * Dispense all items in a prescription.
     * Deducts stock for each item and marks the prescription as dispensed.
     *
     * Returns array with low_stock_alerts for any item that drops below minimum.
     */
    public function dispense(int $prescriptionId, int $apotekerUserId): array {
        $prescription = $this->getPrescriptionDetail($prescriptionId);
        if (!$prescription) {
            throw new \RuntimeException("Prescription not found.");
        }
        if ($prescription['status'] === 'dispensed') {
            throw new \RuntimeException("Prescription has already been dispensed.");
        }

        // Pre-validate all stock levels before touching anything
        foreach ($prescription['items'] as $item) {
            if ((int)$item['stock'] < (int)$item['quantity']) {
                throw new \RuntimeException(
                    "Insufficient stock for '{$item['medicine_name']}'. Available: {$item['stock']}, needed: {$item['quantity']}."
                );
            }
        }

        $this->db->beginTransaction();
        try {
            $lowStockAlerts = [];

            foreach ($prescription['items'] as $item) {
                // Deduct stock via InventoryService (logs internally)
                $this->inventoryService->deductStock(
                    $item['medicine_id'],
                    $item['quantity'],
                    "Dispensed via prescription #{$prescriptionId}"
                );

                // Check if now below minimum
                $med = $this->inventoryService->find($item['medicine_id']);
                if ($med && (int)$med['stock'] < (int)$med['minimum_stock']) {
                    $lowStockAlerts[] = [
                        'medicine_id'   => $item['medicine_id'],
                        'medicine_name' => $item['medicine_name'],
                        'stock'         => $med['stock'],
                        'minimum_stock' => $med['minimum_stock'],
                    ];
                }
            }

            // Mark prescription as dispensed
            $this->db->prepare("
                UPDATE prescriptions
                SET status = 'dispensed', dispensed_by = ?, dispensed_at = NOW()
                WHERE id = ?
            ")->execute([$apotekerUserId, $prescriptionId]);

            // Transition appointment to done
            $this->db->prepare("
                UPDATE appointments
                SET status = 'done', completed_at = NOW(), updated_at = NOW()
                WHERE id = ? AND status NOT IN ('cancelled')
            ")->execute([$prescription['appointment_id']]);

            $this->db->commit();
            Auth::logAudit('pharmacy.dispense', 'prescriptions', $prescriptionId);

            return ['low_stock_alerts' => $lowStockAlerts];

        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
