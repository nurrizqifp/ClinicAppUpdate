<?php $pageTitle = 'Rekam Medis Baru'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Input Rekam Medis &amp; Resep</h1>
        <p>Appointment #<?= (int)$appointment_id ?>. Isi diagnosis, catatan, dan resep obat pasien.</p>
    </div>
    <a href="/index.php?page=medical-record" class="btn btn-outline btn-sm">← Kembali</a>
</div>

<div style="max-width:860px;">
    <form method="POST" action="/index.php?page=medical-record&action=create&appointment_id=<?= (int)$appointment_id ?>">
        <?= $csrfField ?>

        <!-- EHR -->
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <span class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    Rekam Medis
                </span>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Diagnosis <span style="color:var(--red)">*</span></label>
                    <textarea id="diagnosis" name="diagnosis" required placeholder="Tuliskan diagnosis utama pasien..." rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Catatan Dokter <span style="color:var(--red)">*</span></label>
                    <textarea id="notes" name="notes" required placeholder="Catatan pemeriksaan, anjuran, tindak lanjut, dll..." rows="5"></textarea>
                </div>
            </div>
        </div>

        <!-- Prescription -->
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <span class="card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/></svg>
                    Item Resep
                </span>
                <button type="button" id="add-item" class="btn btn-outline btn-sm">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Tambah Obat
                </button>
            </div>
            <div class="card-body" id="prescription-items" style="display:flex;flex-direction:column;gap:.75rem;">
                <div class="prescription-item" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.875rem;align-items:end;padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid var(--border);">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Nama Obat</label>
                        <select name="medicine_id[]">
                            <option value="">— Tidak ada resep —</option>
                            <?php foreach ($medicines as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?> (Stok: <?= $m['stock'] ?> <?= $m['unit'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Dosis</label>
                        <input type="text" name="dosage[]" placeholder="mis. 3×1">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="quantity[]" min="1" value="1">
                    </div>
                    <div>
                        <button type="button" onclick="this.closest('.prescription-item').remove()" class="btn btn-danger btn-sm" title="Hapus baris ini" style="width:36px;padding:.375rem;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <span style="font-size:.8rem;color:var(--text-muted);">Kosongkan semua baris jika pasien tidak memerlukan resep.</span>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary btn-lg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Rekam Medis
            </button>
            <a href="/index.php?page=medical-record" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<script>
const medicinesData = <?= json_encode(array_map(fn($m) => ['id'=>$m['id'],'name'=>$m['name'],'unit'=>$m['unit'],'stock'=>$m['stock']], $medicines), JSON_UNESCAPED_UNICODE) ?>;

document.getElementById('add-item').addEventListener('click', () => {
    const opts = medicinesData.map(m => `<option value="${m.id}">${m.name} (Stok: ${m.stock} ${m.unit})</option>`).join('');
    const row = document.createElement('div');
    row.className = 'prescription-item';
    row.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.875rem;align-items:end;padding:1rem;background:var(--bg);border-radius:var(--radius);border:1px solid var(--border);';
    row.innerHTML = `
        <div class="form-group" style="margin:0;"><label class="form-label">Nama Obat</label><select name="medicine_id[]"><option value="">— Pilih obat —</option>${opts}</select></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Dosis</label><input type="text" name="dosage[]" placeholder="mis. 3×1"></div>
        <div class="form-group" style="margin:0;"><label class="form-label">Jumlah</label><input type="number" name="quantity[]" min="1" value="1"></div>
        <div><button type="button" onclick="this.closest('.prescription-item').remove()" class="btn btn-danger btn-sm" style="width:36px;padding:.375rem;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></div>
    `;
    document.getElementById('prescription-items').appendChild(row);
    // Scroll to new row
    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
});
</script>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
