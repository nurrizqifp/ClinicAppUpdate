<?php
use App\Services\AppointmentService;
use App\App\Auth;
$pageTitle = 'Dashboard Pasien';
$service   = new AppointmentService();
$history   = $service->getPatientHistory(Auth::id(), 5);
$doctors   = $service->getAvailableDoctors();
include BASE_PATH.'/src/Views/layout/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Halo, <?= htmlspecialchars(Auth::user()['name']) ?> 👋</h1>
        <p>Selamat datang di Klinik Verdana. Daftar antrian atau pantau status kunjungan Anda.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:1.25rem;margin-bottom:1.5rem;align-items:start;">

    <!-- Book Appointment -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Daftar Antrian Baru
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="/index.php?page=appointment">
                <?= \App\Utils\Security::csrfField() ?>
                <div class="form-group">
                    <label class="form-label">Pilih Dokter <span>*</span></label>
                    <select name="doctor_id" required>
                        <option value="">-- Pilih Dokter --</option>
                        <?php foreach ($doctors as $doc): ?>
                            <option value="<?= $doc['user_id'] ?>">dr. <?= htmlspecialchars($doc['name']) ?> — <?= htmlspecialchars($doc['specialization']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Keluhan <span>*</span></label>
                    <textarea name="complaint" required placeholder="Deskripsikan keluhan Anda secara singkat..." rows="4"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Daftar Antrian
                </button>
            </form>
        </div>
    </div>

    <!-- Status & Info -->
    <div style="display:flex;flex-direction:column;gap:1rem;">
        <!-- Check Status -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Cek Status Antrian
                </span>
            </div>
            <div class="card-body">
                <p style="font-size:.845rem;color:var(--text-secondary);margin-bottom:.875rem;">Masukkan ID Antrian yang Anda terima saat mendaftar.</p>
                <form method="GET" action="/index.php">
                    <input type="hidden" name="page" value="queue-status">
                    <div class="form-group" style="margin-bottom:.75rem;">
                        <input type="text" name="public_id" placeholder="xxxx-xxxx-xxxx-xxxx" required>
                    </div>
                    <button type="submit" class="btn btn-outline" style="width:100%;">Cek Status</button>
                </form>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <div class="card-body" style="display:flex;flex-direction:column;gap:.5rem;">
                <a href="/index.php?page=appointment&view=history" class="btn btn-ghost" style="justify-content:flex-start;gap:.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.75"/></svg>
                    Riwayat Kunjungan (<?= count($history) ?>)
                </a>
                <a href="/index.php?page=medical-record&action=history" class="btn btn-ghost" style="justify-content:flex-start;gap:.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Rekam Medis Saya
                </a>
                <a href="/index.php?page=public-queue" target="_blank" class="btn btn-ghost" style="justify-content:flex-start;gap:.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Papan Antrian Publik
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent History -->
<?php if (!empty($history)): ?>
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.75"/></svg>
            Kunjungan Terbaru
        </span>
        <a href="/index.php?page=appointment&view=history" class="btn btn-ghost btn-sm">Lihat semua →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Tanggal</th><th>Dokter</th><th>Spesialisasi</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td style="font-family:'Fira Code',monospace;font-size:.82rem;"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                    <td style="font-weight:600;">dr. <?= htmlspecialchars($h['doctor_name']) ?></td>
                    <td style="color:var(--text-secondary);"><?= htmlspecialchars($h['specialization']) ?></td>
                    <td><span class="badge badge-<?= $h['status'] ?>"><?= str_replace('_',' ',$h['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
