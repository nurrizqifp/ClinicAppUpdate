<?php use App\Utils\Security; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Klinik Verdana</title>
    <meta name="description" content="Masuk ke Sistem Manajemen Klinik dan Apotek Klinik Verdana">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&family=Fira+Code:wght@500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            background: #0F172A;
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
        }

        /* Left Panel */
        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at 30% 30%, rgba(5,150,105,.25) 0%, transparent 60%),
                        radial-gradient(ellipse at 70% 70%, rgba(59,130,246,.1) 0%, transparent 60%);
            pointer-events: none;
        }
        /* Decorative grid pattern */
        .left-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .left-content { position: relative; z-index: 1; max-width: 400px; }
        .left-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(5,150,105,.15);
            border: 1px solid rgba(5,150,105,.3);
            color: #34D399;
            font-size: .75rem;
            font-weight: 600;
            padding: .35rem .875rem;
            border-radius: 9999px;
            margin-bottom: 2rem;
            letter-spacing: .03em;
        }
        .left-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #34D399; animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.4; } }
        .left-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: #F1F5F9;
            line-height: 1.2;
            letter-spacing: -.03em;
            margin-bottom: 1rem;
        }
        .left-title span { color: #34D399; }
        .left-desc {
            font-size: .9rem;
            color: #64748B;
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }
        .feature-list { display: flex; flex-direction: column; gap: .75rem; }
        .feature-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .84rem;
            color: #94A3B8;
        }
        .feature-icon {
            width: 28px; height: 28px;
            border-radius: 8px;
            background: rgba(5,150,105,.12);
            border: 1px solid rgba(5,150,105,.2);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-size: .85rem;
        }

        /* Right Panel — Login Form */
        .right-panel {
            width: 440px;
            flex-shrink: 0;
            background: #1E293B;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2.5rem;
            border-left: 1px solid rgba(255,255,255,.06);
            position: relative;
        }
        .form-header { margin-bottom: 2rem; }
        .form-logo {
            display: flex;
            align-items: center;
            gap: .625rem;
            margin-bottom: 1.75rem;
        }
        .form-logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #059669, #047857);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 0 0 3px rgba(5,150,105,.25);
        }
        .form-logo-name {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            color: #F1F5F9;
        }
        .form-logo-sub { font-size: .72rem; color: #64748B; }
        .form-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            color: #F1F5F9;
            letter-spacing: -.02em;
        }
        .form-subtitle { font-size: .875rem; color: #64748B; margin-top: .35rem; }

        /* Form Elements */
        .form-group { margin-bottom: 1.125rem; }
        label {
            display: block;
            font-size: .72rem;
            font-weight: 600;
            color: #94A3B8;
            margin-bottom: .45rem;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        input[type=email], input[type=password] {
            width: 100%;
            padding: .75rem 1rem;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 8px;
            color: #F1F5F9;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus { outline: none; border-color: #059669; box-shadow: 0 0 0 3px rgba(5,150,105,.2); }
        input::placeholder { color: #475569; }

        .btn-login {
            width: 100%;
            margin-top: 1.75rem;
            padding: .8125rem;
            background: #059669;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: .925rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s, box-shadow .15s, transform .1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
        }
        .btn-login:hover { background: #047857; box-shadow: 0 0 0 3px rgba(5,150,105,.25); }
        .btn-login:active { transform: scale(.98); }

        /* Alert */
        .alert-error {
            background: rgba(239,68,68,.1);
            border: 1px solid rgba(239,68,68,.25);
            color: #FCA5A5;
            padding: .75rem 1rem;
            border-radius: 8px;
            font-size: .845rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
        }

        .form-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,.07);
            text-align: center;
        }
        .form-footer a {
            color: #34D399;
            font-size: .84rem;
            text-decoration: none;
            font-weight: 500;
        }
        .form-footer a:hover { text-decoration: underline; }
        .form-footer p { font-size: .82rem; color: #475569; margin-bottom: .5rem; }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .left-panel { display: none; }
            .right-panel { width: 100%; min-height: 100vh; }
        }
    </style>
</head>
<body>

<!-- Left: Hero Panel -->
<div class="left-panel">
    <div class="left-content">
        <h1 class="left-title">Selamat Datang di<br><span>Klinik Verdana</span></h1>
        <p class="left-desc">Platform manajemen klinik dan apotek terintegrasi untuk layanan kesehatan yang efisien, aman, dan terpercaya.</p>
    </div>
</div>

<!-- Right: Login Form -->
<div class="right-panel">
    <div class="form-logo">
        <div class="form-logo-icon">🏥</div>
        <div>
            <div class="form-logo-name">Klinik Verdana</div>
            <div class="form-logo-sub">Klinik &amp; Apotek</div>
        </div>
    </div>
    <div class="form-header">
        <h2 class="form-title">Masuk ke Sistem</h2>
        <p class="form-subtitle">Gunakan akun yang diberikan administrator</p>
    </div>

    <?php if (!empty($_GET['error'])): ?>
    <div class="alert-error">
        <span>⚠️</span>
        <span><?= htmlspecialchars(urldecode($_GET['error'])) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" action="/index.php?page=login" id="login-form">
        <?= Security::csrfField() ?>
        <div class="form-group">
            <label for="email">Alamat Email</label>
            <input type="email" id="email" name="email" required autocomplete="email"
                   placeholder="nama@klinik.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••••">
        </div>
        <button type="submit" class="btn-login" id="btn-login">
            <span>Masuk ke Sistem</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </button>
    </form>

    <div class="form-footer">
        <p>Akses Publik:</p>
        <a href="/index.php?page=public-queue">📺 Lihat Papan Antrian →</a>
    </div>
</div>

<script>
document.getElementById('login-form')?.addEventListener('submit', function() {
    const btn = document.getElementById('btn-login');
    btn.innerHTML = '<span>Memproses...</span>';
    btn.disabled = true;
});
</script>
</body>
</html>
