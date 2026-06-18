<?php
use App\Database\Connection;
use App\Services\InventoryService;
$pageTitle = 'Dashboard Admin';
$db  = Connection::getConnection();
$inv = new InventoryService();
$stats = [
    'users'        => $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn(),
    'appointments' => $db->query("SELECT COUNT(*) FROM appointments WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    'pending_rx'   => $db->query("SELECT COUNT(*) FROM prescriptions WHERE status='pending'")->fetchColumn(),
    'low_stock'    => count($inv->getLowStockItems()),
];
$recentQueues = $db->query("
    SELECT a.queue_number, a.status, a.priority, u.name AS patient_name, ud.name AS doctor_name
    FROM appointments a
    JOIN patients p ON a.patient_id=p.user_id JOIN users u ON p.user_id=u.id
    JOIN doctors d ON a.doctor_id=d.user_id JOIN users ud ON d.user_id=ud.id
    WHERE DATE(a.created_at)=CURDATE()
    ORDER BY a.queue_number ASC LIMIT 10
")->fetchAll();
include BASE_PATH.'/src/Views/layout/header.php';
?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Dashboard Administrator</h1>
        <p>Selamat datang, <?= htmlspecialchars(\App\App\Auth::user()['name']) ?>. Ini ringkasan aktivitas klinik hari ini.</p>
    </div>
    <div style="display:flex;gap:.625rem;">
        <a href="/index.php?page=admin&action=audit-logs" class="btn btn-outline btn-sm">📝 Audit Log</a>
        <a href="/index.php?page=queue" class="btn btn-primary btn-sm">🔢 Papan Antrian</a>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card emerald">
        <div class="stat-card-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-number"><?= $stats['users'] ?></div>
        <div class="stat-label">Total Pengguna</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-card-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="stat-number"><?= $stats['appointments'] ?></div>
        <div class="stat-label">Antrian Hari Ini</div>
    </div>
    <div class="stat-card <?= $stats['pending_rx'] > 0 ? 'amber' : 'emerald' ?>">
        <div class="stat-card-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>
        </div>
        <div class="stat-number"><?= $stats['pending_rx'] ?></div>
        <div class="stat-label">Resep Menunggu</div>
    </div>
    <div class="stat-card <?= $stats['low_stock'] > 0 ? 'red' : 'emerald' ?>">
        <div class="stat-card-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        </div>
        <div class="stat-number"><?= $stats['low_stock'] ?></div>
        <div class="stat-label">Stok Kritis</div>
    </div>
</div>

<!-- Quick Actions -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.875rem;margin-bottom:1.5rem;">
    <?php
    $quickActions = [
        ['href'=>'/index.php?page=admin&action=users',    'icon'=>'👥', 'label'=>'Kelola Pengguna'],
        ['href'=>'/index.php?page=admin&action=settings', 'icon'=>'⚙️', 'label'=>'Pengaturan Sistem'],
        ['href'=>'/index.php?page=inventory',             'icon'=>'📦', 'label'=>'Inventaris Obat'],
        ['href'=>'/index.php?page=pharmacy',              'icon'=>'💊', 'label'=>'Farmasi'],
    ];
    foreach ($quickActions as $qa): ?>
    <a href="<?= $qa['href'] ?>" style="
        display:flex;align-items:center;gap:.75rem;padding:1rem 1.125rem;
        background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);
        text-decoration:none;color:var(--text-primary);font-weight:600;font-size:.875rem;
        transition:box-shadow .15s,transform .15s;box-shadow:var(--shadow-sm);"
        onmouseover="this.style.boxShadow='var(--shadow-md)';this.style.transform='translateY(-1px)'"
        onmouseout="this.style.boxShadow='var(--shadow-sm)';this.style.transform='translateY(0)'">
        <span style="font-size:1.25rem;"><?= $qa['icon'] ?></span>
        <span><?= $qa['label'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Today's Queue -->
<div class="card">
    <div class="card-header">
        <span class="card-title">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            Antrian Hari Ini
        </span>
        <a href="/index.php?page=queue" class="btn btn-ghost btn-sm">Lihat semua →</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>No. Antrian</th><th>Pasien</th><th>Dokter</th><th>Prioritas</th><th>Status</th></tr></thead>
            <tbody>
            <?php if (empty($recentQueues)): ?>
                <tr><td colspan="5">
                    <div class="empty-state" style="padding:2.5rem;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <h3>Belum ada antrian</h3>
                        <p>Tidak ada pasien yang terdaftar hari ini.</p>
                    </div>
                </td></tr>
            <?php else: foreach ($recentQueues as $q): ?>
                <tr>
                    <td><span class="queue-num queue-num-lg"><?= str_pad($q['queue_number'],3,'0',STR_PAD_LEFT) ?></span></td>
                    <td style="font-weight:600;"><?= htmlspecialchars($q['patient_name']) ?></td>
                    <td style="color:var(--text-secondary);">dr. <?= htmlspecialchars($q['doctor_name']) ?></td>
                    <td><span class="badge badge-<?= $q['priority'] ?>"><?= $q['priority'] ?></span></td>
                    <td><span class="badge badge-<?= $q['status'] ?>"><?= str_replace('_',' ',$q['status']) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
