<?php
use App\Services\MedicalRecordService;
use App\App\Auth;
$pageTitle = 'Dashboard Dokter';
$service   = new MedicalRecordService();
$queue     = $service->getDoctorQueue(Auth::id());
$waiting   = count(array_filter($queue, fn($q) => $q['status'] === 'waiting'));
$inConsult = count(array_filter($queue, fn($q) => $q['status'] === 'in_consultation'));
include BASE_PATH.'/src/Views/layout/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Dashboard Dokter</h1>
        <p>Selamat datang, dr. <?= htmlspecialchars(Auth::user()['name']) ?>. Ini antrian konsultasi Anda hari ini.</p>
    </div>
    <a href="/index.php?page=queue&action=my" class="btn btn-primary btn-sm">🔄 Refresh Antrian</a>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="stat-number"><?= count($queue) ?></div>
        <div class="stat-label">Total Antrian Hari Ini</div>
    </div>
    <div class="stat-card amber">
        <div class="stat-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="stat-number"><?= $waiting ?></div>
        <div class="stat-label">Menunggu</div>
    </div>
    <div class="stat-card emerald">
        <div class="stat-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        <div class="stat-number"><?= $inConsult ?></div>
        <div class="stat-label">Sedang Konsultasi</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/></svg>
            Daftar Pasien Hari Ini
        </span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>No.</th><th>Nama Pasien</th><th>Keluhan</th><th>Prioritas</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (empty($queue)): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        <h3>Tidak ada antrian</h3>
                        <p>Belum ada pasien yang mendaftar untuk hari ini.</p>
                    </div>
                </td></tr>
            <?php else: foreach ($queue as $item): ?>
                <tr>
                    <td><span class="queue-num queue-num-lg"><?= str_pad($item['queue_number'],3,'0',STR_PAD_LEFT) ?></span></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($item['patient_name']) ?></td>
                    <td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-secondary);"><?= htmlspecialchars($item['complaint']) ?></td>
                    <td><span class="badge badge-<?= $item['priority'] ?>"><?= $item['priority'] ?></span></td>
                    <td><span class="badge badge-<?= $item['status'] ?>"><?= str_replace('_',' ',$item['status']) ?></span></td>
                    <td>
                        <a href="/index.php?page=medical-record&action=create&appointment_id=<?= $item['id'] ?>" class="btn btn-primary btn-sm">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Rekam Medis
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
