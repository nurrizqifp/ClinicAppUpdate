<?php $pageTitle = 'Antrian Saya'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Antrian Konsultasi Saya</h1>
        <p>Daftar pasien hari ini yang ditugaskan kepada Anda untuk pemeriksaan medis</p>
    </div>
    <button onclick="location.reload()" class="btn btn-navy">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
        Refresh Data
    </button>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Pasien Menunggu Konsultasi
        </h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No. Antrian</th>
                    <th>Nama Pasien</th>
                    <th>Keluhan Utama</th>
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($queue)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <h3>Tidak Ada Antrian Pasien</h3>
                            <p>Hari ini belum ada pasien dalam daftar antrian pemeriksaan Anda.</p>
                        </div>
                    </td>
                </tr>
            <?php else: foreach ($queue as $q): ?>
                <tr>
                    <td>
                        <span class="queue-num queue-num-lg" style="font-size: 1.25rem;">
                            <?= str_pad($q['queue_number'], 3, '0', STR_PAD_LEFT) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($q['patient_name']) ?></div>
                    </td>
                    <td>
                        <div style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-secondary); font-size: 0.875rem;" title="<?= htmlspecialchars($q['complaint']) ?>">
                            <?= htmlspecialchars($q['complaint']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $q['priority'] ?>">
                            <?= $q['priority'] === 'emergency' ? 'Gawat Darurat' : 'Normal' ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $q['status'] ?>">
                            <?= match($q['status']) {
                                'waiting' => 'Menunggu',
                                'called' => 'Dipanggil',
                                'in_consultation' => 'Pemeriksaan',
                                'done' => 'Selesai',
                                'cancelled' => 'Batal',
                                default => $q['status']
                            } ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <a href="/index.php?page=medical-record&action=create&appointment_id=<?= $q['id'] ?>" class="btn btn-primary btn-sm">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Tulis Rekam Medis
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-refresh every 20 seconds
setTimeout(() => location.reload(), 20000);
</script>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
