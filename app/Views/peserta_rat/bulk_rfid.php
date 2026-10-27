<?php
/**
 * @var array $rat
 * @var array $peserta
 * @var int $totalDone
 * @var int $totalAll
 * @var string $mode  'peserta' atau 'anggota'
 * @var string $backUrl
 */
$mode = $mode ?? 'peserta';
$backUrl = $backUrl ?? '/peserta-rat';
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div style="max-width:700px;margin:0 auto;padding:24px 20px;">
    <div class="page-header">
        <div>
            <h1 class="page-title">Pendaftaran RFID Massal</h1>
            <p class="page-subtitle"><?= esc($rat['nama_rat'] ?? 'Data Anggota') ?> · <?= $totalDone ?> / <?= $totalAll ?> terdaftar</p>
        </div>
        <a href="<?= $backUrl ?>" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left" style="font-size:13px;"></i> Kembali</a>
    </div>

    <!-- Progress -->
    <div class="card p-5 mb-4">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:13px;font-weight:600;color:#022760;">Progress Pendaftaran</span>
            <span style="font-size:14px;font-weight:700;color:#0960A8;"><span id="prog-done"><?= $totalDone ?></span> / <?= $totalAll ?></span>
        </div>
        <div style="background:#e5e7eb;border-radius:99px;height:10px;overflow:hidden;">
            <div id="prog-bar" style="background:linear-gradient(90deg,#0960A8,#009EE0);height:100%;border-radius:99px;transition:width 0.5s ease;width:<?= $totalAll > 0 ? round($totalDone/$totalAll*100) : 0 ?>%;"></div>
        </div>
        <div style="text-align:right;margin-top:4px;font-size:11px;color:#6b7280;"><?= $totalAll > 0 ? round($totalDone/$totalAll*100) : 0 ?>% selesai</div>
    </div>

    <?php if (empty($peserta)): ?>
        <div class="card p-8" style="text-align:center;">
            <div style="width:64px;height:64px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="ti ti-circle-check" style="font-size:32px;color:#16a34a;"></i>
            </div>
            <p style="font-size:18px;font-weight:700;color:#022760;margin-bottom:4px;">Semua Sudah Terdaftar!</p>
            <p style="font-size:13px;color:#6b7280;"><?= $totalAll ?> peserta sudah punya kartu RFID.</p>
        </div>
    <?php else: ?>

    <!-- Current peserta card -->
    <div class="card mb-4" id="card-current" style="border:2px solid #0960A8;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#022760,#0960A8);padding:16px 20px;color:white;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.6);margin-bottom:2px;">Peserta <span id="cur-idx">1</span> dari <?= count($peserta) ?></div>
            <div style="font-size:26px;font-weight:800;" id="cur-nama"><?= esc($peserta[0]['nama']) ?></div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
                <span style="background:rgba(0,158,224,0.2);padding:4px 14px;border-radius:20px;font-family:var(--font-mono);font-weight:700;font-size:14px;border:1px solid rgba(0,158,224,0.3);" id="cur-nip"><?= esc($peserta[0]['nip']) ?></span>
                <span style="background:rgba(255,255,255,0.08);padding:4px 14px;border-radius:20px;font-size:12px;" id="cur-dept"><?= esc($peserta[0]['departemen'] ?? '-') ?></span>
                <span style="background:rgba(255,255,255,0.08);padding:4px 14px;border-radius:20px;font-size:12px;" id="cur-section"><?= esc($peserta[0]['section'] ?? '-') ?></span>
                <?php if (!empty($peserta[0]['shift'])): ?>
                <span style="background:rgba(0,158,224,0.2);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;" id="cur-shift">Shift <?= esc($peserta[0]['shift']) ?></span>
                <?php else: ?>
                <span id="cur-shift"></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="padding:20px;">
            <div style="position:relative;">
                <i class="ti ti-nfc" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:22px;color:#0960A8;"></i>
                <input type="text" id="input-rfid" placeholder="Tap kartu RFID..." autofocus
                    style="width:100%;height:56px;border:2.5px solid #009EE0;border-radius:12px;padding:0 16px 0 48px;font-size:18px;text-align:center;letter-spacing:3px;outline:none;box-sizing:border-box;">
            </div>
            <div id="status-msg" style="text-align:center;margin-top:12px;font-size:14px;color:#6b7280;">
                <i class="ti ti-scan" style="font-size:16px;"></i> Menunggu tap kartu RFID...
            </div>
            <div style="text-align:center;margin-top:10px;">
                <button type="button" id="btn-lewati" class="btn btn-ghost btn-sm"><i class="ti ti-chevron-right"></i> Lewati (orangnya belum ada / belum punya kartu)</button>
            </div>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="card">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Baru Didaftarkan</div>
        <div id="riwayat" style="max-height:240px;overflow-y:auto;"></div>
        <div id="riwayat-empty" style="padding:24px;text-align:center;color:#9ca3af;font-size:13px;">Belum ada pendaftaran. Tap kartu untuk mulai.</div>
    </div>

    <script>window.KP_PAGE = <?= json_encode(['peserta' => array_map(static fn ($p) => ['id' => (int) $p['id'], 'nip' => $p['nip'], 'nama' => $p['nama'], 'departemen' => $p['departemen'] ?? '-', 'section' => $p['section'] ?? '-', 'shift' => $p['shift'] ?? ''], $peserta), 'totalDone' => (int) $totalDone, 'totalAll' => (int) $totalAll, 'saveUrl' => $mode === 'anggota' ? '/anggota/bulk-rfid-save' : '/peserta-rat/bulk-rfid-save'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="<?= aset('js/bulk-rfid.js') ?>"></script>
    <?php endif; ?>
</div>
<style>
@keyframes slideUp{0%{opacity:0;transform:translateY(8px)}100%{opacity:1;transform:translateY(0)}}
@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
</style>
<?= $this->endSection() ?>
