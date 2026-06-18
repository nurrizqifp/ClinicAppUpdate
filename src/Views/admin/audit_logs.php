<?php $pageTitle = 'Audit Log'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Audit Log Sistem</h1>
        <p>Catatan riwayat aktivitas pengguna dan perubahan data penting (200 data terakhir)</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Riwayat Aktivitas &amp; Log Perubahan
        </h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Tabel Referensi</th>
                    <th>ID Entity</th>
                    <th>Alamat IP</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            <h3>Belum Ada Log</h3>
                            <p>Tidak ada catatan riwayat aktivitas yang ditemukan di sistem saat ini.</p>
                        </div>
                    </td>
                </tr>
            <?php else: foreach ($logs as $log): ?>
                <tr>
                    <td style="font-family: 'Fira Code', monospace; font-size: 0.8rem; white-space: nowrap;">
                        <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                    </td>
                    <td>
                        <div style="font-weight: 600; color: var(--text-primary);">
                            <?= htmlspecialchars($log['user_name'] ?? 'Sistem') ?>
                        </div>
                    </td>
                    <td>
                        <code style="background: rgba(15,23,42,0.06); color: var(--navy); padding: 0.2rem 0.5rem; border-radius: 4px; font-family: 'Fira Code', monospace; font-size: 0.75rem; font-weight: 600;">
                            <?= htmlspecialchars($log['action']) ?>
                        </code>
                    </td>
                    <td>
                        <span style="font-weight: 500; color: var(--text-secondary);">
                            <?= htmlspecialchars($log['entity_table']) ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-family: 'Fira Code', monospace; font-size: 0.825rem; font-weight: 500;">
                            <?= $log['entity_id'] ?? '—' ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-family: 'Fira Code', monospace; font-size: 0.775rem; color: var(--text-muted);">
                            <?= htmlspecialchars($log['ip_address']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
