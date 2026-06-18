<?php $pageTitle = 'Konfirmasi Dispensing'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Konfirmasi Dispensing</h1>
        <p>Pemberian dan pemotongan stok obat apotek berdasarkan instruksi resep dokter</p>
    </div>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m14.5 9.5-5 5"/><path d="M8.5 8.5a2.121 2.121 0 1 1 3 3 2.121 2.121 0 1 1-3-3z"/><path d="M12.5 12.5a2.121 2.121 0 1 1 3 3 2.121 2.121 0 1 1-3-3z"/></svg>
            Detail Lembar Resep #<?= $prescription['id'] ?>
        </h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);">
            <div>
                <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.15rem;">Nama Pasien</span>
                <strong style="color: var(--text-primary); font-size: 1rem;"><?= htmlspecialchars($prescription['patient_name']) ?></strong>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.15rem;">Dokter Pengirim</span>
                <strong style="color: var(--text-primary); font-size: 1rem;"><?= htmlspecialchars($prescription['doctor_name']) ?></strong>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.15rem;">Nomor Antrian</span>
                <strong class="queue-num" style="font-size: 1.25rem;"><?= str_pad($prescription['queue_number'], 3, '0', STR_PAD_LEFT) ?></strong>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.25rem;">Status Resep</span>
                <span class="badge badge-<?= $prescription['status'] ?>"><?= $prescription['status'] === 'dispensed' ? 'Sudah Diberikan' : 'Menunggu' ?></span>
            </div>
        </div>

        <div style="background: var(--bg); border-radius: var(--radius); padding: 1.25rem; border: 1px solid var(--border); margin-bottom: 1.5rem;">
            <strong style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); display: block; margin-bottom: 0.4rem;">Diagnosis Pasien</strong>
            <p style="margin: 0; color: var(--text-primary); font-size: 0.9rem; line-height: 1.6; font-weight: 500;"><?= htmlspecialchars($prescription['diagnosis']) ?></p>
        </div>

        <div class="table-wrap" style="margin-bottom: 1.75rem;">
            <table style="border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden;">
                <thead>
                    <tr style="background: var(--bg);">
                        <th>Nama Obat</th>
                        <th>Aturan Pakai / Dosis</th>
                        <th style="text-align: center;">Jumlah</th>
                        <th style="text-align: center;">Stok Apotek</th>
                        <th style="text-align: center;">Kelayakan</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($prescription['items'] as $item): $enough = $item['stock'] >= $item['quantity']; ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($item['medicine_name']) ?></div>
                        </td>
                        <td>
                            <span style="color: var(--text-secondary);"><?= htmlspecialchars($item['dosage']) ?></span>
                        </td>
                        <td style="text-align: center; font-family: 'Fira Code', monospace; font-weight: 600; font-size: 0.85rem;">
                            <?= $item['quantity'] ?> <span style="font-weight: 400; color: var(--text-muted);"><?= $item['unit'] ?></span>
                        </td>
                        <td style="text-align: center; font-family: 'Fira Code', monospace; font-weight: 600; font-size: 0.85rem; color: <?= $enough ? 'var(--emerald)' : 'var(--red)' ?>;">
                            <?= $item['stock'] ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($enough): ?>
                                <span class="badge badge-active" style="padding: 0.25rem 0.5rem;">Cukup</span>
                            <?php else: ?>
                                <span class="badge badge-cancelled" style="padding: 0.25rem 0.5rem;">Kurang</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($prescription['status'] === 'dispensed'): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <span><strong>Transaksi Berhasil:</strong> Resep obat ini sudah diserahkan ke pasien dan stok telah disesuaikan secara otomatis.</span>
            </div>
            <div style="margin-top: 1.5rem;">
                <a href="/index.php?page=pharmacy" class="btn btn-outline">Kembali ke Antrian</a>
            </div>
        <?php else: ?>
            <form method="POST" action="/index.php?page=pharmacy&action=dispense&id=<?= $prescription['id'] ?>" onsubmit="return confirm('Konfirmasi pemberian obat sesuai resep?')">
                <?= $csrfField ?>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        Konfirmasi &amp; Serahkan Obat
                    </button>
                    <a href="/index.php?page=pharmacy" class="btn btn-outline btn-lg">Batal</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
