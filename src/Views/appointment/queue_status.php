<?php
// Public status check page — no auth required
$publicId    = htmlspecialchars($_GET['public_id'] ?? '');
$queueNumber = strtoupper(htmlspecialchars(trim($_GET['queue_number'] ?? '')));
$appointment = $appointment ?? null;
$ewt         = $ewt ?? null;
$statusLabels = [
    'waiting'         => ['label'=>'Menunggu',           'color'=>'#3B82F6', 'bg'=>'rgba(59,130,246,.12)', 'icon'=>'⏳'],
    'called'          => ['label'=>'Dipanggil!',          'color'=>'#EAB308', 'bg'=>'rgba(234,179,8,.12)',  'icon'=>'📢'],
    'in_consultation' => ['label'=>'Konsultasi',          'color'=>'#059669', 'bg'=>'rgba(5,150,105,.12)',  'icon'=>'🩺'],
    'done'            => ['label'=>'Selesai',             'color'=>'#64748B', 'bg'=>'rgba(100,116,139,.12)','icon'=>'✅'],
    'cancelled'       => ['label'=>'Dibatalkan',          'color'=>'#EF4444', 'bg'=>'rgba(239,68,68,.12)',  'icon'=>'❌'],
];
$st = $statusLabels[$appointment['status'] ?? ''] ?? ['label'=>'Tidak Diketahui','color'=>'#94A3B8','bg'=>'rgba(148,163,184,.1)','icon'=>'❓'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($appointment && in_array($appointment['status'] ?? '', ['waiting','called'])): ?>
    <meta http-equiv="refresh" content="30">
    <?php endif; ?>
    <title>Status Antrian — Klinik Verdana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=DM+Sans:wght@400;500&family=Fira+Code:wght@500;700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#F8FAFC;min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased;}
        header{background:#0F172A;padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;}
        .brand{display:flex;align-items:center;gap:.625rem;text-decoration:none;}
        .brand-icon{width:34px;height:34px;background:linear-gradient(135deg,#059669,#047857);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem;}
        .brand-name{font-family:'Plus Jakarta Sans',sans-serif;font-size:.95rem;font-weight:800;color:#fff;}
        .board-link{color:#34D399;font-size:.8rem;text-decoration:none;font-weight:500;}
        .board-link:hover{text-decoration:underline;}

        .container{max-width:560px;margin:3rem auto;padding:0 1.25rem;flex:1;}

        /* Search bar */
        .search-card{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06);}
        .search-card h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:.95rem;font-weight:700;margin-bottom:.875rem;color:#0F172A;}
        .search-row{display:flex;gap:.625rem;}
        .search-row input{
            flex:1;padding:.625rem .875rem;border:1px solid #CBD5E1;border-radius:8px;
            font-family:'DM Sans',sans-serif;font-size:.875rem;color:#0F172A;
        }
        .search-row input:focus{outline:none;border-color:#059669;box-shadow:0 0 0 3px rgba(5,150,105,.12);}
        .search-row button{
            padding:.625rem 1.125rem;background:#059669;color:#fff;border:none;
            border-radius:8px;font-family:'DM Sans',sans-serif;font-weight:600;cursor:pointer;
            white-space:nowrap;transition:background .15s;
        }
        .search-row button:hover{background:#047857;}

        /* Ticket card */
        .ticket{background:#fff;border:1px solid #E2E8F0;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.07);}
        .ticket-top{background:#0F172A;padding:2.25rem 2rem;text-align:center;position:relative;overflow:hidden;}
        .ticket-top::before{
            content:'';position:absolute;top:-40%;right:-10%;
            width:200px;height:200px;border-radius:50%;background:rgba(5,150,105,.12);
        }
        .ticket-top::after{
            content:'';position:absolute;bottom:-30%;left:-5%;
            width:150px;height:150px;border-radius:50%;background:rgba(59,130,246,.08);
        }
        .ticket-label{font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#64748B;margin-bottom:.875rem;position:relative;z-index:1;}
        .ticket-number{font-family:'Fira Code',monospace;font-size:5.5rem;font-weight:700;color:#34D399;line-height:1;position:relative;z-index:1;letter-spacing:-.02em;}
        .ticket-body{padding:1.75rem 2rem;}
        .info-row{display:flex;justify-content:space-between;align-items:center;padding:.75rem 0;border-bottom:1px solid #F1F5F9;}
        .info-row:last-child{border-bottom:none;}
        .info-label{font-size:.78rem;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;}
        .info-value{font-size:.9rem;font-weight:600;color:#0F172A;}

        /* Status badge */
        .status-pill{
            display:inline-flex;align-items:center;gap:.5rem;
            padding:.4rem 1rem;border-radius:9999px;
            font-size:.82rem;font-weight:700;
        }
        .status-pulse{width:8px;height:8px;border-radius:50%;background:currentColor;animation:pulse 1.5s infinite;}
        @keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(.85);}}

        /* EWT box */
        .ewt-box{
            background:linear-gradient(135deg,rgba(5,150,105,.06),rgba(5,150,105,.02));
            border:1px solid rgba(5,150,105,.2);border-radius:12px;
            padding:1.375rem;text-align:center;margin-top:1.25rem;
        }
        .ewt-num{font-family:'Fira Code',monospace;font-size:3rem;font-weight:700;color:#059669;line-height:1;}
        .ewt-unit{font-size:.8rem;color:#059669;font-weight:600;margin-top:.25rem;}
        .ewt-label{font-size:.78rem;color:#64748B;margin-top:.375rem;}

        /* Done banner */
        .done-banner{
            background:linear-gradient(135deg,#D1FAE5,#A7F3D0);
            border:1px solid #6EE7B7;border-radius:12px;
            padding:1.5rem;text-align:center;margin-top:1.25rem;
        }
        .done-banner h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.1rem;font-weight:700;color:#065F46;margin:.5rem 0 .25rem;}
        .done-banner p{font-size:.875rem;color:#047857;}

        /* Not found */
        .not-found{text-align:center;padding:3rem 1rem;}
        .not-found-icon{font-size:3.5rem;margin-bottom:1rem;}
        .not-found h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.125rem;font-weight:700;color:#0F172A;margin-bottom:.5rem;}
        .not-found p{font-size:.875rem;color:#64748B;}

        .auto-refresh{text-align:center;margin-top:1rem;font-size:.78rem;color:#94A3B8;display:flex;align-items:center;justify-content:center;gap:.375rem;}
        .refresh-dot{width:6px;height:6px;border-radius:50%;background:#059669;animation:pulse 1.5s infinite;}
    </style>
</head>
<body>
<header>
    <a href="/index.php?page=login" class="brand">
        <div class="brand-icon">🏥</div>
        <span class="brand-name">Klinik Verdana</span>
    </a>
    <a href="/index.php?page=public-queue" class="board-link">📺 Papan Antrian →</a>
</header>

<div class="container">
    <!-- Search -->
    <div class="search-card">
        <h3>🔍 Cek Status Antrian Anda</h3>
        <form method="GET" action="/index.php" class="search-row">
            <input type="hidden" name="page" value="queue-status">
            <input type="text" name="queue_number" value="<?= $queueNumber ?: $publicId ?>" placeholder="Nomor/ID Antrian" required autofocus>
            <button type="submit">Cek</button>
        </form>
    </div>

    <?php if (($queueNumber !== '' || $publicId !== '') && !$appointment): ?>
    <div class="not-found">
        <div class="not-found-icon">❓</div>
        <h3>Antrian Tidak Ditemukan</h3>
        <p>Nomor/ID Antrian <strong><?= htmlspecialchars($queueNumber ?: $publicId) ?></strong> tidak ditemukan hari ini. Pastikan nomor yang Anda masukkan benar.</p>
    </div>

    <?php elseif ($appointment): ?>
    <div class="ticket">
        <div class="ticket-top">
            <div class="ticket-label">Nomor Antrian Anda</div>
            <div class="ticket-number"><?= str_pad($appointment['queue_number'],3,'0',STR_PAD_LEFT) ?></div>
        </div>
        <div class="ticket-body">
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="status-pill" style="color:<?= $st['color'] ?>;background:<?= $st['bg'] ?>;">
                    <?php if (in_array($appointment['status'],['waiting','called','in_consultation'])): ?>
                    <span class="status-pulse" style="color:<?= $st['color'] ?>;"></span>
                    <?php endif; ?>
                    <?= $st['icon'] ?> <?= $st['label'] ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Dokter</span>
                <span class="info-value">dr. <?= htmlspecialchars($appointment['doctor_name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Prioritas</span>
                <span class="info-value"><?= ucfirst($appointment['priority']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">ID Antrian</span>
                <span style="font-family:'Fira Code',monospace;font-size:.78rem;color:#64748B;"><?= $appointment['public_id'] ?></span>
            </div>

            <?php if ($ewt !== null && in_array($appointment['status'],['waiting','called'])): ?>
            <div class="ewt-box">
                <div class="ewt-num"><?= $ewt ?></div>
                <div class="ewt-unit">menit</div>
                <div class="ewt-label">Estimasi Waktu Tunggu</div>
            </div>
            <?php endif; ?>

            <?php if ($appointment['status'] === 'done'): ?>
            <div class="done-banner">
                <div style="font-size:2.5rem;">✅</div>
                <h3>Konsultasi Selesai</h3>
                <p>Terima kasih telah mempercayakan kesehatan Anda kepada Klinik Verdana.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (in_array($appointment['status'],['waiting','called'])): ?>
    <div class="auto-refresh">
        <div class="refresh-dot"></div>
        <span>Halaman diperbarui otomatis setiap 30 detik</span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
