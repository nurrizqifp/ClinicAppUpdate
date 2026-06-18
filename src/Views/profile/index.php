<?php
$pageTitle = 'Profil Saya';
include BASE_PATH.'/src/Views/layout/header.php';

$db = \App\Database\Connection::getConnection();
$role = $user['role'] ?? '';
$patientInfo = null;

if ($role === 'patient') {
    $stmt = $db->prepare("SELECT * FROM patients WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    $patientInfo = $stmt->fetch() ?: null;
}
?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Profil Saya</h1>
        <p>Kelola data diri dan informasi keamanan akun Anda</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.5rem; align-items: start;">
    
    <!-- User Card -->
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 2rem 1.5rem;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--emerald); color: #fff; font-size: 2.25rem; font-weight: 700; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
            </div>
            <h3 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.25rem;">
                <?= htmlspecialchars($user['name']) ?>
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em;">
                Role: <?= htmlspecialchars($role) ?>
            </p>
            <hr class="divider" style="margin: 1rem 0;">
            <a href="/index.php?page=change-password" class="btn btn-navy" style="width: 100%; justify-content: center; gap: 0.5rem;">
                <i data-lucide="key" style="width:16px;height:16px;"></i>
                Ubah Password
            </a>
        </div>
    </div>

    <!-- Details Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i data-lucide="user" style="width:18px;height:18px;"></i>
                Informasi Akun
            </h3>
        </div>
        <div class="card-body" style="padding: 1.5rem 2rem;">
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <span style="font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">Nama Lengkap</span>
                    <span style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($user['name']) ?></span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <span style="font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">Email / Username</span>
                    <span style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($user['email']) ?></span>
                </div>

                <?php if ($role === 'patient'): ?>
                    <?php
                    $nikPlain = $patientInfo['nik'] ?? '';
                    if (empty($nikPlain) && !empty($patientInfo['nik_encrypted'])) {
                        try {
                            $nikPlain = \App\Utils\Security::decryptNik($patientInfo['nik_encrypted']);
                        } catch (\Throwable $e) {}
                    }
                    ?>
                    <div style="display: grid; grid-template-columns: 1fr 2fr; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                        <span style="font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">NIK (16 Digit)</span>
                        <span style="font-family: 'Fira Code', monospace; font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($nikPlain ?: 'Belum diset') ?></span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                        <span style="font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">Tanggal Lahir</span>
                        <span style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($patientInfo['date_of_birth'] ?? 'Belum diset') ?></span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                        <span style="font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">Jenis Kelamin</span>
                        <span style="font-weight: 600; color: var(--text-primary);">
                            <?= ($patientInfo['gender'] ?? '') === 'M' ? 'Laki-laki' : 'Perempuan' ?>
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                        <span style="font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">Nomor Telepon</span>
                        <span style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($patientInfo['phone'] ?? 'Belum diset') ?></span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; padding-bottom: 0.75rem;">
                        <span style="font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">Alamat Lengkap</span>
                        <span style="font-weight: 500; color: var(--text-secondary); line-height: 1.5;"><?= htmlspecialchars($patientInfo['address'] ?? 'Belum diset') ?></span>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
