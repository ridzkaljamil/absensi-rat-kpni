<?php
/**
 * @var array $rat
 * @var array $dataSesi
 * @var int   $totalAktif
 */
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<style>
    @keyframes spin {
        0% {
            transform: rotate(0)
        }

        100% {
            transform: rotate(360deg)
        }
    }
</style>

<div class="max-w-6xl mx-auto px-5 py-8">

    <div class="page-header">
        <div>
            <h1 class="page-title">Rekap Absensi</h1>
            <p class="page-subtitle"><?= esc($rat['nama_rat']) ?> · <?= esc($totalAktif) ?> peserta RAT</p>
        </div>
        <div class="page-actions">
            <a href="/rat" class="btn btn-ghost btn-sm"><i class="ti ti-refresh"></i> Ganti RAT</a>
        </div>
    </div>

    <!-- Per sesi -->
    <div class="card mb-6">
        <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:10px;">
            <div
                style="width:32px;height:32px;background:#E8F4FD;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-chart-bar" style="font-size:16px;color:#0960A8;"></i>
            </div>
            <div>
                <div style="font-size:14px;font-weight:700;color:#022760;">Download Rekap Per Sesi</div>
                <div style="font-size:12px;color:#6b7280;">Daftar hadir, waktu absen, dan metode (RFID/Manual) per sesi
                </div>
            </div>
        </div>
        <div class="rekap-grid">
            <?php foreach ($dataSesi as $s): ?>
                <div class="rekap-cell">
                    <div style="font-size:13px;font-weight:700;color:#022760;margin-bottom:4px;"><?= esc(label_sesi($s['nama_sesi'])) ?></div>
                    <div style="font-size:32px;font-weight:800;color:#0960A8;margin-bottom:2px;">
                        <?= esc($s['jumlah_hadir']) ?>
                    </div>
                    <div style="font-size:11px;color:#6b7280;margin-bottom:12px;"><?= $s['total_peserta'] > 0 ? 'hadir dari ' . esc($s['total_peserta']) . ' peserta sesi' : 'belum ada daftar peserta' ?></div>
                    <span class="badge <?= $s['status'] === 'aktif' ? 'badge-active' : 'badge-locked' ?>"
                        style="margin-bottom:12px;display:inline-flex;">
                        <i class="ti <?= $s['status'] === 'aktif' ? 'ti-circle-check' : 'ti-lock' ?>"
                            style="font-size:11px;"></i>
                        <?= $s['status'] === 'aktif' ? 'Aktif' : 'Terkunci' ?>
                    </span>
                    <br>
                    <div style="display:flex;gap:6px;justify-content:center;">
                        <a href="/rekap/sesi/<?= esc($s['id']) ?>" class="btn btn-primary btn-sm"><i class="ti ti-download"></i> Excel</a>
                        <a href="/rekap/foto-sesi/<?= esc($s['id']) ?>" class="btn btn-teal btn-sm"><i class="ti ti-camera"></i> Foto</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Gabungan -->
    <div class="card p-6" style="margin-bottom:20px;">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div
                style="width:44px;height:44px;background:linear-gradient(135deg,#092650,#C9920F);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="ti ti-file-spreadsheet" style="font-size:22px;color:white;"></i>
            </div>
            <div style="flex:1;">
                <div style="font-size:15px;font-weight:700;color:#022760;margin-bottom:4px;">Download Rekap Gabungan
                </div>
                <div style="font-size:13px;color:#6b7280;margin-bottom:8px;">
                    1 file Excel dengan <?= count($dataSesi) + 1 ?> sheet:
                    <?= esc(implode(', ', array_map(static fn ($s) => label_sesi($s['nama_sesi']), $dataSesi))) ?>, dan
                    <strong>Summary Doorprize</strong>.
                </div>
                <div
                    style="background:#E8F4FD;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#064687;border-left:3px solid #009EE0;">
                    <i class="ti ti-star" style="font-size:12px;"></i>
                    Sheet <strong>Summary Doorprize</strong> otomatis menandai kolom "Eligible Doorprize" =
                    <strong>YA</strong> untuk peserta yang hadir di <strong>seluruh sesi</strong>. Tidak perlu hitung
                    manual.
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="/rekap/gabungan" class="btn btn-amber">
                        <i class="ti ti-download"></i> Download Rekap Gabungan (<?= count($dataSesi) + 1 ?> Sheet)
                    </a>
                    <a href="/rekap/cetak" target="_blank" class="btn btn-ghost">
                        <i class="ti ti-printer"></i> Cetak Daftar Hadir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Database -->
    <div class="card p-5">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <i class="ti ti-database-export" style="font-size:20px;color:#0960A8;"></i>
                    <span style="font-size:15px;font-weight:700;color:#022760;">Backup Database</span>
                </div>
                <p style="font-size:12px;color:#6b7280;margin:0;">Download seluruh data sistem. Disarankan sebelum dan
                    sesudah pelaksanaan RAT.</p>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="/rekap/backup/sql" class="btn btn-indigo" id="btn-sql"
                    onclick="document.getElementById('btn-sql').innerHTML='<i class=\'ti ti-loader-2\' style=\'animation:spin 0.8s linear infinite\'></i> Menyiapkan...';setTimeout(()=>{document.getElementById('btn-sql').innerHTML='<i class=\'ti ti-database-export\'></i> Download SQL';},4000)">
                    <i class="ti ti-database-export"></i> Download SQL
                </a>
                <a href="/rekap/backup/excel" class="btn btn-primary" id="btn-excel"
                    onclick="document.getElementById('btn-excel').innerHTML='<i class=\'ti ti-loader-2\' style=\'animation:spin 0.8s linear infinite\'></i> Menyiapkan...';setTimeout(()=>{document.getElementById('btn-excel').innerHTML='<i class=\'ti ti-file-spreadsheet\'></i> Download Excel';},5000)">
                    <i class="ti ti-file-spreadsheet"></i> Download Excel
                </a>
            </div>
        </div>
    </div>

</div>

<style>
    .rekap-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1px;background:#e5e7eb; }
    .rekap-cell { background:white;padding:20px;text-align:center; }
</style>

<?= $this->endSection() ?>