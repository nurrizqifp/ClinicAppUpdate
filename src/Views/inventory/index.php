<?php $pageTitle = 'Inventaris Obat'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Inventaris Obat</h1>
        <p><?= count($medicines) ?> obat terdaftar · <?= count($lowStockItems) ?> di bawah stok minimum</p>
    </div>
    <?php if (isset($user) && $user['role'] === 'admin'): ?>
    <a href="/index.php?page=inventory&action=create" class="btn btn-primary btn-sm">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Tambah Obat
    </a>
    <?php endif; ?>
</div>

<?php if (!empty($lowStockItems)): ?>
<div class="alert alert-warning">
    <span class="alert-icon">⚠️</span>
    <div><strong><?= count($lowStockItems) ?> obat kritis:</strong>
    <?= implode(', ', array_map(fn($m) => '<strong>'.htmlspecialchars($m['name']).'</strong> (sisa '.$m['stock'].')', $lowStockItems)) ?></div>
</div>
<?php endif; ?>

<!-- Stock Adjustment -->
<details class="card" style="margin-bottom:1.25rem;">
    <summary style="padding:1.125rem 1.375rem;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;font-size:.9rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.5rem;list-style:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
        Penyesuaian Stok
        <span style="margin-left:auto;font-size:.75rem;color:var(--text-muted);font-weight:400;">Klik untuk buka form</span>
    </summary>
    <div style="padding:1.375rem;border-top:1px solid var(--border);">
        <form method="POST" action="/index.php?page=inventory&action=stock" style="display:grid;grid-template-columns:2fr 1fr 1fr 1.5fr auto;gap:.875rem;align-items:flex-end;">
            <?= $csrfField ?>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Obat</label>
                <select name="medicine_id" required>
                    <option value="">— Pilih —</option>
                    <?php foreach ($medicines as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Tipe</label>
                <select name="type" required>
                    <option value="in">Masuk (+)</option>
                    <option value="out">Keluar (−)</option>
                    <option value="adjustment">Koreksi</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Jumlah</label>
                <input type="number" name="quantity" min="1" value="1" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Keterangan</label>
                <input type="text" name="note" placeholder="Opsional">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</details>

<!-- Medicines Table -->
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama Obat</th>
                    <th>Satuan</th>
                    <th>Stok Saat Ini</th>
                    <th>Min. Stok</th>
                    <th>Harga</th>
                    <th>Status</th>
                    <?php if (isset($user) && $user['role'] === 'admin'): ?><th>Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($medicines)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                        <h3>Belum ada obat</h3><p>Tambahkan obat pertama.</p>
                    </div>
                </td></tr>
            <?php else: foreach ($medicines as $m): $isLow = $m['stock'] < $m['minimum_stock']; ?>
                <tr>
                    <td style="font-weight:600;"><?= htmlspecialchars($m['name']) ?></td>
                    <td style="color:var(--text-secondary);"><?= htmlspecialchars($m['unit']) ?></td>
                    <td>
                        <span style="font-family:'Fira Code',monospace;font-weight:700;font-size:.95rem;color:<?= $isLow ? 'var(--red)' : 'var(--emerald)' ?>;">
                            <?= number_format($m['stock']) ?>
                        </span>
                    </td>
                    <td style="font-family:'Fira Code',monospace;font-size:.85rem;color:var(--text-muted);"><?= number_format($m['minimum_stock']) ?></td>
                    <td style="font-family:'Fira Code',monospace;font-size:.85rem;">Rp <?= number_format($m['price'],0,',','.') ?></td>
                    <td>
                        <?php if ($isLow): ?>
                            <span class="badge badge-emergency">Kritis</span>
                        <?php else: ?>
                            <span class="badge badge-in_consultation">Normal</span>
                        <?php endif; ?>
                    </td>
                    <?php if (isset($user) && $user['role'] === 'admin'): ?>
                    <td>
                        <form method="POST" action="/index.php?page=inventory&action=delete" style="display:inline;" onsubmit="return confirm('Hapus obat ini?')">
                            <?= $csrfField ?>
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
