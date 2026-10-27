<!DOCTYPE html>
<html lang="id" style="background:linear-gradient(135deg,#022760 0%,#102A83 35%,#0960A8 65%,#064687 100%);">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Sistem Absensi RAT KPNI') ?></title>
    <link rel="icon" type="image/png" href="/img/favicon.png">
    <link rel="preload" href="/assets/fonts/poppins-400.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/poppins-700.woff2" as="font" type="font/woff2" crossorigin>
    <meta name="csrf-header" content="<?= csrf_header() ?>">
    <meta name="csrf-hash" content="<?= csrf_hash() ?>">
    <script src="<?= aset('js/app.js') ?>"></script>
    <link rel="stylesheet" href="<?= aset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/icons.css') ?>">
    <style>
        * { box-sizing:border-box; }
        /* Form 2 kolom di modal jadi 1 kolom di layar HP */
        @media (max-width:480px) { .modal-box [style*="grid-template-columns:1fr 1fr"] { grid-template-columns:minmax(0,1fr) !important; } }

        /* ── BCD Background ── */
        .bcd-bg {
            position:fixed; inset:0; z-index:0; pointer-events:none; overflow:hidden;
            background: linear-gradient(160deg, #f0f7ff 0%, #f8fafc 50%, #e8f4fd 100%);
        }
        /* C: Aurora waves — pure CSS, GPU accelerated */
        .bcd-wave { position:absolute;border-radius:50%;will-change:transform;transform-origin:center; }
        .bcd-wave:nth-child(1){width:140%;height:140%;top:-20%;left:-20%;background:rgba(9,96,168,0.04);animation:waveRot 18s linear infinite;animation-delay:calc(var(--bg-t,0s) * -1);}
        .bcd-wave:nth-child(2){width:120%;height:120%;top:-10%;left:-10%;background:rgba(0,158,224,0.05);animation:waveRot 24s linear infinite reverse;animation-delay:calc(var(--bg-t,0s) * -1);}
        .bcd-wave:nth-child(3){width:100%;height:100%;top:0;left:0;background:rgba(16,42,131,0.03);animation:waveRot 30s linear infinite;animation-delay:calc(var(--bg-t,0s) * -1);}
        @keyframes waveRot { from{transform:rotate(0)} to{transform:rotate(360deg)} }
        /* B: Grid */
        .bcd-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(9,96,168,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(9,96,168,0.04) 1px,transparent 1px);background-size:40px 40px;animation:gridMove 12s linear infinite;animation-delay:calc(var(--bg-t,0s) * -1); }
        @keyframes gridMove { from{background-position:0 0} to{background-position:40px 40px} }
        /* B: Glow */
        .bcd-glow { position:absolute;border-radius:50%;filter:blur(60px);will-change:transform;animation:glowFloat var(--dur,6s) ease-in-out infinite alternate;animation-delay:calc(var(--bg-t,0s) * -1 - var(--off,0s)); }
        .bcd-glow:nth-child(5){width:280px;height:280px;top:-60px;right:-40px;background:rgba(0,158,224,0.07);--dur:5s;}
        .bcd-glow:nth-child(6){width:220px;height:220px;bottom:-40px;left:5%;background:rgba(9,96,168,0.06);--dur:7s;--off:2s;}
        .bcd-glow:nth-child(7){width:180px;height:180px;top:40%;left:55%;background:rgba(16,42,131,0.05);--dur:6s;--off:1s;}
        @keyframes glowFloat { from{transform:scale(1) translate(0,0)} to{transform:scale(1.3) translate(16px,-12px)} }
        /* D: Dots canvas */
        #bcd-dots { position:absolute;inset:0;width:100%;height:100%; }

        /* ── Splash-active ── */
        body.splash-active > *:not(#splash) { visibility:hidden; }

        /* ── Transisi antarhalaman (View Transitions API, Chrome/Edge 126+) ──
           Halaman tetap dimuat dari server, tapi pergantiannya memudar halus
           tanpa layar putih. Navbar diberi nama sendiri supaya tetap diam. */
        @view-transition { navigation: auto; }
        ::view-transition-old(root), ::view-transition-new(root) { animation-duration: .18s; animation-timing-function: ease-out; }
        .nav-row-1 { view-transition-name: nav-atas; }
        .nav-row-2 { view-transition-name: nav-menu; }
        #bcd-bg { view-transition-name: latar; }
        ::view-transition-group(nav-atas), ::view-transition-group(nav-menu), ::view-transition-group(latar) { animation: none; }
        ::view-transition-old(latar) { display: none; }
        ::view-transition-new(latar) { animation: none; }
        /* Bukan kunjungan pertama: langsung tampil tanpa splash & tanpa fade latar */
        html.tanpa-splash #splash { display: none !important; }
        html.tanpa-splash #bcd-bg { opacity: 1 !important; transition: none !important; }
        @media (prefers-reduced-motion: reduce) { ::view-transition-group(*), ::view-transition-old(*), ::view-transition-new(*) { animation: none !important; } }

        /* ── Layout ── */
        .page-wrapper { position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column; }

        /* ── Buttons ── */
        .btn { display:inline-flex;align-items:center;gap:6px;border:none;cursor:pointer;font-size:13px;font-weight:600;border-radius:8px;padding:0 16px;height:36px;transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1);text-decoration:none;white-space:nowrap; }
        .btn-sm { height:30px;padding:0 12px;font-size:12px; }
        .btn-lg { height:44px;padding:0 24px;font-size:14px; }
        .btn:hover { transform:translateY(-2px); }
        .btn:active { transform:scale(0.97);transition-duration:0.1s; }
        .btn-primary { background:linear-gradient(135deg,#0960A8,#009EE0);color:white!important;box-shadow:0 2px 10px rgba(9,96,168,0.3); }
        .btn-primary:hover { box-shadow:0 6px 20px rgba(9,96,168,0.4); }
        .btn-edit    { background:linear-gradient(135deg,#064687,#009EE0);color:white!important;box-shadow:0 2px 10px rgba(6,70,135,0.3); }
        .btn-edit:hover { box-shadow:0 6px 20px rgba(6,70,135,0.4); }
        .btn-danger  { background:linear-gradient(135deg,#6B1F3A,#A8295A);color:white!important;box-shadow:0 2px 10px rgba(107,31,58,0.25); }
        .btn-danger:hover { box-shadow:0 6px 20px rgba(107,31,58,0.4); }
        .btn-indigo  { background:linear-gradient(135deg,#064687,#102A83);color:white!important;box-shadow:0 2px 10px rgba(16,42,131,0.3); }
        .btn-indigo:hover { box-shadow:0 6px 20px rgba(16,42,131,0.4); }
        .btn-teal    { background:linear-gradient(135deg,#0A5E6E,#0960A8);color:white!important;box-shadow:0 2px 10px rgba(10,94,110,0.3); }
        .btn-teal:hover { box-shadow:0 6px 20px rgba(10,94,110,0.4); }
        .btn-amber   { background:linear-gradient(135deg,#92650A,#C9920F);color:white!important;box-shadow:0 2px 10px rgba(146,101,10,0.25); }
        .btn-amber:hover { box-shadow:0 6px 20px rgba(146,101,10,0.35); }
        .btn-ghost   { background:#E8F4FD;color:#064687!important;border:1.5px solid #0960A8; }
        .btn-ghost:hover { background:#d4ebf9;box-shadow:0 4px 12px rgba(9,96,168,0.15); }

        /* ── Cards ── */
        .card { background:rgba(255,255,255,0.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-radius:16px;border:1px solid rgba(255,255,255,0.7);box-shadow:0 2px 16px rgba(2,39,96,0.06);transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
        .card-hover:hover { transform:translateY(-4px);box-shadow:0 12px 32px rgba(9,96,168,0.14); }
        .card-bounce, .sesi-bounce { backdrop-filter:none !important;-webkit-backdrop-filter:none !important;will-change:transform;transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1) !important; }
        .card-bounce:hover { transform:translateY(-2px) scale(1.02) !important;box-shadow:0 12px 32px rgba(2,39,96,0.2) !important; }
        .sesi-bounce:hover { transform:translateY(-2px) scale(1.02) !important;box-shadow:0 12px 32px rgba(2,39,96,0.12) !important; }
        .btn-bounce { will-change:transform;transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1) !important; }
        .btn-bounce:hover { transform:translateY(-2px) scale(1.04) !important;box-shadow:0 6px 20px rgba(0,0,0,0.12) !important; }

        /* ── Form ── */
        .input-field { display:block;width:100%;border:2px solid #e5e7eb;border-radius:8px;padding:0 14px;height:42px;font-size:14px;outline:none;transition:all 0.2s;background:rgba(255,255,255,0.9);color:#1a1a1a; }
        .input-field:focus { border-color:#0960A8;box-shadow:0 0 0 4px rgba(9,96,168,0.1); }
        select.input-field { cursor:pointer; }
        textarea.input-field { height:auto;padding:10px 14px;resize:vertical; }
        label.form-label { display:block;font-size:13px;font-weight:600;color:#022760;margin-bottom:6px; }

        /* ── Badges ── */
        .badge { display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600; }
        .badge-active  { background:#E8F4FD;color:#064687; }
        .badge-locked  { background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb; }
        .badge-success { background:#dcfce7;color:#15803d; }
        .badge-danger  { background:#fee2e2;color:#991b1b; }
        .badge-warning { background:#fef3c7;color:#92400e; }
        .badge-info    { background:#dbeafe;color:#1e40af; }

        /* ── Table ── */
        .data-table { width:100%;border-collapse:collapse;font-size:13px; }
        .data-table th { padding:10px 14px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;background:rgba(248,250,252,0.8);border-bottom:1px solid #e5e7eb; }
        .data-table td { padding:11px 14px;border-bottom:1px solid rgba(229,231,235,0.6);color:#1a1a1a; }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tbody tr:hover { background:rgba(232,244,253,0.6); }

        /* ── Modal ── */
        .modal-overlay { position:fixed;inset:0;background:rgba(2,39,96,0.45);backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center; }
        .modal-box { background:rgba(255,255,255,0.97);backdrop-filter:blur(20px);border-radius:20px;padding:28px;width:90%;max-width:420px;box-shadow:0 24px 60px rgba(2,39,96,0.25);animation:bounceIn 0.3s ease-out; }
        @keyframes toastIn { 0%{opacity:0;transform:translateX(60px)} 100%{opacity:1;transform:translateX(0)} }
        @keyframes bounceIn { 0%{opacity:0;transform:scale(0.92)} 70%{transform:scale(1.02)} 100%{opacity:1;transform:scale(1)} }

        /* ── Toast ── */
        .toast { position:fixed;top:24px;right:24px;z-index:9998;padding:12px 18px;border-radius:12px;font-size:13px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,0.15);display:flex;align-items:flex-start;gap:10px;max-width:360px; }
        .toast-success { background:rgba(2,39,96,0.95);color:white;border-left:4px solid #009EE0;animation:toastIn 0.3s ease-out; }
        .toast-error   { background:rgba(254,226,226,0.97);color:#991b1b;border-left:4px solid #b3262b;animation:toastIn 0.3s ease-out; }
        .toast-close   { margin-left:auto;cursor:pointer;opacity:0.6;background:none;border:none;color:inherit;font-size:16px; }

        /* ── Nav ── */
        .nav-item { display:flex;align-items:center;gap:5px;padding:0 12px;height:48px;border-radius:6px;color:rgba(255,255,255,0.72);font-size:14px;font-weight:500;text-decoration:none;transition:all 0.2s;border-bottom:2px solid transparent;white-space:nowrap; }
        .nav-item:hover { color:white;background:rgba(255,255,255,0.1); }
        .nav-item.active { color:#009EE0;background:rgba(0,158,224,0.15);border-bottom-color:#009EE0;font-weight:600; }

        /* ── Page utils ── */
        .page-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:24px; }
        .page-title  { font-size:20px;font-weight:700;color:#022760;margin:0; }
        .page-subtitle { font-size:13px;color:#6b7280;margin:2px 0 0; }
        .back-btn { display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#064687;text-decoration:none;padding:6px 12px;border-radius:6px;border:1.5px solid #0960A8;background:#E8F4FD;transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1); }
        .back-btn:hover { color:white;background:#022760;border-color:#022760;transform:translateY(-2px); }
        .back-btn:active { transform:scale(0.97); }

        /* ── Pagination ── */
        .pagination { display:flex!important;flex-wrap:wrap;gap:4px;list-style:none;margin:0;padding:0;justify-content:center; }
        .pagination li { display:inline-flex!important;margin:0!important; }
        .pagination li a,.pagination li span { display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 10px;border-radius:6px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid #e5e7eb;color:#064687;background:rgba(255,255,255,0.7);transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1); }
        .pagination li a:hover { transform:translateY(-2px);background:#E8F4FD!important;border-color:#0960A8!important; }
        .pagination li.active span { background:#0960A8!important;color:white!important;border-color:#0960A8!important; }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width:5px;height:5px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:rgba(9,96,168,0.3);border-radius:3px; }

        /* ── Responsive ── */
        .nav-user-info { display:block; }
        .brand-title, .brand-sub { white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        @media (max-width:768px) {
            .nav-user-info { display:none; }
            #mobile-toggle { display:flex!important; }
            .nav-row-2 { display:none!important; }
            .brand-logo { height:40px!important; }
            .brand { gap:10px!important; }
            .nav-row-1 { padding:0 14px!important; }
        }
        @media (min-width:769px) { #mobile-toggle { display:none!important; } }

        /* Splash glow blobs — CSS only, no JS dependency */
        #splash .splash-glow {
            position:absolute;border-radius:50%;filter:blur(60px);will-change:transform;
        }
        #splash .splash-glow:nth-child(2) {
            width:400px;height:400px;top:-100px;right:-80px;
            background:rgba(0,158,224,0.15);
            animation:splashFloat 5s ease-in-out infinite alternate;
        }
        #splash .splash-glow:nth-child(3) {
            width:300px;height:300px;bottom:-60px;left:-40px;
            background:rgba(9,96,168,0.18);
            animation:splashFloat 7s ease-in-out infinite alternate;animation-delay:-2s;
        }
        #splash .splash-glow:nth-child(4) {
            width:200px;height:200px;top:40%;left:50%;
            background:rgba(0,158,224,0.12);
            animation:splashFloat 6s ease-in-out infinite alternate;animation-delay:-1s;
        }
        @keyframes splashFloat {
            0%  { transform:scale(1) translate(0,0); opacity:0.7; }
            100%{ transform:scale(1.4) translate(20px,-15px); opacity:1; }
        }

        /* ── Alignment fixes ── */
        .page-header .back-btn { height:36px;display:inline-flex;align-items:center; }
        .page-header { flex-wrap:wrap;gap:12px; }
        .page-actions { display:flex;gap:8px;align-items:center;flex-wrap:wrap; }

        /* ── Grid responsif (dipakai stat card & kartu sesi) ── */
        .grid-resp { display:grid;gap:16px; }
        .grid-resp-3 { grid-template-columns:repeat(3,minmax(0,1fr)); }
        .grid-resp-auto { grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); }
        @media (max-width:900px) { .grid-resp-3 { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:640px) { .grid-resp-3 { grid-template-columns:minmax(0,1fr); } }

        /* ── Baris filter (pencarian + dropdown) ── */
        .filter-row { display:flex;gap:8px;align-items:center; }
        .filter-row .input-field { height:36px;font-size:12px;min-width:0; }
        @media (max-width:768px) {
            .filter-row { flex-wrap:wrap; }
            .filter-row > * { flex:1 1 calc(50% - 8px) !important; }
            .filter-row > input[type=text] { flex-basis:100% !important; }
        }

        /* ── Tabel: di layar sempit tabel di-scroll, bukan merusak lebar halaman ── */
        .table-scroll { overflow-x:auto;-webkit-overflow-scrolling:touch; }

        /* Skeleton loading */
        @keyframes shimmer { 0%{background-position:-400px 0} 100%{background-position:400px 0} }
        .skeleton { background:linear-gradient(90deg,rgba(229,231,235,0.6) 25%,rgba(229,231,235,0.3) 50%,rgba(229,231,235,0.6) 75%); background-size:800px 100%; animation:shimmer 1.5s infinite; border-radius:8px; }

        /* ── Animasi halus global ───────────────────────────────────── */
        :root { --kp-ease: cubic-bezier(.22,1,.36,1); }
        @keyframes kpMuncul  { from { opacity:0; transform:translateY(10px); } }
        @keyframes kpFade    { from { opacity:0; } }
        @keyframes kpFadeOut { to   { opacity:0; } }
        @keyframes kpZoomOut { to   { opacity:0; transform:scale(.96); } }
        @keyframes kpTurun   { from { opacity:0; transform:translateY(-8px); } }
        @keyframes kpBar     { from { width:0; } }
        @keyframes kpPop     { 40% { transform:scale(1.12); } }
        a, button, .btn, .badge, .input-field, .nav-item, .data-table tbody tr { transition-timing-function: var(--kp-ease); }
        .btn:focus-visible, .input-field:focus-visible, a:focus-visible { outline:none; box-shadow:0 0 0 3px rgba(0,158,224,.35); }
        .data-table tbody tr { transition: background-color .2s; }
        .toast { transition: opacity .4s var(--kp-ease), transform .4s var(--kp-ease); }
        .kp-pop { display:inline-block; animation: kpPop .45s var(--kp-ease); }
        @media (prefers-reduced-motion: no-preference) {
            /* Isi halaman muncul bertahap (backwards = tidak mengunci transform saat hover) */
            #main-content .page-header { animation: kpMuncul .45s var(--kp-ease) backwards; }
            #main-content .card { animation: kpMuncul .5s var(--kp-ease) backwards; animation-delay: .06s; }
            #main-content .grid-resp > :nth-child(1) { animation-delay: .06s; }
            #main-content .grid-resp > :nth-child(2) { animation-delay: .11s; }
            #main-content .grid-resp > :nth-child(3) { animation-delay: .16s; }
            #main-content .grid-resp > :nth-child(4) { animation-delay: .21s; }
            #main-content .grid-resp > :nth-child(5) { animation-delay: .26s; }
            #main-content .grid-resp > :nth-child(n+6) { animation-delay: .3s; }
            #main-content .data-table tbody tr { animation: kpFade .35s ease-out backwards; }
            #main-content .data-table tbody tr:nth-child(2) { animation-delay: .03s; }
            #main-content .data-table tbody tr:nth-child(3) { animation-delay: .06s; }
            #main-content .data-table tbody tr:nth-child(4) { animation-delay: .09s; }
            #main-content .data-table tbody tr:nth-child(5) { animation-delay: .12s; }
            #main-content .data-table tbody tr:nth-child(6) { animation-delay: .15s; }
            #main-content .data-table tbody tr:nth-child(n+7) { animation-delay: .18s; }
            .progress-fill { animation: kpBar .9s var(--kp-ease) backwards .2s; }
            .tab-section { animation: kpMuncul .35s var(--kp-ease); }
            #mobile-menu { animation: kpTurun .25s var(--kp-ease); }
            .modal-overlay { animation: kpFade .2s ease-out; }
            .modal-overlay.is-closing { animation: kpFadeOut .18s ease-in forwards; }
            .modal-overlay.is-closing .modal-box { animation: kpZoomOut .18s ease-in forwards; }
        }

        /* Responsive mobile */
        @media (max-width: 768px) {
            .data-table { display:block;overflow-x:auto;white-space:nowrap;-webkit-overflow-scrolling:touch; }
        }
        @media (max-width: 640px) {
            .page-header { flex-direction:column;align-items:flex-start;gap:8px; }
            .data-table { font-size:12px; }
            .data-table th, .data-table td { padding:8px 10px; }
            .btn { font-size:12px;padding:0 12px;height:32px; }
            .btn-sm { height:28px;padding:0 10px;font-size:11px; }
            .card { border-radius:12px; }
            .page-title { font-size:18px; }
        }
    </style>
</head>
<body style="background:linear-gradient(135deg,#022760 0%,#102A83 35%,#0960A8 65%,#064687 100%);min-height:100vh;">
<script>if (!document.documentElement.classList.contains('tanpa-splash')) document.body.classList.add('splash-active');</script>

<!-- BCD Background -->
<div class="bcd-bg" id="bcd-bg" style="opacity:0;transition:opacity 0.8s ease;">
    <div class="bcd-wave"></div>
    <div class="bcd-wave"></div>
    <div class="bcd-wave"></div>
    <div class="bcd-grid"></div>
    <div class="bcd-glow"></div>
    <div class="bcd-glow"></div>
    <div class="bcd-glow"></div>
    <canvas id="bcd-dots"></canvas>
</div>

<div id="nav-transition"></div>
<div class="page-wrapper">

<?php if (session()->get('isLoggedIn')):
    $cp = '/' . ltrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $navItems = [
        ['href'=>'/dashboard','label'=>'Dashboard',    'icon'=>'ti-home',          'roles'=>['admin','user']],
        ['href'=>'/rat',      'label'=>'RAT',          'icon'=>'ti-clipboard-list','roles'=>['admin']],
        ['href'=>'/sesi',     'label'=>'Kelola Sesi',  'icon'=>'ti-clock',         'roles'=>['admin']],
        ['href'=>'/anggota',  'label'=>'Data Anggota', 'icon'=>'ti-users',          'roles'=>['admin']],
        ['href'=>'/peserta-rat','label'=>'Peserta RAT','icon'=>'ti-clipboard-check','roles'=>['admin']],
        ['href'=>'/doorprize','label'=>'Doorprize','icon'=>'ti-gift','roles'=>['admin']],
        ['href'=>'/absensi',  'label'=>'Absensi',      'icon'=>'ti-checkup-list',  'roles'=>['admin','user']],
        ['href'=>'/rekap',    'label'=>'Rekap',        'icon'=>'ti-chart-bar',     'roles'=>['admin']],
        ['href'=>'/log',      'label'=>'Log',          'icon'=>'ti-file-text',     'roles'=>['admin']],
        ['href'=>'/users',    'label'=>'Kelola User',  'icon'=>'ti-settings',      'roles'=>['admin']],
    ];
?>
<!-- Navbar baris 1 -->
<div class="nav-row-1" style="background:rgba(2,39,96,0.95);backdrop-filter:blur(12px);padding:0 24px;height:64px;gap:12px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;border-bottom:1px solid rgba(0,158,224,0.2);">
    <div class="brand" style="display:flex;align-items:center;gap:14px;min-width:0;">
        <img src="/img/logo-kpni.png" class="brand-logo" style="height:50px;width:auto;object-fit:contain;mix-blend-mode:screen;flex-shrink:0;" alt="KPNI">
        <div style="min-width:0;">
            <div class="brand-title" style="font-size:16px;font-weight:700;color:white;line-height:1.3;">Sistem Absensi RAT</div>
            <div class="brand-sub" style="font-size:12px;color:#009EE0;line-height:1.3;">KPNI<?= session()->get('rat_dipilih_label') ? ' · ' . esc(session()->get('rat_dipilih_label')) : '' ?></div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
        <div class="nav-user-info" style="text-align:right;margin-right:4px;">
            <div style="font-size:14px;font-weight:600;color:white;line-height:1.3;"><?= esc(session()->get('nama_lengkap')) ?></div>
            <div style="font-size:12px;color:#009EE0;line-height:1.3;text-transform:capitalize;"><?= esc(session()->get('role')) ?></div>
        </div>
        <a href="/ganti-password" title="Ganti Password" style="width:38px;height:38px;background:rgba(255,255,255,0.1);border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,0.15);transition:all 0.2s;text-decoration:none;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
            <i class="ti ti-key" style="font-size:18px;color:rgba(255,255,255,0.8);"></i>
        </a>
        <form method="post" action="/logout" id="form-logout" style="margin:0;" onsubmit="event.preventDefault();showModal({title:'Keluar dari aplikasi?',message:'Anda perlu login lagi untuk melanjutkan.',confirmText:'Logout',type:'warning',onConfirm:()=>this.submit()})">
            <?= csrf_field() ?>
            <button type="submit" title="Logout" aria-label="Logout" style="width:38px;height:38px;background:rgba(168,41,90,0.3);border-radius:8px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(168,41,90,0.4);transition:all 0.2s;cursor:pointer;padding:0;" onmouseover="this.style.background='rgba(168,41,90,0.6)'" onmouseout="this.style.background='rgba(168,41,90,0.3)'">
                <i class="ti ti-logout" style="font-size:18px;color:white;"></i>
            </button>
        </form>
        <button id="mobile-toggle" style="display:none;width:38px;height:38px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);border-radius:8px;align-items:center;justify-content:center;cursor:pointer;" onclick="toggleMobileMenu()">
            <i class="ti ti-menu-2" style="font-size:20px;color:white;"></i>
        </button>
    </div>
</div>
<!-- Navbar baris 2 -->
<div class="nav-row-2" style="background:rgba(9,96,168,0.9);backdrop-filter:blur(12px);padding:0 8px;height:48px;display:flex;align-items:center;gap:2px;overflow-x:auto;scrollbar-width:none;position:sticky;top:64px;z-index:99;border-bottom:1px solid rgba(0,158,224,0.15);">
    <?php
    $userRole = session()->get('role');
    foreach ($navItems as $item):
        if (!in_array($userRole, $item['roles'])) continue;
        $active = str_starts_with($cp, $item['href']);
    ?>
        <a href="<?= $item['href'] ?>" class="nav-item <?= $active ? 'active' : '' ?>" style="height:48px;font-size:14px;padding:0 16px;">
            <i class="ti <?= $item['icon'] ?>" style="font-size:15px;"></i>
            <?= $item['label'] ?>
        </a>
    <?php endforeach; ?>
</div>
<!-- Mobile menu -->
<div id="mobile-menu" style="display:none;background:rgba(6,70,135,0.97);backdrop-filter:blur(12px);padding:10px 14px;grid-template-columns:1fr 1fr;gap:6px;border-bottom:1px solid rgba(0,158,224,0.2);">
    <?php foreach ($navItems as $item):
        if (!in_array($userRole, $item['roles'])) continue;
        $active = str_starts_with($cp, $item['href']);
    ?>
        <a href="<?= $item['href'] ?>" style="display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;color:<?= $active?'#009EE0':'rgba(255,255,255,0.75)' ?>;font-size:14px;text-decoration:none;background:<?= $active?'rgba(0,158,224,0.15)':'transparent' ?>;transition:all 0.15s;">
            <i class="ti <?= $item['icon'] ?>" style="font-size:16px;"></i><?= $item['label'] ?>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<main class="flex-1 page-enter" id="main-content">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="toast toast-success" id="toast-ok">
            <i class="ti ti-circle-check" style="font-size:18px;flex-shrink:0;"></i>
            <span><?= esc(session()->getFlashdata('success')) ?></span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>
    <?php if (($daftarError = session()->getFlashdata('errors')) && ! session()->getFlashdata('error')): ?>
        <div class="toast toast-error" id="toast-err">
            <i class="ti ti-alert-circle" style="font-size:18px;flex-shrink:0;"></i>
            <span><?= esc(implode(' ', (array) $daftarError)) ?></span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="toast toast-error" id="toast-err">
            <i class="ti ti-alert-circle" style="font-size:18px;flex-shrink:0;"></i>
            <span><?= esc(session()->getFlashdata('error')) ?></span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endif; ?>
    <?= $this->renderSection('content') ?>
</main>

<!-- Global Modal -->
<div id="modal-overlay" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
            <div id="modal-icon" style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;background:#fee2e2;">⚠️</div>
            <div style="flex:1;">
                <h3 id="modal-title" style="font-size:16px;font-weight:700;color:#022760;margin:0 0 6px;">Konfirmasi</h3>
                <p id="modal-message" style="font-size:13px;color:#6b7280;margin:0;line-height:1.5;"></p>
            </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeModal()" class="btn btn-ghost">Batal</button>
            <button id="modal-btn" class="btn btn-danger">Hapus</button>
        </div>
    </div>
</div>

</div><!-- end page-wrapper -->

<!-- Splash Screen -->
<div id="splash" style="position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#022760 0%,#102A83 35%,#0960A8 65%,#064687 100%);opacity:1;transition:opacity 0.7s ease,transform 0.7s ease;">
    <div style="position:absolute;inset:0;overflow:hidden;pointer-events:none;">
        <div id="sgrid" style="position:absolute;inset:0;background-image:linear-gradient(rgba(0,158,224,0.12) 1px,transparent 1px),linear-gradient(90deg,rgba(0,158,224,0.12) 1px,transparent 1px);background-size:40px 40px;"></div>
        <div id="sgl1" class="splash-glow" style="position:absolute;width:400px;height:400px;border-radius:50%;background:rgba(0,158,224,0.12);filter:blur(80px);top:-100px;right:-80px;"></div>
        <div id="sgl2" class="splash-glow" style="position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(9,96,168,0.15);filter:blur(60px);bottom:-60px;left:-40px;"></div>
        <div id="sgl3" class="splash-glow" style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(16,42,131,0.12);filter:blur(50px);top:40%;left:50%;"></div>
        <div id="sw1" style="position:absolute;width:160%;height:160%;top:-30%;left:-30%;border-radius:50%;background:rgba(0,158,224,0.05);transform-origin:center;"></div>
        <div id="sw2" style="position:absolute;width:130%;height:130%;top:-15%;left:-15%;border-radius:50%;background:rgba(255,255,255,0.02);transform-origin:center;"></div>
    </div>
    <div id="splash-content" style="text-align:center;position:relative;z-index:2;opacity:0;transition:opacity 0.6s ease,transform 0.6s cubic-bezier(0.34,1.56,0.64,1);transform:translateY(12px);">
        <div style="position:relative;display:flex;align-items:center;justify-content:center;width:280px;height:280px;margin:0 auto 28px;">
            <canvas id="splash-ripple-canvas" width="280" height="280" style="position:absolute;inset:0;z-index:0;pointer-events:none;"></canvas>
            <div style="position:absolute;width:150px;height:150px;border-radius:50%;background:radial-gradient(circle,rgba(0,158,224,0.3),transparent 70%);animation:splashGlow 2s ease-in-out infinite;z-index:1;"></div>
            <div style="position:relative;z-index:2;">
                <img src="/img/logo-kpni.png" style="width:140px;height:auto;object-fit:contain;mix-blend-mode:screen;filter:drop-shadow(0 0 24px rgba(0,158,224,1)) drop-shadow(0 0 48px rgba(0,158,224,0.6));" alt="KPNI">
            </div>
        </div>
        <h1 style="font-size:24px;font-weight:800;color:white;margin:0 0 6px;">Sistem Absensi RAT</h1>
        <p style="font-size:13px;color:rgba(0,158,224,0.9);margin:0 0 6px;">Koperasi Konsumen Pekerja PT NOK Indonesia</p>
        <p id="splash-status" style="font-size:11px;color:rgba(255,255,255,0.5);margin:0 0 28px;min-height:16px;transition:opacity 0.3s;">Memuat sistem...</p>
        <div style="width:220px;height:4px;background:rgba(255,255,255,0.12);border-radius:4px;margin:0 auto;overflow:hidden;">
            <div id="splash-bar" style="height:100%;width:0%;background:linear-gradient(90deg,#009EE0,white,#009EE0);background-size:300% auto;border-radius:4px;transition:width 0.08s linear;animation:barShimmer 2s linear infinite;"></div>
        </div>
        <p style="font-size:10px;color:rgba(255,255,255,0.35);margin:10px 0 0;" id="splash-pct">0%</p>
    </div>
</div>

<style>
@keyframes splashGlow { 0%,100%{transform:scale(1);opacity:0.85} 50%{transform:scale(1.08);opacity:1} }
@keyframes barShimmer { 0%{background-position:300% center} 100%{background-position:-300% center} }
</style>

<script src="<?= aset('js/layout.js') ?>"></script>

</body>
</html>