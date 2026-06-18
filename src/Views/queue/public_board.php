<?php
/**
 * Public Queue Board — designed for TV/waiting room display.
 * No auth required. Auto-refreshes every 15s.
 */
$boardData = $boardData ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="15">
    <title>Papan Antrian — Klinik Verdana</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Fira+Code:wght@500;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'DM Sans',sans-serif;background:#0F172A;color:#F1F5F9;min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased;}

        /* Grid bg overlay */
        body::before{
            content:'';position:fixed;inset:0;
            background-image:linear-gradient(rgba(255,255,255,.01) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.01) 1px,transparent 1px);
            background-size:40px 40px;pointer-events:none;z-index:0;
        }

        header{
            position:relative;z-index:1;
            display:flex;justify-content:space-between;align-items:center;
            padding:1.25rem 2rem;
            background:rgba(30,41,59,.8);
            backdrop-filter:blur(12px);
            border-bottom:1px solid rgba(255,255,255,.06);
        }
        .header-brand{display:flex;align-items:center;gap:.875rem;}
        .header-brand-icon{
            width:42px;height:42px;border-radius:12px;
            background:linear-gradient(135deg,#059669,#047857);
            display:flex;align-items:center;justify-content:center;
            font-size:1.25rem;
            box-shadow:0 0 0 3px rgba(5,150,105,.25);
        }
        .header-brand-name{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.125rem;font-weight:800;color:#fff;}
        .header-brand-sub{font-size:.75rem;color:#64748B;}
        .header-right{display:flex;align-items:center;gap:1.5rem;}
        .header-clock{font-family:'Fira Code',monospace;font-size:1.4rem;font-weight:700;color:#34D399;}
        .header-date{font-size:.8rem;color:#64748B;text-align:right;}

        /* Main Board Container */
        .board{position:relative;z-index:1;padding:2.5rem 2rem;flex:1;}
        .section-label{
            font-size:.72rem;font-weight:700;letter-spacing:.12em;
            text-transform:uppercase;color:#64748B;
            margin-bottom:1.5rem;
            display:flex;align-items:center;gap:.5rem;
        }
        .section-label::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.08);}

        /* Parallel Grid Layout */
        .board-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(340px,1fr));
            gap:1.75rem;
        }

        .doctor-queue-card{
            background:rgba(30,41,59,.6);
            border:1px solid rgba(255,255,255,.08);
            border-radius:1.25rem;
            padding:2rem;
            box-shadow:0 10px 30px rgba(0,0,0,.25);
            display:flex;
            flex-direction:column;
            gap:1.5rem;
            transition:transform .2s, border-color .2s;
            position:relative;
            overflow:hidden;
        }
        .doctor-queue-card::before{
            content:'';position:absolute;top:0;right:0;width:80px;height:80px;
            background:radial-gradient(circle, rgba(5,150,105,0.08) 0%, transparent 70%);
            pointer-events:none;
        }
        .doctor-queue-card:hover{
            transform:translateY(-2px);
            border-color:rgba(5,150,105,.3);
        }

        .card-header{
            border-bottom:1px solid rgba(255,255,255,.06);
            padding-bottom:1rem;
        }
        .room-badge{
            display:inline-block;
            padding:.25rem .625rem;
            border-radius:6px;
            background:rgba(5,150,105,.12);
            color:#34D399;
            font-size:.7rem;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:.05em;
            margin-bottom:.5rem;
        }
        .doctor-name{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.25rem;font-weight:700;color:#fff;}
        .doctor-spec{font-size:.85rem;color:#64748B;margin-top:.125rem;}

        .serving-section{
            text-align:center;
            padding:1rem 0;
        }
        .serving-label{
            font-size:.72rem;
            font-weight:600;
            color:#64748B;
            text-transform:uppercase;
            letter-spacing:.08em;
            margin-bottom:.5rem;
        }
        .serving-num{
            font-family:'Fira Code',monospace;
            font-size:4.5rem;
            font-weight:700;
            color:#34D399;
            line-height:1;
            text-shadow:0 4px 12px rgba(52,211,153,.2);
        }
        .serving-patient{
            font-size:.95rem;
            color:#E2E8F0;
            font-weight:600;
            margin-top:.625rem;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .serving-empty{
            font-family:'Plus Jakarta Sans',sans-serif;
            font-size:2.25rem;
            font-weight:700;
            color:#334155;
            padding:.75rem 0;
            letter-spacing:.02em;
        }

        .next-section{
            background:rgba(15,23,42,.4);
            border:1px solid rgba(255,255,255,.04);
            padding:.875rem 1.25rem;
            border-radius:.75rem;
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-top:auto;
        }
        .next-label{font-size:.78rem;color:#64748B;font-weight:500;}
        .next-num{font-family:'Fira Code',monospace;font-size:1.15rem;font-weight:700;color:#38BDF8;}
        .next-empty{font-size:.8rem;color:#334155;font-weight:600;}

        /* No active doctors */
        .empty-board{
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            padding:8rem 2rem;
            text-align:center;
            color:#475569;
        }
        .empty-board svg{opacity:.25;margin-bottom:1.5rem;}
        .empty-board h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:1.5rem;font-weight:700;color:#64748B;margin-bottom:.5rem;}

        /* Footer */
        footer{
            position:relative;z-index:1;
            padding:.75rem 2rem;
            display:flex;justify-content:space-between;align-items:center;
            border-top:1px solid rgba(255,255,255,.05);
            background:rgba(15,23,42,.5);
        }
        footer span{font-size:.78rem;color:#334155;}
        .refresh-indicator{display:flex;align-items:center;gap:.375rem;font-size:.75rem;color:#334155;}
        .refresh-dot{width:6px;height:6px;border-radius:50%;background:#059669;animation:pulse 1.5s infinite;}
        @keyframes pulse{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(.8);}}
    </style>
</head>
<body>

<header>
    <div class="header-brand">
        <div class="header-brand-icon">🏥</div>
        <div>
            <div class="header-brand-name">Klinik Verdana</div>
            <div class="header-brand-sub">Papan Antrian Pasien Aktif</div>
        </div>
    </div>
    <div class="header-right">
        <div style="text-align:right;">
            <div class="header-clock" id="clock">--:--:--</div>
            <div class="header-date" id="date-display"></div>
        </div>
    </div>
</header>

<div class="board">
    <div class="section-label">LOKET PELAYANAN PARALEL</div>
    
    <?php if (!empty($boardData)): ?>
        <div class="board-grid">
            <?php foreach ($boardData as $data): ?>
                <div class="doctor-queue-card">
                    <div class="card-header">
                        <span class="room-badge"><?= htmlspecialchars($data['room']) ?></span>
                        <div class="doctor-name">dr. <?= htmlspecialchars($data['doctor_name']) ?></div>
                        <div class="doctor-spec"><?= htmlspecialchars($data['specialization']) ?></div>
                    </div>
                    
                    <div class="serving-section">
                        <div class="serving-label">Sedang Dilayani</div>
                        <?php if ($data['now_serving']): ?>
                            <div class="serving-num"><?= str_pad($data['now_serving']['queue_number'], 3, '0', STR_PAD_LEFT) ?></div>
                            <div class="serving-patient"><?= htmlspecialchars($data['now_serving']['patient_name']) ?></div>
                        <?php else: ?>
                            <div class="serving-empty">KOSONG</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="next-section">
                        <span class="next-label">Antrian Berikutnya:</span>
                        <?php if ($data['next_waiting']): ?>
                            <span class="next-num"><?= str_pad($data['next_waiting']['queue_number'], 3, '0', STR_PAD_LEFT) ?></span>
                        <?php else: ?>
                            <span class="next-empty">TIDAK ADA</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-board">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            <h3>Belum Ada Jadwal Dokter Aktif</h3>
            <p>Tidak ada dokter yang terdaftar beroperasi atau memiliki jadwal bertugas hari ini.</p>
        </div>
    <?php endif; ?>
</div>

<footer>
    <span>© 2026 Klinik Verdana — Sistem Manajemen Klinik &amp; Apotek</span>
    <div class="refresh-indicator">
        <div class="refresh-dot"></div>
        <span>Memperbarui otomatis setiap 15 detik</span>
    </div>
</footer>

<script>
function updateClock() {
    const now   = new Date();
    const clock = document.getElementById('clock');
    const date  = document.getElementById('date-display');
    if (clock) clock.textContent = now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
    if (date)  date.textContent  = now.toLocaleDateString('id-ID', {weekday:'long',day:'numeric',month:'long',year:'numeric'});
}
updateClock();
setInterval(updateClock, 1000);
</script>
</body>
</html>
