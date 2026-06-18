<?php
use App\Services\PharmacyService;
use App\Services\InventoryService;
$pageTitle = 'Dashboard Apoteker';
$pharmacy  = new PharmacyService();
$inventory = new InventoryService();
$pending   = $pharmacy->getPendingPrescriptions();
$lowStock  = $inventory->getLowStockItems();
include BASE_PATH.'/src/Views/layout/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Dashboard Apoteker</h1>
        <p>Kelola dispensing resep dan pantau stok obat klinik.</p>
    </div>
    <div style="display:flex;gap:.625rem;">
        <a href="/index.php?page=inventory" class="btn btn-outline btn-sm">📦 Inventaris</a>
        <a href="/index.php?page=pharmacy" class="btn btn-primary btn-sm">💊 Semua Resep</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card <?= count($pending) > 0 ? 'amber' : 'emerald' ?>">
        <div class="stat-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg></div>
        <div class="stat-number"><?= count($pending) ?></div>
        <div class="stat-label">Resep Menunggu</div>
    </div>
    <div class="stat-card <?= count($lowStock) > 0 ? 'red' : 'emerald' ?>">
        <div class="stat-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
        <div class="stat-number"><?= count($lowStock) ?></div>
        <div class="stat-label">Stok Kritis</div>
    </div>
</div>

<?php if (!empty($lowStock)): ?>
<div class="alert alert-warning">
    <span class="alert-icon">⚠️</span>
    <div>
        <strong><?= count($lowStock) ?> obat di bawah stok minimum:</strong>
        <?= implode(', ', array_map(fn($m) => '<strong>'.htmlspecialchars($m['name']).'</strong> (tersisa: '.$m['stock'].')', $lowStock)) ?>
        — <a href="/index.php?page=inventory" style="color:inherit;font-weight:700;">Kelola sekarang →</a>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>
            Resep Menunggu Dispensing
        </span>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Antrian</th><th>Pasien</th><th>Dokter</th><th>Diagnosis</th><th>Masuk</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (empty($pending)): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <h3>Tidak ada resep menunggu</h3>
                        <p>Semua resep sudah diproses.</p>
                    </div>
                </td></tr>
            <?php else: foreach ($pending as $rx): ?>
                <tr>
                    <td><span class="queue-num queue-num-lg"><?= str_pad($rx['queue_number'],3,'0',STR_PAD_LEFT) ?></span></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($rx['patient_name']) ?></td>
                    <td>dr. <?= htmlspecialchars($rx['doctor_name']) ?></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-secondary);"><?= htmlspecialchars($rx['diagnosis']) ?></td>
                    <td style="font-family:'Fira Code',monospace;font-size:.8rem;color:var(--text-muted);"><?= date('H:i', strtotime($rx['created_at'])) ?></td>
                    <td><a href="/index.php?page=pharmacy&action=dispense&id=<?= $rx['prescription_id'] ?>" class="btn btn-primary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Proses
                    </a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
