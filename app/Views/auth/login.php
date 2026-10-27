<!DOCTYPE html>
<html lang="id" style="background:linear-gradient(135deg,#022760 0%,#102A83 35%,#0960A8 65%,#064687 100%);">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Sistem Absensi RAT KPNI</title>
    <link rel="icon" type="image/png" href="/img/favicon.png">
    <link rel="stylesheet" href="<?= aset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/icons.css') ?>">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:var(--font-sans); min-height:100vh; overflow-x:hidden; overflow-y:auto; }

        /* BCD Background — CSS waves sama seperti main.php tapi dark */
        .login-bg {
            position:fixed; inset:0; overflow:hidden;
            background:linear-gradient(135deg,#022760 0%,#102A83 35%,#0960A8 65%,#064687 100%);
        }
        .login-wave { position:absolute;border-radius:50%;will-change:transform;transform-origin:center; }
        .login-wave:nth-child(1){width:140%;height:140%;top:-20%;left:-20%;background:rgba(0,158,224,0.06);animation:waveRot 18s linear infinite;}
        .login-wave:nth-child(2){width:120%;height:120%;top:-10%;left:-10%;background:rgba(255,255,255,0.02);animation:waveRot 24s linear infinite reverse;}
        .login-wave:nth-child(3){width:100%;height:100%;top:0;left:0;background:rgba(16,42,131,0.05);animation:waveRot 30s linear infinite;}
        @keyframes waveRot { from{transform:rotate(0)} to{transform:rotate(360deg)} }
        .login-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(0,158,224,0.07) 1px,transparent 1px),linear-gradient(90deg,rgba(0,158,224,0.07) 1px,transparent 1px);background-size:40px 40px;animation:gridMove 12s linear infinite; }
        @keyframes gridMove { from{background-position:0 0} to{background-position:40px 40px} }
        .login-glow { position:absolute;border-radius:50%;filter:blur(60px);will-change:transform;animation:glowFloat var(--dur,6s) ease-in-out infinite alternate; }
        .login-glow:nth-child(5){width:300px;height:300px;top:-80px;right:-60px;background:rgba(0,158,224,0.1);--dur:5s;}
        .login-glow:nth-child(6){width:250px;height:250px;bottom:-60px;left:-40px;background:rgba(9,96,168,0.12);--dur:7s;animation-delay:-2s;}
        .login-glow:nth-child(7){width:200px;height:200px;top:30%;right:10%;background:rgba(16,42,131,0.08);--dur:6s;animation-delay:-1s;}
        @keyframes glowFloat { from{transform:scale(1) translate(0,0)} to{transform:scale(1.3) translate(16px,-12px)} }
        /* D: Dots canvas */
        #login-dots { position:absolute;inset:0;width:100%;height:100%; }

        /* Card */
        .login-wrapper { position:relative;z-index:10;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px; }
        .login-card {
            background:rgba(255,255,255,0.96);
            backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px);
            border-radius:24px; padding:40px; width:100%; max-width:400px;
            box-shadow:0 24px 80px rgba(2,39,96,0.4),0 0 0 1px rgba(255,255,255,0.3);
            animation:cardIn 0.7s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes cardIn { 0%{opacity:0;transform:translateY(40px) scale(0.95)} 100%{opacity:1;transform:translateY(0) scale(1)} }
        @keyframes loginPulse { 0%,100%{transform:scale(1);opacity:0.7} 50%{transform:scale(1.06);opacity:1} }

        .welcome-title { background:linear-gradient(135deg,#022760,#0960A8,#009EE0);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:22px;font-weight:800;text-align:center; }
        .input-wrap { position:relative;margin-bottom:14px; }
        .input-icon { position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:16px;color:#6b7280; }
        .login-input { width:100%;height:46px;border:2px solid #e5e7eb;border-radius:10px;padding:0 44px;font-size:14px;outline:none;transition:all 0.2s;background:rgba(255,255,255,0.9); }
        .login-input:focus { border-color:#0960A8;box-shadow:0 0 0 4px rgba(9,96,168,0.1); }
        .eye-btn { position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:18px;color:#6b7280;padding:4px;line-height:1; }
        .login-btn { width:100%;height:48px;border:none;cursor:pointer;font-size:15px;font-weight:700;border-radius:10px;color:white;background:linear-gradient(135deg,#022760,#0960A8,#009EE0);background-size:200% auto;box-shadow:0 4px 20px rgba(9,96,168,0.4);transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);animation:btnShimmer 3s linear infinite; }
        .login-btn:hover { transform:translateY(-2px) scale(1.02);box-shadow:0 8px 28px rgba(9,96,168,0.5); }
        .login-btn:active { transform:scale(0.98); }
        @keyframes btnShimmer { 0%{background-position:200% center} 100%{background-position:-200% center} }
        .error-box { background:#fee2e2;border:1px solid #fca5a5;border-left:4px solid #b3262b;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:14px;color:#991b1b; }
    </style>
</head>
<body>

<!-- BCD Background -->
<div class="login-bg">
    <div class="login-wave"></div>
    <div class="login-wave"></div>
    <div class="login-wave"></div>
    <div class="login-grid"></div>
    <div class="login-glow"></div>
    <div class="login-glow"></div>
    <div class="login-glow"></div>
    <canvas id="login-dots"></canvas>
</div>

<!-- Login Card -->
<div class="login-wrapper">
    <div class="login-card" id="login-card">
        <div style="text-align:center;margin-bottom:20px;">
            <div style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:220px;height:220px;margin:0 auto -28px;">
                <canvas id="ripple-canvas" width="220" height="220" style="position:absolute;inset:0;z-index:0;pointer-events:none;"></canvas>
                <div style="position:absolute;width:120px;height:120px;border-radius:50%;background:radial-gradient(circle,rgba(9,96,168,0.14),transparent 70%);z-index:1;animation:loginPulse 2s ease-in-out infinite;"></div>
                <img src="/img/logo-kpni.png" style="position:relative;z-index:2;width:110px;height:auto;object-fit:contain;filter:drop-shadow(0 0 16px rgba(9,96,168,0.8)) drop-shadow(0 0 32px rgba(9,96,168,0.5));" alt="KPNI">
            </div>
            <div class="welcome-title">Selamat Datang</div>
            <p style="font-size:14px;font-weight:600;color:#022760;margin:4px 0 2px;">Sistem Absensi Digital RAT</p>
            <p style="font-size:12px;color:#6b7280;">Koperasi Konsumen Pekerja PT NOK Indonesia</p>
        </div>

        <?php if ($ok = session()->getFlashdata('success')): ?>
            <div class="error-box" style="background:#ecfdf5;border-color:#a7f3d0;border-left-color:#16a34a;color:#065f46;margin-bottom:12px;">
                <i class="ti ti-circle-check" style="font-size:18px;flex-shrink:0;"></i>
                <?= esc($ok) ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="error-box" id="login-error">
                <i class="ti ti-alert-triangle" style="font-size:18px;flex-shrink:0;"></i>
                <span><?= esc(session()->getFlashdata('error')) ?></span>
            </div>
        <?php else: ?>
            <div class="error-box" id="login-error" style="display:none;">
                <i class="ti ti-alert-triangle" style="font-size:18px;flex-shrink:0;"></i>
                <span></span>
            </div>
        <?php endif; ?>

        <form id="login-form" method="post" action="/login">
            <?= csrf_field() ?>
            <div style="margin-bottom:6px;">
                <label style="font-size:13px;font-weight:600;color:#022760;display:block;margin-bottom:6px;">Username</label>
                <div class="input-wrap">
                    <i class="ti ti-user input-icon"></i>
                    <input type="text" name="username" class="login-input" placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            <div style="margin-bottom:24px;">
                <label style="font-size:13px;font-weight:600;color:#022760;display:block;margin-bottom:6px;">Password</label>
                <div class="input-wrap" style="margin-bottom:4px;">
                    <i class="ti ti-lock input-icon"></i>
                    <input type="password" name="password" id="pwd-login" class="login-input" placeholder="Masukkan password" required>
                    <button type="button" class="eye-btn" id="lihat-pwd-login" aria-label="Tampilkan password"><i class="ti ti-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="login-btn" id="login-btn">
                <i class="ti ti-login" style="margin-right:6px;"></i>
                Masuk ke Sistem
            </button>
        </form>
        <p style="font-size:11px;color:#9ca3af;text-align:center;margin-top:20px;">© 2026 KPNI · Sistem Absensi Digital RAT</p>
    </div>
</div>

<script src="<?= aset('js/login.js') ?>"></script>
</body>
</html>
