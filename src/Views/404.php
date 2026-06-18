<?php
$pageTitle = 'Halaman Tidak Ditemukan';
include BASE_PATH . '/src/Views/layout/header.php';
?>

<div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 60vh; text-align: center; padding: 2rem;">
    <div style="font-size: 6rem; font-weight: 800; color: var(--navy); line-height: 1; margin-bottom: 1rem; font-family: 'Plus Jakarta Sans', sans-serif;">404</div>
    <h1 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.75rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.75rem;">Halaman Tidak Ditemukan</h1>
    <p style="color: var(--text-secondary); max-width: 480px; margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.6;">Maaf, halaman yang Anda cari tidak dapat ditemukan atau telah dipindahkan ke alamat lain. Silakan periksa kembali URL Anda.</p>
    
    <div style="display: flex; gap: 0.75rem;">
        <a href="/index.php?page=dashboard" class="btn btn-primary btn-lg">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Kembali ke Dashboard
        </a>
        <button onclick="history.back()" class="btn btn-outline btn-lg">Kembali ke Halaman Sebelumnya</button>
    </div>
</div>

<?php include BASE_PATH . '/src/Views/layout/footer.php'; ?>
