<?php $pageTitle = 'Daftar Antrian Hari Ini'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Daftar Kunjungan &amp; Antrian Hari Ini</h1>
        <p>Pantau semua pasien terdaftar, status pelayanan, dan batalkan antrian jika diperlukan</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="/index.php?page=appointment" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Daftarkan Pasien Baru
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h3 class="card-title" style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Semua Antrian Aktif &amp; Terjadwal
        </h3>
        <form method="GET" action="/index.php" style="display: flex; gap: 0.5rem; align-items: center; margin: 0;">
            <input type="hidden" name="page" value="appointment">
            <input type="hidden" name="view" value="list">
            <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Cari Nomor Antrian (ex: A-001)..." style="padding: 0.45rem 0.75rem; border: 1px solid var(--border); border-radius: 6px; font-size: 0.85rem; width: 220px; outline: none; background: #fff; color: var(--text-primary);">
            <button type="submit" class="btn btn-navy btn-sm" style="padding: 0.45rem 1rem; font-size: 0.85rem;">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No. Antrian</th>
                    <th>Nama Pasien</th>
                    <th>Dokter / Poliklinik</th>
                    <th>Keluhan</th>
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($appointments)): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <h3>Belum Ada Antrian Pasien</h3>
                            <p>Tidak ada data pendaftaran antrian aktif hari ini.</p>
                        </div>
                    </td>
                </tr>
            <?php else: foreach ($appointments as $app): ?>
                <tr>
                    <td>
                        <span class="queue-num queue-num-lg" style="font-size: 1.15rem;">
                            <?= str_pad($app['queue_number'], 3, '0', STR_PAD_LEFT) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($app['patient_name']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 500; color: var(--text-primary);">dr. <?= htmlspecialchars($app['doctor_name']) ?></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($app['specialization']) ?></div>
                    </td>
                    <td>
                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-secondary);" title="<?= htmlspecialchars($app['complaint']) ?>">
                            <?= htmlspecialchars($app['complaint']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?= $app['priority'] ?>">
                            <?= $app['priority'] === 'emergency' ? 'Gawat Darurat' : 'Normal' ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $app['status'] ?>">
                            <?= match($app['status']) {
                                'waiting' => 'Menunggu',
                                'called' => 'Dipanggil',
                                'in_consultation' => 'Pemeriksaan',
                                'done' => 'Selesai',
                                'cancelled' => 'Batal',
                                default => $app['status']
                            } ?>
                        </span>
                    </td>
                    <td style="text-align: right;">
                        <?php if (in_array($app['status'], ['waiting', 'called'], true)): ?>
                            <form method="POST" action="/index.php?page=appointment&action=cancel" onsubmit="return confirm('Batalkan antrian ini?')" style="display: inline-block;">
                                <?= $csrfField ?>
                                <input type="hidden" name="appointment_id" value="<?= $app['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--red); border-color: rgba(239, 68, 68, 0.2);">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    Batalkan
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="font-size: 0.8rem; color: var(--text-muted);">Tidak ada aksi</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-refresh list every 30 seconds
setTimeout(() => location.reload(), 30000);
</script>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
