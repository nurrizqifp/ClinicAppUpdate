<?php $pageTitle = 'Riwayat Kunjungan'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Riwayat Kunjungan Saya</h1>
        <p>Lihat daftar semua pemeriksaan medis dan riwayat antrian Anda sebelumnya</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.75"/></svg>
            Catatan Kunjungan Terdahulu
        </h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal &amp; Waktu</th>
                    <th>Dokter / Poliklinik</th>
                    <th>Spesialisasi</th>
                    <th>Keluhan Utama</th>
                    <th>No. Antrian</th>
                    <th>Status Akhir</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($history)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.75"/></svg>
                            <h3>Belum Ada Riwayat Kunjungan</h3>
                            <p>Anda belum pernah melakukan pendaftaran antrian atau kunjungan di klinik ini.</p>
                        </div>
                    </td>
                </tr>
            <?php else: foreach ($history as $h): ?>
                <tr>
                    <td>
                        <span style="font-family: 'Fira Code', monospace; font-size: 0.825rem; font-weight: 500;">
                            <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-primary);">dr. <?= htmlspecialchars($h['doctor_name']) ?></div>
                    </td>
                    <td>
                        <span style="color: var(--text-secondary);"><?= htmlspecialchars($h['specialization']) ?></span>
                    </td>
                    <td>
                        <div style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-muted);" title="<?= htmlspecialchars($h['complaint']) ?>">
                            <?= htmlspecialchars($h['complaint']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="queue-num" style="font-size: 0.95rem;">
                            <?= str_pad($h['queue_number'], 3, '0', STR_PAD_LEFT) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $h['status'] ?>">
                            <?= match($h['status']) {
                                'waiting' => 'Menunggu',
                                'called' => 'Dipanggil',
                                'in_consultation' => 'Pemeriksaan',
                                'done' => 'Selesai',
                                'cancelled' => 'Batal',
                                default => $h['status']
                            } ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
