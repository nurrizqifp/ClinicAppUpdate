<?php
use App\Utils\Security;
use App\App\Auth;
$pageTitle = 'Ganti Password';
include BASE_PATH.'/src/Views/layout/header.php';
?>

<div style="max-width: 480px; margin: 2rem auto;">
    <div class="card" style="box-shadow: var(--shadow-lg); border-radius: var(--radius-xl);">
        <div class="card-header" style="padding: 1.5rem 1.75rem 1.25rem;">
            <h3 class="card-title" style="font-size: 1.1rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Ganti Password
            </h3>
        </div>
        <div class="card-body" style="padding: 1.75rem;">
            <?php if (Auth::mustForcePasswordChange()): ?>
                <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                    <span class="alert-icon">⚠️</span>
                    <span><strong>Keamanan Penting:</strong> Anda wajib memperbarui password bawaan (default) sebelum mengakses fitur sistem lainnya.</span>
                </div>
            <?php endif; ?>

            <form method="POST" action="/index.php?page=change-password">
                <?= Security::csrfField() ?>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">Password Baru <span>*</span></label>
                    <input type="password" id="new_password" name="new_password" required minlength="8" placeholder="Minimal 8 karakter unik">
                    <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">Gunakan kombinasi huruf, angka, dan simbol untuk kekuatan optimal.</small>
                </div>
                
                <div class="form-group" style="margin-top: 1.25rem;">
                    <label for="confirm_password" class="form-label">Konfirmasi Password <span>*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Ulangi password baru">
                </div>

                <div style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                        Simpan Password Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
