<?php
namespace App\Services;

use App\Database\Connection;
use App\App\Auth;
use PDO;

class InventoryService {
    private PDO $db;

    public function __construct() {
        $this->db = Connection::getConnection();
    }

    // ─── Stock Operations ─────────────────────────────────────────────────────

    /**
     * Add stock (type: 'in') — used when receiving new medicine supplies.
     */
    public function addStock(int $medicineId, int $quantity, string $note = ''): bool {
        return $this->changeStock($medicineId, 'in', abs($quantity), $note);
    }

    /**
     * Reduce stock (type: 'out') — used during dispensing.
     * Validates stock availability before deducting.
     */
    public function deductStock(int $medicineId, int $quantity, string $note = ''): bool {
        $medicine = $this->find($medicineId);
        if (!$medicine) throw new \RuntimeException("Medicine not found.");

        if ($medicine['stock'] < $quantity) {
            throw new \RuntimeException("Insufficient stock. Available: {$medicine['stock']}, requested: {$quantity}.");
        }

        return $this->changeStock($medicineId, 'out', $quantity, $note);
    }

    /**
     * Adjust stock to a specific absolute value (type: 'adjustment').
     */
    public function adjustStock(int $medicineId, int $newStockValue, string $note = ''): bool {
        $medicine = $this->find($medicineId);
        if (!$medicine) throw new \RuntimeException("Medicine not found.");

        $delta = $newStockValue - (int)$medicine['stock'];
        return $this->changeStock($medicineId, 'adjustment', $delta, $note);
    }

    private function changeStock(int $medicineId, string $changeType, int $quantityChange, string $note): bool {
        $performedBy = Auth::id();

        $this->db->beginTransaction();
        try {
            // Update medicine stock
            if ($changeType === 'out') {
                $this->db->prepare("UPDATE medicines SET stock = stock - ?, updated_at = NOW() WHERE id = ?")->execute([abs($quantityChange), $medicineId]);
            } elseif ($changeType === 'in') {
                $this->db->prepare("UPDATE medicines SET stock = stock + ?, updated_at = NOW() WHERE id = ?")->execute([abs($quantityChange), $medicineId]);
            } else {
                // adjustment: quantityChange is a delta (can be negative)
                $this->db->prepare("UPDATE medicines SET stock = stock + ?, updated_at = NOW() WHERE id = ?")->execute([$quantityChange, $medicineId]);
            }

            // Insert log (insert-only table)
            $this->db->prepare("
                INSERT INTO medicine_stock_logs (medicine_id, change_type, quantity_change, reference_note, performed_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ")->execute([$medicineId, $changeType, $quantityChange, $note, $performedBy]);

            $this->db->commit();
            Auth::logAudit("inventory.{$changeType}", 'medicines', $medicineId);

            // Check low stock and return flag
            $this->checkLowStock($medicineId);
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Emit low stock flag by logging to audit if stock drops below minimum.
     */
    private function checkLowStock(int $medicineId): void {
        $stmt = $this->db->prepare("SELECT name, stock, minimum_stock FROM medicines WHERE id = ?");
        $stmt->execute([$medicineId]);
        $med = $stmt->fetch();
        if ($med && (int)$med['stock'] < (int)$med['minimum_stock']) {
            \App\Logger::info("[LOW_STOCK] Medicine '{$med['name']}' (id={$medicineId}) is below minimum. Stock: {$med['stock']}, Min: {$med['minimum_stock']}");
            // Flag is also queryable via getLowStockItems()
        }
    }

    // ─── CRUD Medicines ──────────────────────────────────────────────────────

    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM medicines WHERE deleted_at IS NULL ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM medicines WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $this->db->prepare("
            INSERT INTO medicines (name, unit, stock, minimum_stock, price, created_at, updated_at)
            VALUES (:name, :unit, :stock, :minimum_stock, :price, NOW(), NOW())
        ")->execute($data);
        $id = (int)$this->db->lastInsertId();
        Auth::logAudit('inventory.create', 'medicines', $id);
        return $id;
    }

    public function update(int $id, array $data): bool {
        $data['id'] = $id;
        $result = $this->db->prepare("
            UPDATE medicines SET name = :name, unit = :unit, minimum_stock = :minimum_stock, price = :price, updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ")->execute($data);
        if ($result) Auth::logAudit('inventory.update', 'medicines', $id);
        return $result;
    }

    public function softDelete(int $id): bool {
        $result = $this->db->prepare("UPDATE medicines SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL")->execute([$id]);
        if ($result) Auth::logAudit('inventory.delete', 'medicines', $id);
        return $result;
    }

    // ─── Low Stock Alert ─────────────────────────────────────────────────────

    public function getLowStockItems(): array {
        $stmt = $this->db->query("
            SELECT id, name, unit, stock, minimum_stock
            FROM medicines
            WHERE stock < minimum_stock AND deleted_at IS NULL
            ORDER BY (minimum_stock - stock) DESC
        ");
        return $stmt->fetchAll();
    }
}
