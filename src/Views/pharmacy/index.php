<?php $pageTitle = 'Antrian Resep'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Antrian Resep</h1>
        <p><?= count($pending) ?> resep menunggu dispensing<?= !empty($lowStock) ? ' · '.count($lowStock).' obat stok kritis' : '' ?></p>
    </div>
    <a href="/index.php?page=inventory" class="btn btn-outline btn-sm">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Inventaris
    </a>
</div>

<?php if (!empty($lowStock)): ?>
<div class="alert alert-warning">
    <span class="alert-icon">⚠️</span>
    <div><strong><?= count($lowStock) ?> obat kritis:</strong>
    <?= implode(', ', array_map(fn($m) => htmlspecialchars($m['name']).' (sisa '.$m['stock'].')', $lowStock)) ?>
    — <a href="/index.php?page=inventory" style="color:inherit;font-weight:700;">Kelola →</a></div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>
            Resep Menunggu
        </span>
        <button onclick="location.reload()" class="btn btn-ghost btn-sm">🔄 Refresh</button>
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
                        <p>Semua resep sudah berhasil diproses.</p>
                    </div>
                </td></tr>
            <?php else: foreach ($pending as $rx): ?>
                <tr>
                    <td><span class="queue-num queue-num-lg"><?= str_pad($rx['queue_number'],3,'0',STR_PAD_LEFT) ?></span></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($rx['patient_name']) ?></td>
                    <td>dr. <?= htmlspecialchars($rx['doctor_name']) ?></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-secondary);"><?= htmlspecialchars($rx['diagnosis']) ?></td>
                    <td style="font-family:'Fira Code',monospace;font-size:.8rem;color:var(--text-muted);"><?= date('H:i', strtotime($rx['created_at'])) ?></td>
                    <td>
                        <a href="/index.php?page=pharmacy&action=dispense&id=<?= $rx['prescription_id'] ?>" class="btn btn-primary btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                            Proses
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>setTimeout(() => location.reload(), 30000);</script>
<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
