<?php $pageTitle = 'Pendaftaran Antrian Baru'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Pendaftaran Antrian Baru</h1>
        <p>Silakan pilih poliklinik/dokter dan isi keluhan pasien untuk mendapatkan nomor antrian</p>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Formulir Pendaftaran Antrian
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="/index.php?page=appointment">
            <?= $csrfField ?>

            <?php if (in_array($user['role'], ['receptionist', 'admin'], true)): ?>
                <div class="form-group">
                    <label class="form-label" for="patient_nik">NIK Pasien (16 Digit) <span>*</span></label>
                    <input type="text" id="patient_nik" name="patient_nik" required pattern="\d{16}" placeholder="Masukkan NIK (16 Digit)">
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">Masukkan 16 digit Nomor Induk Kependudukan pasien yang terdaftar.</small>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label" for="doctor_id">Dokter &amp; Spesialisasi <span>*</span></label>
                <select name="doctor_id" id="doctor_id" required>
                    <option value="">-- Pilih Dokter Terbuka --</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['user_id'] ?>">dr. <?= htmlspecialchars($doc['name']) ?> — <?= htmlspecialchars($doc['specialization']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="priority">Tingkat Prioritas <span>*</span></label>
                <select name="priority" id="priority" required>
                    <option value="normal">Normal (Sesuai Urutan)</option>
                    <option value="emergency">Gawat Darurat / Emergency</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="complaint">Keluhan Utama Pasien <span>*</span></label>
                <textarea id="complaint" name="complaint" required placeholder="Jelaskan secara singkat gejala atau keluhan utama yang dirasakan..." rows="5"></textarea>
            </div>

            <div class="form-actions" style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Daftar Antrian Baru
                </button>
                <a href="/index.php?page=dashboard" class="btn btn-outline btn-lg">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
