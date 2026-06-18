<?php $pageTitle = 'Tambah Obat Baru'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Tambah Obat Baru</h1>
        <p>Registrasikan item obat baru ke dalam sistem inventaris klinik</p>
    </div>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            Formulir Data Obat
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="/index.php?page=inventory&action=create">
            <?= $csrfField ?>
            
            <div class="form-group">
                <label class="form-label" for="name">Nama Obat <span>*</span></label>
                <input type="text" id="name" name="name" required maxlength="255" placeholder="Contoh: Paracetamol 500mg">
            </div>

            <div class="form-group">
                <label class="form-label" for="unit">Satuan <span>*</span></label>
                <input type="text" id="unit" name="unit" required placeholder="Contoh: tablet, botol, kapsul, strip" maxlength="50">
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label class="form-label" for="stock">Stok Awal <span>*</span></label>
                    <input type="number" id="stock" name="stock" required min="0" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label" for="minimum_stock">Minimum Stok <span>*</span></label>
                    <input type="number" id="minimum_stock" name="minimum_stock" required min="0" value="10">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="price">Harga Jual Satuan (Rp) <span>*</span></label>
                <input type="number" id="price" name="price" required min="0" step="100" value="0">
            </div>

            <div class="form-actions" style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
                    Simpan Obat
                </button>
                <a href="/index.php?page=inventory" class="btn btn-outline">Batal</a>
            </div>
        </form>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
