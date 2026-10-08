<?php
/**
 * @var array $rat
 * @var array $daftarSesi  tiap sesi: data, peserta, kuorum
 * @var int   $totalAktif
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Absensi — <?= esc($rat['nama_rat']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Times New Roman', serif; font-size: 12pt; color: #000; background: white; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 14pt; font-weight: bold; margin-bottom: 4px; }
        .header p  { font-size: 11pt; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .info-box  { border: 1px solid #000; padding: 8px 12px; }
        .info-box .label { font-size: 9pt; color: #555; text-transform: uppercase; letter-spacing: 0.05em; }
        .info-box .value { font-size: 13pt; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; font-size: 10pt; margin-bottom: 16px; }
        th { background: #022760; color: white; padding: 7px 10px; text-align: left; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.05em; }
        td { padding: 6px 10px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) td { background: #f5f8ff; }
        .sesi-title { font-size: 12pt; font-weight: bold; color: #022760; border-left: 4px solid #0960A8; padding-left: 10px; margin: 20px 0 10px; }
        .footer { border-top: 2px solid #000; padding-top: 12px; display: flex; justify-content: space-between; font-size: 10pt; }
        .ttd-box { text-align: center; }
        .ttd-box .ttd-line { border-bottom: 1px solid #000; width: 180px; height: 60px; margin: 0 auto 4px; }
        @media print {
            @page { size: A4; margin: 1.5cm; }
            .no-print { display: none !important; }
            body { font-size: 11pt; }
        }
    </style>
</head>
<body>

<div class="no-print" style="background:#022760;color:white;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;font-family:system-ui;font-size:13px;">
    <span>Preview Cetak — <strong><?= esc($rat['nama_rat']) ?></strong></span>
    <div style="display:flex;gap:8px;">
        <button onclick="window.print()" style="background:#009EE0;color:white;border:none;border-radius:6px;padding:6px 16px;cursor:pointer;font-size:13px;font-weight:600;">Cetak</button>
        <button onclick="window.close()" style="background:rgba(255,255,255,0.2);color:white;border:none;border-radius:6px;padding:6px 12px;cursor:pointer;font-size:13px;">Tutup</button>
    </div>
</div>

<div style="padding:20px 24px;">

    <div class="header">
        <h1>REKAP ABSENSI</h1>
        <h1><?= strtoupper(esc($rat['nama_rat'])) ?></h1>
        <p>Koperasi Konsumen Pekerja PT NOK Indonesia (KPNI)</p>
        <?php if ($rat['tanggal']): ?>
            <p><?= esc(date('d F Y', strtotime($rat['tanggal']))) ?></p>
        <?php endif; ?>
        <?php if ($rat['lokasi']): ?>
            <p><?= esc($rat['lokasi']) ?></p>
        <?php endif; ?>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="label">Total Peserta RAT</div>
            <div class="value"><?= esc($totalAktif) ?> orang</div>
        </div>
        <div class="info-box">
            <div class="label">Jumlah Sesi</div>
            <div class="value"><?= count($daftarSesi) ?> sesi</div>
        </div>
        <div class="info-box">
            <div class="label">Dicetak pada</div>
            <div class="value" style="font-size:11pt;"><?= date('d/m/Y H:i') ?> WIB</div>
        </div>
    </div>

    <?php foreach ($daftarSesi as $sesi): ?>
        <div class="sesi-title">
            <?= esc(label_sesi($sesi['nama_sesi'])) ?>
            <span style="font-size:10pt;font-weight:normal;color:#555;margin-left:8px;">
                <?= esc(substr($sesi['waktu_mulai'],0,5)) ?> – <?= esc(substr($sesi['waktu_selesai'],0,5)) ?> WIB
                · <?= count($sesi['data']) ?> hadir dari <?= $sesi['peserta'] ?> peserta
                · kuorum <?= $sesi['kuorum'] ?>
                (<?= $sesi['peserta'] === 0 ? 'belum ada peserta' : (count($sesi['data']) >= $sesi['kuorum'] ? 'tercapai' : 'belum tercapai') ?>)
                · <?= $sesi['status']==='terkunci' ? 'Terkunci' : 'Aktif' ?>
            </span>
        </div>
        <?php if (empty($sesi['data'])): ?>
            <p style="color:#999;font-size:10pt;margin-bottom:16px;padding-left:14px;">Belum ada absensi di sesi ini.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">No.</th>
                    <th style="width:100px;">NIP</th>
                    <th>Nama Anggota</th>
                    <th>Departemen</th>
                    <th style="width:110px;">Waktu Absen</th>
                    <th style="width:70px;">Metode</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sesi['data'] as $i => $baris): ?>
                <tr>
                    <td style="text-align:center;"><?= $i + 1 ?></td>
                    <td style="font-family:monospace;"><?= esc($baris['nip']) ?></td>
                    <td><?= esc($baris['nama']) ?></td>
                    <td style="font-size:9pt;"><?= esc($baris['departemen'] ?? '-') ?></td>
                    <td><?= esc(date('H:i:s', strtotime($baris['waktu_absen']))) ?> WIB</td>
                    <td style="text-align:center;"><?= strtoupper(esc($baris['metode'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="footer" style="margin-top:32px;">
        <div>
            <p style="font-size:9pt;color:#555;">Dokumen ini digenerate otomatis oleh Sistem Absensi Digital RAT KPNI</p>
        </div>
        <div class="ttd-box">
            <div class="ttd-line"></div>
            <p>Sekretaris KPNI</p>
        </div>
    </div>

</div>
</body>
</html>
