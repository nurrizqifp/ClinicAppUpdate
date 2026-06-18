<?php $pageTitle = 'Edit Pengguna'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Edit Pengguna</h1>
        <p>Perbarui data akun: <strong><?= htmlspecialchars($target['name']) ?></strong></p>
    </div>
    <a href="<?= $baseUrl ?>/admin?action=users" class="btn btn-outline">
        <i data-lucide="arrow-left" style="width:15px;height:15px;"></i>
        Kembali
    </a>
</div>

<div style="max-width:600px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i data-lucide="user-cog" style="width:18px;height:18px;"></i>
                Informasi Akun
            </h3>
            <span class="badge badge-<?= htmlspecialchars($target['role']) ?>"><?= htmlspecialchars($target['role']) ?></span>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= $baseUrl ?>/admin?action=edit-user" autocomplete="off">
                <?= $csrfField ?>
                <input type="hidden" name="user_id" value="<?= (int)$target['id'] ?>">

                <div class="form-group">
                    <label class="form-label" for="name">Nama Lengkap <span>*</span></label>
                    <input type="text" id="name" name="name" required
                           value="<?= htmlspecialchars($target['name']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email <span>*</span></label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($target['email']) ?>">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="role">Role <span>*</span></label>
                        <select id="role" name="role" required>
                            <option value="doctor"       <?= $target['role'] === 'doctor'       ? 'selected' : '' ?>>Dokter</option>
                            <option value="receptionist" <?= $target['role'] === 'receptionist' ? 'selected' : '' ?>>Resepsionis</option>
                            <option value="apoteker"     <?= $target['role'] === 'apoteker'     ? 'selected' : '' ?>>Apoteker</option>
                            <option value="patient"      <?= $target['role'] === 'patient'      ? 'selected' : '' ?>>Pasien</option>
                            <option value="admin"        <?= $target['role'] === 'admin'        ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="is_active">Status Akun</label>
                        <select id="is_active" name="is_active">
                            <option value="1" <?= $target['is_active'] ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= !$target['is_active'] ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>

                <hr class="divider">

                <div class="form-group">
                    <label class="form-label" for="password">Reset Password</label>
                    <input type="password" id="password" name="password"
                           minlength="8"
                           placeholder="Kosongkan jika tidak ingin mengubah password"
                           autocomplete="new-password">
                    <p style="font-size:.75rem;color:var(--text-muted);margin-top:.35rem;">
                        <i data-lucide="shield-alert" style="width:12px;height:12px;vertical-align:middle;"></i>
                        Jika diisi, pengguna akan diminta ganti password saat login berikutnya.
                    </p>
                </div>

                <?php
                $patientNik = '';
                if ($patient) {
                    $patientNik = $patient['nik'] ?? '';
                    if (empty($patientNik) && !empty($patient['nik_encrypted'])) {
                        try {
                            $patientNik = \App\Utils\Security::decryptNik($patient['nik_encrypted']);
                        } catch (\Throwable) {}
                    }
                }
                ?>
                <!-- Patient Extra Fields -->
                <div id="patient-fields" style="display: none; border: 1px dashed var(--border); padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; background: rgba(248, 250, 252, 0.02);">
                    <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--emerald); font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 0.35rem;">
                        <i data-lucide="contact" style="width:16px;height:16px;"></i>
                        Data Medis / Profil Pasien
                    </h4>
                    
                    <div class="form-group">
                        <label class="form-label" for="nik">NIK (16 Digit) <span>*</span></label>
                        <input type="text" id="nik" name="nik" pattern="\d{16}" placeholder="NIK (16 Digit)" value="<?= htmlspecialchars($patientNik) ?>">
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="date_of_birth">Tanggal Lahir <span>*</span></label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?= htmlspecialchars($patient['date_of_birth'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="gender">Jenis Kelamin <span>*</span></label>
                            <select id="gender" name="gender">
                                <option value="M" <?= (($patient['gender'] ?? '') === 'M') ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="F" <?= (($patient['gender'] ?? '') === 'F') ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Nomor Telepon <span>*</span></label>
                        <input type="text" id="phone" name="phone" placeholder="Contoh: 08123456789" value="<?= htmlspecialchars($patient['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="address">Alamat Lengkap <span>*</span></label>
                        <textarea id="address" name="address" rows="3" placeholder="Alamat rumah sesuai KTP..."><?= htmlspecialchars($patient['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save" style="width:15px;height:15px;"></i>
                        Simpan Perubahan
                    </button>
                    <a href="<?= $baseUrl ?>/admin?action=users" class="btn btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('role')?.addEventListener('change', function() {
    const pFields = document.getElementById('patient-fields');
    if (this.value === 'patient') {
        pFields.style.display = 'block';
        document.getElementById('nik').required = true;
        document.getElementById('date_of_birth').required = true;
        document.getElementById('phone').required = true;
        document.getElementById('address').required = true;
    } else {
        pFields.style.display = 'none';
        document.getElementById('nik').required = false;
        document.getElementById('date_of_birth').required = false;
        document.getElementById('phone').required = false;
        document.getElementById('address').required = false;
    }
});

// Trigger change on load if role is already selected
if (document.getElementById('role')?.value === 'patient') {
    const pFields = document.getElementById('patient-fields');
    if (pFields) {
        pFields.style.display = 'block';
        document.getElementById('nik').required = true;
        document.getElementById('date_of_birth').required = true;
        document.getElementById('phone').required = true;
        document.getElementById('address').required = true;
    }
}
</script>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
