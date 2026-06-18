<?php $pageTitle = 'Pengaturan Sistem'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Pengaturan Sistem</h1>
        <p>Konfigurasi variabel runtime global untuk aplikasi klinik dan apotek</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Variabel Konfigurasi Global
        </h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Kunci Pengaturan</th>
                    <th style="width: 35%;">Nilai Pengaturan</th>
                    <th style="width: 40%;">Deskripsi / Fungsi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($settings as $s): ?>
                <tr>
                    <td>
                        <code style="background: rgba(15,23,42,0.06); color: var(--navy); padding: 0.25rem 0.5rem; border-radius: 4px; font-family: 'Fira Code', monospace; font-size: 0.8rem; font-weight: 600;">
                            <?= htmlspecialchars($s['setting_key']) ?>
                        </code>
                    </td>
                    <td>
                        <form method="POST" action="/index.php?page=admin&action=save-setting" style="display: flex; gap: 0.5rem; align-items: center;">
                            <?= $csrfField ?>
                            <input type="hidden" name="setting_key" value="<?= htmlspecialchars($s['setting_key']) ?>">
                            <input type="text" name="setting_value" value="<?= htmlspecialchars($s['setting_value']) ?>" style="max-width: 180px; padding: 0.45rem 0.75rem; font-family: 'Fira Code', monospace; font-size: 0.85rem;">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                Simpan
                            </button>
                        </form>
                    </td>
                    <td>
                        <span style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5; display: inline-block;">
                            <?= htmlspecialchars($s['description'] ?? '—') ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
