<?php
use App\App\Auth;

$user      = $user      ?? Auth::user();
$pageTitle = $pageTitle ?? 'Klinik Verdana';
$activeNav = $activeNav ?? '';
$navRole   = $user['role'] ?? 'guest';
$baseUrl   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// ─── Nav items: 'path' = clean URL segment(s) after base, no leading slash ──
// 'match' = array of exact path(s) that activate this menu item
// 'prefix' = true  → also active for any sub-path (e.g. appointment/list/123)
// 'prefix' = false → exact match only
$navItems = match($navRole) {
    'admin' => [
        ['label' => 'Dashboard',   'path' => 'dashboard',          'icon' => 'layout-dashboard', 'match' => ['dashboard',''],            'prefix' => false],
        ['label' => 'Antrian',     'path' => 'queue',              'icon' => 'list-ordered',     'match' => ['queue'],                   'prefix' => true ],
        ['label' => 'Appointment', 'path' => 'appointment/list',   'icon' => 'calendar',         'match' => ['appointment','appointment/list'], 'prefix' => false],
        ['label' => 'Rekam Medis', 'path' => 'medical-record',     'icon' => 'file-text',        'match' => ['medical-record'],          'prefix' => true ],
        ['label' => 'Farmasi',     'path' => 'pharmacy',           'icon' => 'droplet',          'match' => ['pharmacy'],                'prefix' => true ],
        ['label' => 'Inventaris',  'path' => 'inventory',          'icon' => 'package',          'match' => ['inventory'],               'prefix' => true ],
        ['label' => 'Admin',       'path' => 'admin',              'icon' => 'shield',           'match' => ['admin'],                   'prefix' => true ],
    ],
    'receptionist' => [
        ['label' => 'Dashboard',   'path' => 'dashboard',          'icon' => 'layout-dashboard', 'match' => ['dashboard',''],            'prefix' => false],
        ['label' => 'Antrian',     'path' => 'queue',              'icon' => 'list-ordered',     'match' => ['queue'],                   'prefix' => true ],
        ['label' => 'Appointment', 'path' => 'appointment/list',   'icon' => 'calendar',         'match' => ['appointment','appointment/list'], 'prefix' => false],
    ],
    'doctor' => [
        ['label' => 'Dashboard',    'path' => 'dashboard',         'icon' => 'layout-dashboard', 'match' => ['dashboard',''],            'prefix' => false],
        ['label' => 'Antrian Saya', 'path' => 'queue/my',          'icon' => 'clipboard-list',   'match' => ['queue/my'],                'prefix' => false],
        ['label' => 'Rekam Medis',  'path' => 'medical-record',    'icon' => 'file-text',        'match' => ['medical-record'],          'prefix' => true ],
    ],
    'apoteker' => [
        ['label' => 'Dashboard',   'path' => 'dashboard',          'icon' => 'layout-dashboard', 'match' => ['dashboard',''],            'prefix' => false],
        ['label' => 'Farmasi',     'path' => 'pharmacy',           'icon' => 'droplet',          'match' => ['pharmacy'],                'prefix' => true ],
        ['label' => 'Inventaris',  'path' => 'inventory',          'icon' => 'package',          'match' => ['inventory'],               'prefix' => true ],
    ],
    'patient' => [
        ['label' => 'Dashboard',      'path' => 'dashboard',             'icon' => 'layout-dashboard', 'match' => ['dashboard',''],                   'prefix' => false],
        ['label' => 'Daftar Antrian', 'path' => 'appointment',           'icon' => 'calendar',         'match' => ['appointment','appointment/list'],  'prefix' => false],
        ['label' => 'Riwayat',        'path' => 'appointment/history',   'icon' => 'history',          'match' => ['appointment/history'],             'prefix' => false],
        ['label' => 'Rekam Medis',    'path' => 'medical-record/history','icon' => 'file-text',        'match' => ['medical-record/history'],          'prefix' => false],
    ],
    default => [],
};

// ─── Resolve current path (mirrors Router::dispatch() logic) ─────────────────
$_rawUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$_baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

// Strip base directory prefix (subdirectory hosting support)
if ($_baseDir !== '' && str_starts_with($_rawUri, $_baseDir)) {
    $_rawUri = substr($_rawUri, strlen($_baseDir));
}
// Strip index.php prefix (legacy fallback URLs)
if (str_starts_with($_rawUri, '/index.php')) {
    $_rawUri = substr($_rawUri, 10);
}

$currentPath = trim($_rawUri, '/');

// Fallback: if path is empty, check legacy ?page= query param
if ($currentPath === '') {
    $currentPath = trim(filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'dashboard');
}
// Handle legacy ?page=appointment&view=history → appointment/history
if ($currentPath !== '' && !str_contains($currentPath, '/') && isset($_GET['view']) && $_GET['view'] !== '') {
    $currentPath = $currentPath . '/' . trim($_GET['view']);
} elseif ($currentPath !== '' && !str_contains($currentPath, '/') && isset($_GET['action']) && $_GET['action'] !== '') {
    $currentPath = $currentPath . '/' . trim($_GET['action']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — Klinik Verdana</title>
    <meta name="description" content="Sistem Manajemen Klinik dan Apotek Klinik Verdana">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Sans:wght@300;400;500;600&family=Fira+Code:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* ═══════════════════════════════════════════════════════════════
           Klinik Verdana DESIGN SYSTEM — CSS Token Layer
           Palette:
             --navy    #0F172A  (bg sidebar, primary dark)
             --emerald #059669  (accent / CTA)
             --amber   #EAB308  (warning)
             --bg      #F8FAFC  (page background)
           Typography:
             Plus Jakarta Sans → headings
             DM Sans          → body
             Fira Code        → data / numbers
        ═══════════════════════════════════════════════════════════════ */
        :root {
            --navy:        #0F172A;
            --navy-800:    #1E293B;
            --navy-700:    #334155;
            --emerald:     #059669;
            --emerald-500: #10B981;
            --emerald-400: #34D399;
            --amber:       #EAB308;
            --amber-light: #FEF9C3;
            --red:         #EF4444;
            --red-light:   #FEE2E2;
            --blue:        #3B82F6;
            --blue-light:  #DBEAFE;
            --bg:          #F8FAFC;
            --surface:     #FFFFFF;
            --border:      #E2E8F0;
            --border-dark: #CBD5E1;
            --text-primary:   #0F172A;
            --text-secondary: #475569;
            --text-muted:     #94A3B8;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / .05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / .07), 0 2px 4px -2px rgb(0 0 0 / .05);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / .08), 0 4px 6px -4px rgb(0 0 0 / .04);
            --radius:    8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --sidebar-w: 248px;
            --topbar-h:  60px;
            --transition: 150ms cubic-bezier(.4,0,.2,1);
        }

        /* ── Reset ─────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'DM Sans', system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Layout Shell ──────────────────────────────── */
        .app-shell { display: flex; min-height: 100vh; }

        /* ═══════════════════════════════════════════════
           SIDEBAR
        ═══════════════════════════════════════════════ */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--navy);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            border-right: 1px solid rgba(255,255,255,.06);
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }

        /* Brand */
        .sidebar-brand {
            padding: 1.5rem 1.25rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }
        .brand-logo {
            display: flex;
            align-items: center;
            gap: .625rem;
            text-decoration: none;
        }
        .brand-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--emerald) 0%, #047857 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 0 0 2px rgba(5,150,105,.3);
        }
        .brand-text { }
        .brand-name {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .9rem;
            font-weight: 800;
            color: #fff;
            line-height: 1.2;
            letter-spacing: -.01em;
        }
        .brand-sub {
            font-size: .7rem;
            color: var(--text-muted);
            letter-spacing: .02em;
        }

        /* Nav Section Label */
        .nav-section-label {
            padding: 1.25rem 1.25rem .4rem;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255,255,255,.25);
        }

        /* Nav Items */
        .sidebar-nav { flex: 1; padding: .5rem 0; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1px .625rem;
            padding: .6rem .875rem;
            border-radius: var(--radius);
            color: #94A3B8;
            text-decoration: none;
            font-size: .83rem;
            font-weight: 500;
            transition: background var(--transition), color var(--transition);
        }
        .nav-item:hover {
            background: rgba(255,255,255,.08);
            color: #E2E8F0;
        }
        .nav-item.active {
            background: rgba(255,255,255,.12);
            color: #fff;
            font-weight: 600;
        }
        .nav-item svg { flex-shrink: 0; opacity: .7; }
        .nav-item.active svg { opacity: 1; }
        .nav-item-label { line-height: 1; }

        /* Sidebar Footer */
        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }
        .sidebar-user {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-bottom: .875rem;
        }
        .sidebar-avatar {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--emerald), #047857);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .8rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .sidebar-user-info { min-width: 0; }
        .sidebar-user-name {
            font-size: .8rem;
            font-weight: 600;
            color: #E2E8F0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user-role {
            font-size: .68rem;
            color: var(--text-muted);
            text-transform: capitalize;
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: .5rem;
            width: 100%;
            padding: .525rem .75rem;
            border-radius: var(--radius);
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.15);
            color: #FCA5A5;
            font-size: .8rem;
            font-weight: 500;
            text-decoration: none;
            transition: background var(--transition), color var(--transition);
            cursor: pointer;
        }
        .logout-btn:hover { background: rgba(239,68,68,.18); color: #FECACA; }

        /* ═══════════════════════════════════════════════
           MAIN CONTENT
        ═══════════════════════════════════════════════ */
        .main { margin-left: var(--sidebar-w); display: flex; flex-direction: column; min-height: 100vh; flex-grow: 1; min-width: 0; }

        /* Topbar */
        .topbar {
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.75rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }
        .topbar-left { display: flex; align-items: center; gap: .75rem; }
        .topbar-breadcrumb {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .85rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: .375rem;
        }
        .topbar-breadcrumb span:last-child { color: var(--text-primary); font-weight: 600; }
        .topbar-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-primary);
        }
        .topbar-right { display: flex; align-items: center; gap: .875rem; }
        .topbar-role {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: .25rem .625rem;
            border-radius: 9999px;
            background: rgba(5,150,105,.12);
            color: var(--emerald);
            border: 1px solid rgba(5,150,105,.2);
        }
        .topbar-time {
            font-family: 'Fira Code', monospace;
            font-size: .78rem;
            color: var(--text-muted);
        }

        /* Page Body */
        .page-body { padding: 1.75rem; flex: 1; max-width: 1400px; }

        /* ═══════════════════════════════════════════════
           PAGE HEADER
        ═══════════════════════════════════════════════ */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .page-header-text h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.375rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1.3;
            letter-spacing: -.02em;
        }
        .page-header-text p {
            font-size: .875rem;
            color: var(--text-secondary);
            margin-top: .25rem;
        }

        /* ═══════════════════════════════════════════════
           ALERTS
        ═══════════════════════════════════════════════ */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .875rem 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.25rem;
            font-size: .875rem;
            border: 1px solid transparent;
            animation: slideDown .2s ease-out;
        }
        @keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
        .alert-icon { font-size: 1rem; flex-shrink: 0; line-height: 1.6; }
        .alert-success { background: #D1FAE5; color: #065F46; border-color: #6EE7B7; }
        .alert-error   { background: var(--red-light); color: #991B1B; border-color: #FCA5A5; }
        .alert-warning { background: var(--amber-light); color: #713F12; border-color: #FDE047; }
        .alert-info    { background: var(--blue-light); color: #1E40AF; border-color: #93C5FD; }

        /* ═══════════════════════════════════════════════
           STAT CARDS
        ═══════════════════════════════════════════════ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 1.375rem 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: box-shadow var(--transition), transform var(--transition);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 4px;
            height: 100%;
            background: var(--stat-color, var(--emerald));
            border-radius: 4px 0 0 4px;
        }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .stat-card-icon {
            width: 40px; height: 40px;
            border-radius: var(--radius);
            background: var(--stat-bg, rgba(5,150,105,.1));
            display: flex; align-items: center; justify-content: center;
            color: var(--stat-color, var(--emerald));
            margin-bottom: .875rem;
        }
        .stat-number {
            font-family: 'Fira Code', monospace;
            font-size: 2rem;
            font-weight: 700;
            color: var(--stat-color, var(--text-primary));
            line-height: 1;
        }
        .stat-label {
            font-size: .78rem;
            color: var(--text-secondary);
            margin-top: .375rem;
            font-weight: 500;
        }
        .stat-card.emerald { --stat-color: var(--emerald); --stat-bg: rgba(5,150,105,.1); }
        .stat-card.amber   { --stat-color: var(--amber);   --stat-bg: rgba(234,179,8,.1); }
        .stat-card.red     { --stat-color: var(--red);     --stat-bg: rgba(239,68,68,.1); }
        .stat-card.blue    { --stat-color: var(--blue);    --stat-bg: rgba(59,130,246,.1); }
        .stat-card.navy    { --stat-color: var(--navy);    --stat-bg: rgba(15,23,42,.1);   }

        /* ═══════════════════════════════════════════════
           CARDS
        ═══════════════════════════════════════════════ */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        .card-header {
            padding: 1.125rem 1.375rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .card-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .9rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .card-body { padding: 1.375rem; }
        .card-footer {
            padding: 1rem 1.375rem;
            border-top: 1px solid var(--border);
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .625rem;
        }

        /* ═══════════════════════════════════════════════
           TABLES
        ═══════════════════════════════════════════════ */
        .table-wrap { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .845rem;
        }
        thead tr { background: #F8FAFC; }
        th {
            padding: .75rem 1rem;
            text-align: left;
            font-family: 'DM Sans', sans-serif;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        td {
            padding: .8125rem 1rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr { transition: background var(--transition); }
        tbody tr:hover td { background: #F8FAFC; }

        /* ═══════════════════════════════════════════════
           BADGES
        ═══════════════════════════════════════════════ */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .2rem .6rem;
            border-radius: 9999px;
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
        .badge-waiting         { background: var(--blue-light);  color: #1D4ED8; }
        .badge-called          { background: var(--amber-light); color: #92400E; }
        .badge-in_consultation { background: #D1FAE5; color: #065F46; }
        .badge-done            { background: #F1F5F9; color: #475569; }
        .badge-cancelled       { background: var(--red-light);   color: #991B1B; }
        .badge-pending         { background: var(--amber-light); color: #92400E; }
        .badge-dispensed       { background: #D1FAE5; color: #065F46; }
        .badge-emergency       { background: var(--red-light);   color: #991B1B; }
        .badge-normal          { background: #F1F5F9;            color: #475569; }
        .badge-active          { background: #D1FAE5; color: #065F46; }
        .badge-inactive        { background: #F1F5F9; color: #64748B; }
        .badge-admin           { background: rgba(15,23,42,.08); color: var(--navy); }
        .badge-doctor          { background: var(--blue-light);  color: #1D4ED8; }
        .badge-patient         { background: #F5F3FF; color: #5B21B6; }
        .badge-receptionist    { background: rgba(234,179,8,.12); color: #78350F; }
        .badge-apoteker        { background: rgba(5,150,105,.12); color: var(--emerald); }

        /* ═══════════════════════════════════════════════
           BUTTONS
        ═══════════════════════════════════════════════ */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            padding: .5625rem 1.125rem;
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: .845rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all var(--transition);
            white-space: nowrap;
            line-height: 1;
        }
        .btn:active { transform: scale(.97); }
        .btn svg { flex-shrink: 0; }

        .btn-primary   { background: var(--emerald); color: #fff; border-color: var(--emerald); }
        .btn-primary:hover { background: #047857; border-color: #047857; box-shadow: 0 0 0 3px rgba(5,150,105,.2); }

        .btn-navy      { background: var(--navy); color: #fff; border-color: var(--navy); }
        .btn-navy:hover { background: var(--navy-800); box-shadow: 0 0 0 3px rgba(15,23,42,.15); }

        .btn-danger    { background: var(--red); color: #fff; border-color: var(--red); }
        .btn-danger:hover { background: #DC2626; box-shadow: 0 0 0 3px rgba(239,68,68,.2); }

        .btn-outline   { background: transparent; border-color: var(--border-dark); color: var(--text-secondary); }
        .btn-outline:hover { background: var(--bg); border-color: var(--border-dark); color: var(--text-primary); }

        .btn-ghost     { background: transparent; border-color: transparent; color: var(--text-secondary); }
        .btn-ghost:hover { background: var(--bg); color: var(--text-primary); }

        .btn-sm { padding: .375rem .75rem; font-size: .775rem; }
        .btn-lg { padding: .75rem 1.5rem; font-size: .95rem; }

        /* ═══════════════════════════════════════════════
           FORMS
        ═══════════════════════════════════════════════ */
        .form-group { margin-bottom: 1.125rem; }
        .form-group:last-child { margin-bottom: 0; }
        .form-label {
            display: block;
            font-size: .75rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: .4rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .form-label span { color: var(--red); margin-left: .1rem; }

        input[type=text], input[type=email], input[type=password],
        input[type=number], input[type=date], input[type=search],
        select, textarea {
            width: 100%;
            padding: .625rem .875rem;
            background: var(--surface);
            border: 1px solid var(--border-dark);
            border-radius: var(--radius);
            font-family: 'DM Sans', sans-serif;
            font-size: .875rem;
            color: var(--text-primary);
            transition: border-color var(--transition), box-shadow var(--transition);
            appearance: none;
            -webkit-appearance: none;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--emerald);
            box-shadow: 0 0 0 3px rgba(5,150,105,.12);
        }
        input::placeholder, textarea::placeholder { color: var(--text-muted); }
        textarea { resize: vertical; min-height: 90px; line-height: 1.6; }
        select { cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394A3B8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right .75rem center; padding-right: 2.25rem; }

        /* Form Grid */
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .form-actions { display: flex; gap: .75rem; align-items: center; margin-top: 1.5rem; }

        /* ═══════════════════════════════════════════════
           QUEUE NUMBER DISPLAY
        ═══════════════════════════════════════════════ */
        .queue-num {
            font-family: 'Fira Code', monospace;
            font-weight: 700;
            color: var(--emerald);
        }
        .queue-num-lg { font-size: 2rem; }
        .queue-num-xl { font-size: 3rem; }

        /* ═══════════════════════════════════════════════
           DIVIDER
        ═══════════════════════════════════════════════ */
        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 1.5rem 0;
        }
        .divider-label {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1.25rem 0;
            color: var(--text-muted);
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .divider-label::before, .divider-label::after {
            content: '';
            flex: 1;
            border-top: 1px solid var(--border);
        }

        /* ═══════════════════════════════════════════════
           EMPTY STATE
        ═══════════════════════════════════════════════ */
        .empty-state {
            text-align: center;
            padding: 3.5rem 1.5rem;
            color: var(--text-muted);
        }
        .empty-state svg { opacity: .35; margin-bottom: 1rem; }
        .empty-state h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: .375rem;
        }
        .empty-state p { font-size: .875rem; }

        /* ═══════════════════════════════════════════════
           MOBILE RESPONSIVE
        ═══════════════════════════════════════════════ */
        @media (max-width: 1024px) {
            :root { --sidebar-w: 220px; }
            .form-grid-3 { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 64px;
            }
            .sidebar-brand { padding: 1rem .875rem; }
            .brand-text, .nav-item-label, .sidebar-user-info { display: none; }
            .brand-logo { justify-content: center; }
            .nav-item { justify-content: center; padding: .7rem; gap: 0; }
            .sidebar-footer { padding: .75rem; }
            .sidebar-user { justify-content: center; }
            .logout-btn { justify-content: center; }
            .main { margin-left: 64px; }
            .page-body { padding: 1.25rem; }
            .form-grid-2, .form-grid-3 { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .page-header { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="app-shell">

    <!-- ═══════════ SIDEBAR ═══════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="<?= $baseUrl ?>/dashboard" class="brand-logo">
                <div class="brand-icon" style="color: white;"><i data-lucide="activity" style="width: 20px; height: 20px;"></i></div>
                <div class="brand-text">
                    <div class="brand-name">Klinik Verdana</div>
                    <div class="brand-sub">Klinik &amp; Apotek</div>
                </div>
            </a>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Menu</div>
            <?php foreach ($navItems as $item):
                // ── Strict active detection ──────────────────────────────────
                $isActive = false;

                // 1. Exact match against any path listed in 'match'
                if (in_array($currentPath, $item['match'], true)) {
                    $isActive = true;
                }

                // 2. Prefix match (only when explicitly enabled for this item)
                //    e.g. 'queue' prefix=true → active for queue/call, queue/123, etc.
                //    Guard: only if current path is NOT 'dashboard' or ''
                //    to prevent dashboard from prefix-matching everything.
                if (!$isActive && $item['prefix'] && $currentPath !== '' && $currentPath !== 'dashboard') {
                    $itemRoot = $item['path'];
                    if ($currentPath === $itemRoot || str_starts_with($currentPath, $itemRoot . '/')) {
                        $isActive = true;
                    }
                }

                // 3. Manual override via $activeNav (set by controllers for edge cases)
                if (!$isActive && $activeNav !== '' && $activeNav === ($item['path'] ?? '')) {
                    $isActive = true;
                }
            ?>
            <a href="<?= $baseUrl ?>/<?= $item['path'] ?>"
               class="nav-item <?= $isActive ? 'active' : '' ?>"
               title="<?= htmlspecialchars($item['label']) ?>">
                <i data-lucide="<?= $item['icon'] ?>" style="width: 18px; height: 18px;"></i>
                <span class="nav-item-label"><?= $item['label'] ?></span>
            </a>
            <?php endforeach; ?>

            <!-- Public Queue link for all roles -->
            <div class="nav-section-label" style="margin-top:.75rem;">Lainnya</div>
            <a href="<?= $baseUrl ?>/public-queue" target="_blank" class="nav-item" title="Papan Antrian Publik">
                <i data-lucide="monitor" style="width: 18px; height: 18px;"></i>
                <span class="nav-item-label">Papan Publik</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name"><?= htmlspecialchars($user['name'] ?? '') ?></div>
                    <div class="sidebar-user-role"><?= htmlspecialchars($user['role'] ?? '') ?></div>
                </div>
            </div>
            <a href="<?= $baseUrl ?>/logout" class="logout-btn">
                <i data-lucide="log-out" style="width: 15px; height: 15px;"></i>
                <span>Keluar</span>
            </a>
        </div>
    </aside>

    <!-- ═══════════ MAIN ═══════════ -->
    <main class="main">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h1 class="topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
            <div class="topbar-right">
                <span class="topbar-time" id="topbar-clock"></span>
                <span class="topbar-role"><?= htmlspecialchars($user['role'] ?? '') ?></span>
            </div>
        </header>

        <!-- Page Body -->
        <div class="page-body">
            <!-- Flash Messages -->
            <?php if (!empty($_GET['success'])): ?>
                <div class="alert alert-success">
                    <span class="alert-icon"><i data-lucide="circle-check" style="width:16px;height:16px;"></i></span>
                    <span><?= htmlspecialchars(urldecode($_GET['success'])) ?></span>
                </div>
            <?php elseif (!empty($_GET['error'])): ?>
                <div class="alert alert-error">
                    <span class="alert-icon"><i data-lucide="circle-x" style="width:16px;height:16px;"></i></span>
                    <span><?= htmlspecialchars(urldecode($_GET['error'])) ?></span>
                </div>
            <?php elseif (!empty($_GET['info'])): ?>
                <div class="alert alert-info">
                    <span class="alert-icon"><i data-lucide="info" style="width:16px;height:16px;"></i></span>
                    <span><?= htmlspecialchars(urldecode($_GET['info'])) ?></span>
                </div>
            <?php elseif (!empty($_GET['notice'])): ?>
                <div class="alert alert-warning">
                    <span class="alert-icon"><i data-lucide="triangle-alert" style="width:16px;height:16px;"></i></span>
                    <span><?= htmlspecialchars(urldecode($_GET['notice'])) ?></span>
                </div>
            <?php endif; ?>
