<?php $pageTitle = 'Detail Rekam Medis'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Detail Rekam Medis</h1>
        <p>Lembar rekam medis elektronik (EHR) pasien terdaftar secara aman</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <button onclick="history.back()" class="btn btn-outline">Kembali</button>
    </div>
</div>

<div class="card" style="max-width: 800px; margin-bottom: 2rem;">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Lembar Rekam Medis Pasien
        </h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);">
            <div>
                <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.15rem;">Nama Pasien</span>
                <strong style="color: var(--text-primary); font-size: 1rem;"><?= htmlspecialchars($record['patient_name']) ?></strong>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.15rem;">Dokter Pemeriksa</span>
                <strong style="color: var(--text-primary); font-size: 1rem;">dr. <?= htmlspecialchars($record['doctor_name']) ?></strong>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.15rem;">Tanggal Pemeriksaan</span>
                <strong style="color: var(--text-primary); font-size: 0.95rem; font-family: 'Fira Code', monospace;"><?= date('d F Y H:i', strtotime($record['created_at'])) ?></strong>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <strong style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); display: block; margin-bottom: 0.4rem;">Diagnosis Dokter</strong>
            <div style="background: var(--bg); border-radius: var(--radius); padding: 1.25rem; border: 1px solid var(--border); color: var(--text-primary); font-size: 0.95rem; line-height: 1.6; font-weight: 500;">
                <?= nl2br(htmlspecialchars($record['diagnosis'])) ?>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <strong style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); display: block; margin-bottom: 0.4rem;">Catatan Klinis / Tindakan</strong>
            <div style="background: var(--bg); border-radius: var(--radius); padding: 1.25rem; border: 1px solid var(--border); color: var(--text-secondary); font-size: 0.925rem; line-height: 1.6;">
                <?= nl2br(htmlspecialchars($record['notes'] ?? 'Tidak ada catatan tambahan.')) ?>
            </div>
        </div>

        <?php if (!empty($record['prescription_items'])): ?>
        <div>
            <strong style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); display: block; margin-bottom: 0.5rem;">Resep Obat yang Diberikan</strong>
            <div class="table-wrap">
                <table style="border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden;">
                    <thead>
                        <tr style="background: var(--bg);">
                            <th>Nama Obat</th>
                            <th>Dosis / Aturan Pakai</th>
                            <th style="text-align: center;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($record['prescription_items'] as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['medicine_name']) ?></strong></td>
                            <td><?= htmlspecialchars($item['dosage']) ?></td>
                            <td style="text-align: center; font-family: 'Fira Code', monospace; font-weight: 600;"><?= $item['quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
