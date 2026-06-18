<?php
namespace App\Controllers;

use App\App\Auth;
use App\Services\InventoryService;
use App\Services\PharmacyService;
use App\Utils\Validator;
use App\Utils\Security;

class InventoryController extends BaseController {

    private InventoryService $inventoryService;
    private PharmacyService  $pharmacyService;

    public function __construct() {
        $this->inventoryService = new InventoryService();
        $this->pharmacyService  = new PharmacyService();
    }

    // ─── Inventory List ───────────────────────────────────────────────────────

    /**
     * GET  ?page=inventory  → Full stock list for apoteker/admin
     */
    public function index(): void {
        $this->requireRole(['apoteker', 'admin']);
        $medicines    = $this->inventoryService->all();
        $lowStockItems = $this->inventoryService->getLowStockItems();

        $this->view('inventory.index', [
            'medicines'     => $medicines,
            'lowStockItems' => $lowStockItems,
            'user'          => Auth::user(),
        ]);
    }

    // ─── Create Medicine ──────────────────────────────────────────────────────

    /**
     * GET  ?page=inventory&action=create
     * POST ?page=inventory&action=create
     */
    public function create(): void {
        $this->requireRole(['admin']);

        if ($this->isPost()) {
            Security::verifyCsrfOrFail();
            $data = [
                'name'          => $this->inputStr('name'),
                'unit'          => $this->inputStr('unit'),
                'stock'         => $this->inputInt('stock'),
                'minimum_stock' => $this->inputInt('minimum_stock'),
                'price'         => filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            ];

            $validator = Validator::make($data, [
                'name'          => 'required|min:2|max:255',
                'unit'          => 'required|min:1|max:50',
                'stock'         => 'required|numeric|min:0',
                'minimum_stock' => 'required|numeric|min:0',
                'price'         => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                $this->redirect('inventory', ['action' => 'create', 'error' => urlencode($validator->firstError())]);
                return;
            }

            try {
                $this->inventoryService->create($data);
                $this->redirect('inventory', ['success' => urlencode('Obat berhasil ditambahkan.')]);
            } catch (\Throwable $e) {
                \App\Logger::error("Inventory create error: " . $e->getMessage());
                $this->redirect('inventory', ['action' => 'create', 'error' => urlencode('Gagal menambah obat.')]);
            }
            return;
        }

        $this->view('inventory.create', ['user' => Auth::user()]);
    }

    // ─── Update Medicine ──────────────────────────────────────────────────────

    /**
     * POST ?page=inventory&action=update
     */
    public function update(): void {
        $this->requireRole(['admin']);
        Security::verifyCsrfOrFail();

        $id   = $this->inputInt('id');
        $data = [
            'name'          => $this->inputStr('name'),
            'unit'          => $this->inputStr('unit'),
            'minimum_stock' => $this->inputInt('minimum_stock'),
            'price'         => filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        ];

        try {
            $this->inventoryService->update($id, $data);
            $this->redirect('inventory', ['success' => urlencode('Data obat berhasil diperbarui.')]);
        } catch (\Throwable $e) {
            $this->redirect('inventory', ['error' => urlencode($e->getMessage())]);
        }
    }

    // ─── Adjust Stock ─────────────────────────────────────────────────────────

    /**
     * POST ?page=inventory&action=stock
     * type: 'in' | 'out' | 'adjustment'
     */
    public function stock(): void {
        $this->requireRole(['apoteker', 'admin']);
        Security::verifyCsrfOrFail();

        $id       = $this->inputInt('medicine_id');
        $type     = $this->inputStr('type');
        $quantity = $this->inputInt('quantity');
        $note     = $this->inputStr('note');

        try {
            match($type) {
                'in'         => $this->inventoryService->addStock($id, $quantity, $note),
                'out'        => $this->inventoryService->deductStock($id, $quantity, $note),
                'adjustment' => $this->inventoryService->adjustStock($id, $quantity, $note),
                default      => throw new \InvalidArgumentException("Tipe stok tidak valid.")
            };
            $this->redirect('inventory', ['success' => urlencode('Stok berhasil diperbarui.')]);
        } catch (\Throwable $e) {
            \App\Logger::error("Stock update error: " . $e->getMessage());
            $this->redirect('inventory', ['error' => urlencode($e->getMessage())]);
        }
    }

    // ─── Delete ───────────────────────────────────────────────────────────────

    /**
     * POST ?page=inventory&action=delete
     */
    public function delete(): void {
        $this->requireRole(['admin']);
        Security::verifyCsrfOrFail();

        $id = $this->inputInt('id');
        try {
            $this->inventoryService->softDelete($id);
            $this->redirect('inventory', ['success' => urlencode('Obat berhasil dihapus.')]);
        } catch (\Throwable $e) {
            $this->redirect('inventory', ['error' => urlencode($e->getMessage())]);
        }
    }

    // ─── Pharmacy: Pending Prescriptions ─────────────────────────────────────

    /**
     * GET  ?page=pharmacy  → List of pending prescriptions for dispensing
     */
    public function pharmacy(): void {
        $this->requireRole(['apoteker', 'admin']);
        $pending  = $this->pharmacyService->getPendingPrescriptions();
        $lowStock = $this->inventoryService->getLowStockItems();

        $this->view('pharmacy.index', [
            'pending'  => $pending,
            'lowStock' => $lowStock,
            'user'     => Auth::user(),
        ]);
    }

    // ─── Pharmacy: Dispense ───────────────────────────────────────────────────

    /**
     * GET  ?page=pharmacy&action=dispense&id=X  → Show prescription detail
     * POST ?page=pharmacy&action=dispense        → Confirm dispense
     */
    public function dispense(): void {
        $this->requireRole(['apoteker', 'admin']);

        $prescriptionId = $this->inputInt('id');

        if ($this->isPost()) {
            Security::verifyCsrfOrFail();
            try {
                $result = $this->pharmacyService->dispense($prescriptionId, Auth::id());
                $alerts = count($result['low_stock_alerts']);
                $msg    = 'Obat berhasil diberikan.' . ($alerts > 0 ? " Peringatan: {$alerts} obat di bawah stok minimum." : '');
                $this->redirect('pharmacy', ['success' => urlencode($msg)]);
            } catch (\Throwable $e) {
                \App\Logger::error("Dispense error: " . $e->getMessage());
                $this->redirect('pharmacy', ['error' => urlencode($e->getMessage())]);
            }
            return;
        }

        // GET: show detail
        $prescription = $this->pharmacyService->getPrescriptionDetail($prescriptionId);
        if (!$prescription) {
            $this->redirect('pharmacy', ['error' => urlencode('Resep tidak ditemukan.')]);
        }

        $this->view('pharmacy.dispense', [
            'prescription' => $prescription,
            'user'         => Auth::user(),
        ]);
    }
}
