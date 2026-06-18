<?php $pageTitle = 'Riwayat Rekam Medis'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Riwayat Rekam Medis Saya</h1>
        <p>Akses catatan medis elektronik (EHR) dan histori diagnosis pemeriksaan Anda di klinik secara aman</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Daftar Rekam Medis Elektronik (EHR) Anda
        </h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal Periksa</th>
                    <th>Dokter Pemeriksa</th>
                    <th>Diagnosis Utama</th>
                    <th>Catatan Klinis</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($records)): ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            <h3>Belum Ada Rekam Medis</h3>
                            <p>Anda belum memiliki catatan rekam medis terdaftar di klinik ini.</p>
                        </div>
                    </td>
                </tr>
            <?php else: foreach ($records as $rec): ?>
                <tr>
                    <td>
                        <span style="font-family: 'Fira Code', monospace; font-size: 0.825rem; font-weight: 500;">
                            <?= date('d/m/Y H:i', strtotime($rec['created_at'])) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-primary);">dr. <?= htmlspecialchars($rec['doctor_name']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 500; color: var(--text-primary); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?= htmlspecialchars($rec['diagnosis']) ?>
                        </div>
                    </td>
                    <td>
                        <div style="color: var(--text-secondary); max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($rec['notes'] ?? '') ?>">
                            <?= htmlspecialchars($rec['notes'] ?? '—') ?>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <a href="/index.php?page=medical-record&action=view&appointment_id=<?= $rec['appointment_id'] ?>" class="btn btn-outline btn-sm">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Lihat Detail
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
