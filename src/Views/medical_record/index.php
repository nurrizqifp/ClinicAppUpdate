<?php $pageTitle = 'Kelola Rekam Medis'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Daftar Konsultasi Pasien Saya</h1>
        <p>Pilih pasien dari antrian hari ini untuk memulai pemeriksaan medis dan mencatat rekam medis</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Antrian Pasien Hari Ini
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
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <h3>Antrian Kosong</h3>
                            <p>Tidak ada pasien yang mengantri untuk Anda saat ini.</p>
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
                        <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-secondary);" title="<?= htmlspecialchars($q['complaint']) ?>">
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
                        <?php if ($q['status'] === 'in_consultation'): ?>
                            <a href="/index.php?page=medical-record&action=create&appointment_id=<?= $q['id'] ?>" class="btn btn-primary btn-sm">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Isi Rekam Medis
                            </a>
                        <?php elseif ($q['status'] === 'done'): ?>
                            <a href="/index.php?page=medical-record&action=view&appointment_id=<?= $q['id'] ?>" class="btn btn-outline btn-sm">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                Lihat Rekam Medis
                            </a>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Menunggu Dipanggil</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-refresh queue list every 30 seconds
setTimeout(() => location.reload(), 30000);
</script>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
