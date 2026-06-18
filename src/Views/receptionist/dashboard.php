<?php
use App\Services\QueueService;
use App\Services\AppointmentService;
$pageTitle    = 'Dashboard Resepsionis';
$queueService = new QueueService();
$allQueues    = $queueService->getAllActiveQueues();
$doctors      = (new AppointmentService())->getAvailableDoctors();
$waiting      = count(array_filter($allQueues, fn($q) => $q['status'] === 'waiting'));
$called       = count(array_filter($allQueues, fn($q) => $q['status'] === 'called'));
include BASE_PATH.'/src/Views/layout/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Dashboard Resepsionis</h1>
        <p>Monitor dan kelola antrian pasien secara real-time.</p>
    </div>
    <a href="/index.php?page=public-queue" target="_blank" class="btn btn-outline btn-sm">📺 Buka Papan Publik</a>
</div>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg></div>
        <div class="stat-number"><?= count($allQueues) ?></div>
        <div class="stat-label">Antrian Aktif</div>
    </div>
    <div class="stat-card amber">
        <div class="stat-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div class="stat-number"><?= $waiting ?></div>
        <div class="stat-label">Menunggu Panggilan</div>
    </div>
    <div class="stat-card emerald">
        <div class="stat-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/></svg></div>
        <div class="stat-number"><?= $called ?></div>
        <div class="stat-label">Sudah Dipanggil</div>
    </div>
</div>

<!-- Call Next Panel -->
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-header">
        <span class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
            Panggil Pasien Berikutnya
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="/index.php?page=queue&action=call" style="display:flex;gap:.875rem;align-items:flex-end;flex-wrap:wrap;">
            <?= \App\Utils\Security::csrfField() ?>
            <div class="form-group" style="margin:0;flex:1;min-width:220px;">
                <label class="form-label">Pilih Dokter</label>
                <select name="doctor_id" required>
                    <option value="">-- Pilih Dokter --</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['user_id'] ?>">dr. <?= htmlspecialchars($doc['name']) ?> — <?= htmlspecialchars($doc['specialization']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="flex-shrink:0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                Panggil Sekarang
            </button>
        </form>
    </div>
</div>

<!-- Queue Board -->
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Papan Antrian Aktif
        </span>
        <button onclick="location.reload()" class="btn btn-ghost btn-sm">🔄 Refresh</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>No.</th><th>Pasien</th><th>Dokter</th><th>Spesialisasi</th><th>Prioritas</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($allQueues)): ?>
                <tr><td colspan="6">
                    <div class="empty-state"><h3>Tidak ada antrian aktif</h3><p>Semua pasien sudah terlayani atau belum ada yang mendaftar.</p></div>
                </td></tr>
            <?php else: foreach ($allQueues as $q): ?>
                <tr>
                    <td><span class="queue-num queue-num-lg"><?= str_pad($q['queue_number'],3,'0',STR_PAD_LEFT) ?></span></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($q['patient_name']) ?></td>
                    <td>dr. <?= htmlspecialchars($q['doctor_name']) ?></td>
                    <td style="color:var(--text-secondary);font-size:.82rem;"><?= htmlspecialchars($q['specialization']) ?></td>
                    <td><span class="badge badge-<?= $q['priority'] ?>"><?= $q['priority'] ?></span></td>
                    <td><span class="badge badge-<?= $q['status'] ?>"><?= str_replace('_',' ',$q['status']) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>setTimeout(() => location.reload(), 30000);</script>
<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
