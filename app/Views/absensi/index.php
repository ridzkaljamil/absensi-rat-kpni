<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div style="max-width:960px;margin:0 auto;padding:24px 20px;">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#022760,#102A83,#0960A8);border-radius:16px;padding:22px 26px;margin-bottom:20px;color:white;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(0,158,224,0.08);"></div>
        <div style="position:absolute;bottom:-20px;left:30%;width:80px;height:80px;border-radius:50%;background:rgba(127,255,180,0.05);"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;position:relative;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.5);"><?= esc($rat['nama_rat']) ?></div>
            <a href="/dashboard" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;background:rgba(255,255,255,0.12);color:white;border:1.5px solid rgba(255,255,255,0.3);transition:transform 0.2s ease,background 0.2s ease;" onmouseover="this.style.transform='translateY(-2px) scale(1.05)';this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.transform='';this.style.background='rgba(255,255,255,0.12)'"><i class="ti ti-arrow-left" style="font-size:13px;"></i> Dashboard</a>
        </div>
        <div style="display:flex;align-items:baseline;gap:12px;position:relative;">
            <div style="font-size:26px;font-weight:800;"><?= esc(strtoupper(label_sesi($sesi['nama_sesi']))) ?></div>
            <span style="font-size:14px;font-weight:400;opacity:0.7;"><?= esc(substr($sesi['waktu_mulai'],0,5)) ?> – <?= esc(substr($sesi['waktu_selesai'],0,5)) ?> WIB</span>
        </div>
        <div style="display:flex;align-items:center;gap:12px;margin-top:10px;position:relative;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:30px;height:30px;border-radius:8px;background:rgba(0,158,224,0.15);display:flex;align-items:center;justify-content:center;"><i class="ti ti-users" style="font-size:14px;color:#009EE0;"></i></div>
                <span id="jumlah-hadir" style="font-size:28px;font-weight:800;"><?= esc($jumlahHadir) ?></span>
                <span style="font-size:13px;color:#009EE0;">hadir dari <?= esc($totalAnggota) ?> peserta sesi ini</span>
            </div>
            <span style="font-size:12px;padding:3px 10px;border-radius:20px;font-weight:600;white-space:nowrap;<?= $sesi['status']==='aktif' ? 'background:rgba(0,158,224,0.25);color:#009EE0;' : 'background:rgba(255,255,255,0.15);color:rgba(255,255,255,0.6);' ?>"><?= $sesi['status']==='aktif' ? '● Aktif' : '🔒 Terkunci' ?></span>
        </div>
        <?php $persenHadir = (int) $totalAnggota > 0 ? min(100, (int) round($jumlahHadir / $totalAnggota * 100)) : 0; ?>
        <div style="margin-top:14px;position:relative;">
            <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:8px;">
                <span style="font-size:12px;color:rgba(255,255,255,0.65);padding-bottom:4px;">Persentase kehadiran</span>
                <span id="persen-hadir" style="font-size:32px;font-weight:800;line-height:1;letter-spacing:-.01em;"><?= $persenHadir ?>%</span>
            </div>
            <div style="height:8px;border-radius:99px;background:rgba(255,255,255,0.14);overflow:hidden;">
                <div id="bar-hadir" style="height:100%;width:<?= min(100, $persenHadir) ?>%;border-radius:99px;background:linear-gradient(90deg,#009EE0,#7FFFB4);transition:width 0.6s cubic-bezier(.22,1,.36,1);"></div>
            </div>
        </div>
    </div>
    <!-- Pilih sesi -->
    <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;">
        <?php foreach ($daftarSesi as $s): ?>
        <a href="/absensi/sesi/<?= esc($s['id']) ?>" class="sesi-btn" style="flex:1 1 120px;text-align:center;padding:8px 4px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:transform 0.2s ease,box-shadow 0.2s ease;<?= (int)$s['id']==(int)$sesi['id'] ? 'background:#022760;color:#009EE0;border:2px solid #009EE0;' : 'background:white;color:#6b7280;border:2px solid #e5e7eb;' ?>"><?= esc(label_sesi($s['nama_sesi'])) ?><?= $s['status']==='terkunci' ? ' 🔒' : '' ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ((int) $totalAnggota === 0 && $terbuka): ?>
    <div class="card p-4 mb-4" style="border-left:4px solid #C9920F;background:#fffbeb;color:#92650A;font-size:13px;">
        <i class="ti ti-alert-triangle"></i> <?= esc(label_sesi($sesi['nama_sesi'])) ?> belum punya daftar peserta, jadi semua absen akan ditolak.
        <?php if (session()->get('role')==='admin'): ?><a href="/peserta-rat?sesi=<?= esc($sesi['id']) ?>" style="color:#0960A8;font-weight:600;">Atur peserta sesi ini →</a><?php else: ?>Hubungi Admin.<?php endif; ?>
    </div>
    <?php endif; ?>
    <div id="banner-tutup" class="card p-4 mb-4" style="border-left:4px solid #C9920F;background:#fffbeb;color:#92650A;font-size:13px;<?= $terbuka ? 'display:none;' : '' ?>"><i class="ti ti-lock"></i> <span id="banner-tutup-teks"><?= esc($pesanTutup) ?></span> <?php if (session()->get('role')==='admin'): ?><a href="/sesi" style="color:#0960A8;font-weight:600;">Kelola sesi →</a><?php endif; ?></div>

    <!-- Notifikasi -->
    <div id="area-notif" style="margin-bottom:12px;"></div>

    <!-- Card input + kamera compact -->
    <div class="card p-5" style="margin-bottom:16px;">
        <div id="input-layout" style="display:flex;gap:14px;align-items:stretch;">

            <!-- Kamera compact (hidden default) -->
            <div id="col-kamera" style="display:none;flex:0 0 160px;">
                <div style="border-radius:10px;overflow:hidden;background:#022760;position:relative;height:100%;min-height:120px;">
                    <video id="cam-video" autoplay playsinline muted style="width:100%;height:100%;object-fit:cover;display:block;"></video>
                    <canvas id="cam-canvas" style="display:none;"></canvas>
                    <div style="position:absolute;bottom:4px;left:4px;background:rgba(0,0,0,0.6);color:#7FFFB4;font-size:9px;padding:2px 6px;border-radius:3px;font-weight:600;">● LIVE</div>
                </div>
            </div>

            <!-- Input + toggle -->
            <div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center;">
                <div style="font-size:12px;font-weight:600;color:#6b7280;text-align:center;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.05em;">Tap kartu RFID atau ketik NIP — Enter untuk absen</div>
                <div style="position:relative;">
                    <i class="ti ti-scan" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:20px;color:#0960A8;"></i>
                    <input type="text" id="input-absensi" placeholder="Tap kartu atau ketik NIP..." autocomplete="off" <?= $terbuka ? 'autofocus' : 'disabled' ?> style="width:100%;height:52px;border:2.5px solid #0960A8;border-radius:10px;padding:0 16px 0 46px;font-size:16px;text-align:center;letter-spacing:2px;outline:none;box-sizing:border-box;background:<?= $terbuka ? 'white' : '#f8fafc' ?>;">
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;">
                    <label for="toggle-kamera" style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;color:#6b7280;">
                        <div style="position:relative;width:36px;height:20px;"><input type="checkbox" id="toggle-kamera" style="opacity:0;width:0;height:0;position:absolute;"><span id="toggle-track" style="position:absolute;inset:0;background:#e5e7eb;border-radius:10px;transition:0.3s;"></span><span id="toggle-dot" style="position:absolute;top:2px;left:2px;width:16px;height:16px;background:white;border-radius:50%;transition:0.3s;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></span></div>
                        <i class="ti ti-camera" style="font-size:14px;"></i> Verifikasi Foto
                    </label>
                    <span id="camera-status" style="font-size:11px;color:#9ca3af;">Kamera mati</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 5 terakhir -->
    <div class="card">
        <div style="padding:10px 16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">5 Terakhir Absen</span>
            <div style="display:flex;align-items:center;gap:5px;"><div id="dot-koneksi" style="width:7px;height:7px;border-radius:50%;background:#16a34a;"></div><span id="label-koneksi" style="font-size:11px;color:#6b7280;">OK</span></div>
        </div>
        <div style="overflow-x:auto;">
        <table class="data-table" id="tabel-terbaru">
            <thead><tr><th>NIP</th><th>Nama</th><th>Departemen</th><th>Section</th><th>Shift</th><th>Waktu</th><th>Metode</th></tr></thead>
            <tbody>
                <?php if (empty($terbaruAbsen)): ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af;">Belum ada absensi.</td></tr>
                <?php else: foreach ($terbaruAbsen as $t): ?>
                <tr>
                    <td style="font-family:var(--font-mono);font-weight:600;color:#0960A8;"><?= esc($t['nip']) ?></td>
                    <td style="font-weight:600;"><?= esc($t['nama']) ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?= esc($t['departemen'] ?? '-') ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?= esc($t['section'] ?? '-') ?></td>
                    <td style="text-align:center;"><?php if(!empty($t['shift'])): ?><span class="badge badge-info"><?= esc($t['shift']) ?></span><?php else: ?>-<?php endif; ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?= esc($t['waktu']) ?></td>
                    <td><span class="badge <?= $t['metode']==='rfid'?'badge-active':'badge-info' ?>" style="font-size:10px;"><?= strtoupper($t['metode']) ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal konfirmasi data induk -->
<div id="modal-ki" style="display:none;position:fixed;inset:0;background:rgba(2,39,96,0.6);backdrop-filter:blur(8px);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)tutupKI()">
    <div style="background:white;border-radius:20px;padding:24px;width:90%;max-width:380px;box-shadow:0 24px 60px rgba(2,39,96,0.3);">
        <div style="text-align:center;margin-bottom:16px;"><div style="width:48px;height:48px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i class="ti ti-alert-triangle" style="font-size:22px;color:#92400e;"></i></div><h2 style="font-size:15px;font-weight:700;color:#111;margin:0 0 6px;">NIP Tidak Ada di Data Induk</h2></div>
        <div style="background:#E8F4FD;border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;"><div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span style="color:#6b7280;">Nama</span><span style="font-weight:600;color:#022760;" id="ki-nama"></span></div><div style="display:flex;justify-content:space-between;"><span style="color:#6b7280;">NIP</span><span style="font-family:var(--font-mono);font-weight:600;" id="ki-nip"></span></div></div>
        <input type="hidden" id="ki-sesi"><input type="hidden" id="ki-nipv"><input type="hidden" id="ki-met">
        <div style="display:flex;gap:8px;"><button onclick="tutupKI()" style="flex:1;height:38px;border:1.5px solid #0960A8;background:#E8F4FD;color:#064687;border-radius:8px;font-weight:600;cursor:pointer;">Batal</button><button onclick="submitKI()" style="flex:1;height:38px;background:linear-gradient(135deg,#0960A8,#009EE0);color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Tambahkan & Absen</button></div>
    </div>
</div>

<script>window.KP_PAGE = <?= json_encode(['idSesi' => (int) $sesi['id'], 'sesiAktif' => $terbuka, 'hadir' => (int) $jumlahHadir, 'totalPeserta' => (int) $totalAnggota, 'userId' => (int) session()->get('user_id')], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= aset('js/absensi.js') ?>"></script>
<style>@media(max-width:640px){#col-kamera{flex:0 0 120px !important;}#col-kamera video{min-height:100px !important;}.notif-orang{gap:14px !important;}.notif-orang img,.notif-orang>div:first-child>div:first-child{width:72px !important;height:72px !important;min-width:72px !important;}.notif-nama{font-size:21px !important;}}
.sesi-btn:hover{transform:translateY(-2px) scale(1.05);box-shadow:0 4px 12px rgba(0,0,0,0.15);}
@keyframes slideUp{0%{opacity:0;transform:translateY(8px)}100%{opacity:1;transform:translateY(0)}}</style>
<?= $this->endSection() ?>
