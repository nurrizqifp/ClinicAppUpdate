<?php $pageTitle = 'Papan Antrian'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Papan Antrian</h1>
        <p>Kelola alur antrian pasien klinik, pemanggilan, dan status konsultasi secara real-time</p>
    </div>
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="/index.php?page=public-queue" target="_blank" class="btn btn-outline">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            Papan Publik (TV)
        </a>
        <button onclick="location.reload()" class="btn btn-navy">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
            Refresh Data
        </button>
    </div>
</div>

<!-- Call Next Panel -->
<?php if (\App\App\Auth::hasRole(['receptionist','admin'])): ?>
<?php $doctors = (new \App\Services\AppointmentService())->getAvailableDoctors(); ?>
<div class="card" style="margin-bottom: 1.5rem; border-color: rgba(5, 150, 105, 0.2); background: linear-gradient(to right, #ffffff, rgba(5, 150, 105, 0.02));">
    <div class="card-header" style="border-bottom: none; padding-bottom: 0.25rem;">
        <h3 class="card-title" style="color: var(--emerald);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v1a7 7 0 0 1-14 0v-1"/><line x1="12" y1="19" x2="12" y2="22"/></svg>
            Panggil Pasien Berikutnya
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="/index.php?page=queue&action=call" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <?= $csrfField ?>
            <div class="form-group" style="margin: 0; flex: 1; min-width: 250px;">
                <label class="form-label" for="doctor_id">Dokter Tujuan &amp; Poliklinik</label>
                <select name="doctor_id" id="doctor_id" required>
                    <option value="">-- Pilih Dokter Terbuka --</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['user_id'] ?>"><?= htmlspecialchars($doc['name']) ?> — <?= htmlspecialchars($doc['specialization']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 40px; padding: 0 1.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 5L6 9H2v6h4l5 4V5z"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                Panggil Antrian
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            Daftar Antrian Hari Ini
        </h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No. Antrian</th>
                    <th>Nama Pasien</th>
                    <th>Dokter Spesialis</th>
                    <th>Prioritas</th>
                    <th>Status</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($queues)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <h3>Tidak Ada Antrian Aktif</h3>
                            <p>Saat ini belum ada data antrian pasien aktif di poliklinik.</p>
                        </div>
                    </td>
                </tr>
            <?php else: foreach ($queues as $q): ?>
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
                        <div style="font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($q['doctor_name']) ?></div>
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
                        <div style="display: inline-flex; gap: 0.5rem; justify-content: flex-end;">
                            <?php if ($q['status'] === 'called'): ?>
                            <form method="POST" action="/index.php?page=queue&action=start" style="display: inline;">
                                <?= $csrfField ?>
                                <input type="hidden" name="appointment_id" value="<?= $q['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                    Mulai Konsultasi
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($q['status'] === 'in_consultation'): ?>
                            <form method="POST" action="/index.php?page=queue&action=complete" style="display: inline;">
                                <?= $csrfField ?>
                                <input type="hidden" name="appointment_id" value="<?= $q['id'] ?>">
                                <button type="submit" class="btn btn-navy btn-sm">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    Selesai
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-refresh every 15 seconds to sync queue boards
setTimeout(() => location.reload(), 15000);
</script>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
