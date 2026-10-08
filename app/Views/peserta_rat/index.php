<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $labelAktif = label_sesi($sesiAktif['nama_sesi']);
    $labelSblm  = $sesiSebelumnya ? label_sesi($sesiSebelumnya['nama_sesi']) : '';
?>
<div class="max-w-6xl mx-auto px-5 py-8 page-enter">
    <div class="page-header">
        <div>
            <h1 class="page-title">Peserta RAT</h1>
            <p class="page-subtitle"><?= esc($rat['nama_rat']) ?> · daftar peserta dicatat per sesi</p>
        </div>
        <div class="page-actions">
            <button type="button" onclick="bukaModal('modal-import')" class="btn btn-primary btn-sm"><i class="ti ti-upload"></i> Import</button>
            <button type="button" onclick="bukaModal('modal-tambah')" class="btn btn-edit btn-sm"><i class="ti ti-plus"></i> Tambah</button>
            <a href="/peserta-rat/bulk-rfid" class="btn btn-teal btn-sm"><i class="ti ti-nfc"></i> RFID Massal</a>
            <a href="/peserta-rat/export?sesi=<?= $idSesi ?>" class="btn btn-amber btn-sm"><i class="ti ti-download"></i> Excel</a>
            <?php if ($total > 0): ?>
                <button type="button" title="Hapus semua peserta <?= esc($labelAktif) ?>" class="btn btn-danger btn-sm"
                    onclick="showModal({title:'Hapus semua peserta <?= esc($labelAktif, 'js') ?>?',message:'<?= $total ?> peserta di sesi ini akan dihapus. Data absensi tidak ikut terhapus.',confirmText:'Hapus Semua',type:'danger',onConfirm:()=>document.getElementById('form-hapus-semua').submit()})"><i class="ti ti-trash"></i></button>
                <form id="form-hapus-semua" action="/peserta-rat/hapus-semua" method="post" style="display:none;"><?= csrf_field() ?><input type="hidden" name="id_sesi" value="<?= $idSesi ?>"></form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pilih sesi -->
    <div class="card p-4 mb-4">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span style="font-size:13px;font-weight:700;color:#022760;white-space:nowrap;"><i class="ti ti-clock" style="font-size:14px;"></i> Sesi:</span>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <?php foreach ($daftarSesi as $s):
                    $pilih  = (int) $s['id'] === $idSesi;
                    $jumlah = (int) ($pesertaPerSesi[$s['id']] ?? 0);
                ?>
                    <a href="/peserta-rat?sesi=<?= $s['id'] ?>" class="sesi-pill <?= $pilih ? 'is-active' : '' ?>">
                        <span><?= esc(label_sesi($s['nama_sesi'])) ?></span>
                        <span class="sesi-pill-time"><?= esc(substr($s['waktu_mulai'], 0, 5)) ?>–<?= esc(substr($s['waktu_selesai'], 0, 5)) ?></span>
                        <span class="sesi-pill-count <?= $jumlah === 0 ? 'is-empty' : '' ?>"><?= $jumlah ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($sesiSebelumnya): ?>
        <form id="form-salin" action="/peserta-rat/salin-kehadiran" method="post" style="display:none;"><?= csrf_field() ?><input type="hidden" name="id_sesi" value="<?= $idSesi ?>"></form>
    <?php endif; ?>

    <?php if ($total === 0): ?>
        <!-- Sesi belum punya peserta -->
        <div class="card p-6 mb-4" style="border-left:4px solid #C9920F;">
            <div style="display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap;">
                <div style="width:44px;height:44px;border-radius:12px;background:#fef3c7;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="ti ti-users-plus" style="font-size:22px;color:#92650A;"></i></div>
                <div style="flex:1;min-width:240px;">
                    <div style="font-size:15px;font-weight:700;color:#022760;margin-bottom:4px;"><?= esc($labelAktif) ?> belum punya daftar peserta</div>
                    <p style="font-size:13px;color:#6b7280;margin:0 0 14px;">
                        Kuorum sesi ini dihitung dari daftar peserta sesi ini sendiri. Selama daftarnya kosong, absen di sesi ini akan ditolak.
                        <?php if ($sesiSebelumnya): ?>Biasanya peserta sesi berikutnya = yang hadir di sesi sebelumnya.<?php endif; ?>
                    </p>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if ($sesiSebelumnya && $hadirSebelumnya > 0): ?>
                            <button type="button" class="btn btn-indigo btn-sm" onclick="document.getElementById('form-salin').submit()"><i class="ti ti-copy"></i> Salin <?= $hadirSebelumnya ?> orang yang hadir di <?= esc($labelSblm) ?></button>
                        <?php elseif ($sesiSebelumnya): ?>
                            <span class="badge badge-warning" style="height:30px;">Belum ada yang absen di <?= esc($labelSblm) ?></span>
                        <?php endif; ?>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="bukaModal('modal-import')"><i class="ti ti-upload"></i> Import Excel</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stat cards -->
    <div class="grid-resp grid-resp-3" style="margin-bottom:20px;">
        <div class="card card-hover stat-card" style="background:linear-gradient(135deg,#022760,#102A83);">
            <div class="stat-head"><span class="stat-icon" style="background:rgba(0,158,224,0.15);"><i class="ti ti-users" style="color:#009EE0;"></i></span>Total Peserta</div>
            <div class="stat-num"><?= $total ?></div>
            <div class="stat-sub" style="color:rgba(125,211,252,0.9);"><?= esc($labelAktif) ?></div>
        </div>
        <div class="card card-hover stat-card" style="background:linear-gradient(135deg,#064687,#0960A8);">
            <div class="stat-head"><span class="stat-icon" style="background:rgba(127,255,180,0.12);"><i class="ti ti-target" style="color:#7FFFB4;"></i></span>Kuorum (50% + 1)</div>
            <div class="stat-num"><?= $kuorum ?></div>
            <div class="stat-sub" style="color:rgba(127,255,180,0.8);">minimal hadir di <?= esc($labelAktif) ?></div>
        </div>
        <div class="card card-hover stat-card" style="background:linear-gradient(135deg,#0960A8,#009EE0);">
            <div class="stat-head"><span class="stat-icon" style="background:rgba(255,217,125,0.15);"><i class="ti ti-nfc" style="color:#FFD97D;"></i></span>RFID Terdaftar</div>
            <div class="stat-num"><?= $totalRfid ?></div>
            <div class="stat-sub" style="color:rgba(255,217,125,0.85);">dari <?= $total ?> peserta</div>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="card p-4 mb-4">
        <input type="hidden" name="sesi" value="<?= $idSesi ?>">
        <div class="filter-row">
            <input type="text" name="search" value="<?= esc($filter['search']) ?>" placeholder="Cari NIP / nama / departemen..." class="input-field" style="flex:2 1 200px;">
            <select name="departemen" class="input-field" style="flex:1.5 1 160px;"><option value="">Semua Dept</option><?php foreach ($deptList as $d): ?><option value="<?= esc($d) ?>" <?= $filter['departemen'] === $d ? 'selected' : '' ?>><?= esc($d) ?></option><?php endforeach; ?></select>
            <select name="section" class="input-field" style="flex:1.5 1 160px;"><option value="">Semua Section</option><?php foreach ($sectionList as $sc): ?><option value="<?= esc($sc) ?>" <?= $filter['section'] === $sc ? 'selected' : '' ?>><?= esc($sc) ?></option><?php endforeach; ?></select>
            <select name="shift" class="input-field" style="flex:0.8 1 90px;"><option value="">Shift</option><?php foreach (['A', 'B', 'N'] as $sh): ?><option value="<?= $sh ?>" <?= $filter['shift'] === $sh ? 'selected' : '' ?>><?= $sh ?></option><?php endforeach; ?></select>
            <select name="rfid" class="input-field" style="flex:1 1 110px;"><option value="">RFID</option><option value="ada" <?= $filter['rfid'] === 'ada' ? 'selected' : '' ?>>Sudah</option><option value="belum" <?= $filter['rfid'] === 'belum' ? 'selected' : '' ?>>Belum</option></select>
            <button type="submit" class="btn btn-primary" style="height:36px;flex-shrink:0;"><i class="ti ti-filter"></i> Filter</button>
            <?php if ($adaFilter): ?><a href="/peserta-rat?sesi=<?= $idSesi ?>" class="btn btn-ghost" style="height:36px;flex-shrink:0;"><i class="ti ti-x"></i> Reset</a><?php endif; ?>
        </div>
    </form>

    <!-- Tabel -->
    <div class="card" style="overflow:hidden;">
        <div class="table-scroll">
            <table class="data-table" style="min-width:860px;">
                <thead><tr>
                    <th style="width:50px;">No</th><th style="width:90px;">NIP</th><th>Nama</th><th style="width:160px;">Departemen</th><th>Section</th>
                    <th style="width:60px;text-align:center;">Shift</th><th style="width:140px;text-align:center;">RFID</th><th style="width:60px;text-align:center;">Aksi</th>
                </tr></thead>
                <tbody>
                    <?php if (empty($peserta)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:#6b7280;">
                            <?= $adaFilter ? 'Tidak ada peserta yang cocok dengan filter.' : 'Belum ada peserta di ' . esc($labelAktif) . '.' ?>
                        </td></tr>
                    <?php else: foreach ($peserta as $i => $p): ?>
                        <tr>
                            <td style="color:#9ca3af;font-size:12px;"><?= $nomorAwal + $i + 1 ?></td>
                            <td style="font-family:var(--font-mono);font-weight:600;color:#0960A8;"><?= esc($p['nip']) ?></td>
                            <td style="font-weight:500;"><?= esc($p['nama']) ?></td>
                            <td style="color:#6b7280;font-size:12px;"><?= esc($p['departemen'] ?: '—') ?></td>
                            <td style="color:#6b7280;font-size:12px;"><?= esc($p['section'] ?: '—') ?></td>
                            <td style="text-align:center;"><?php if ($p['shift']): ?><span class="badge badge-info"><?= esc($p['shift']) ?></span><?php else: ?>—<?php endif; ?></td>
                            <td style="text-align:center;">
                                <?php if ($p['uid_rfid']): ?>
                                    <span class="rfid-chip"><?= esc($p['uid_rfid']) ?></span>
                                <?php else: ?>
                                    <button type="button" onclick="bukaRfid(<?= (int) $p['id'] ?>,'<?= esc($p['nama'], 'js') ?>')" class="btn btn-teal btn-sm" style="height:26px;font-size:11px;"><i class="ti ti-nfc" style="font-size:12px;"></i> Daftarkan</button>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <button type="button" title="Hapus dari sesi ini" class="btn btn-danger btn-sm" style="height:26px;padding:0 8px;"
                                    onclick="showModal({title:'Hapus peserta?',message:'<?= esc($p['nama'], 'js') ?> akan dihapus dari daftar peserta <?= esc($labelAktif, 'js') ?>.',confirmText:'Hapus',type:'danger',onConfirm:()=>document.getElementById('fh-<?= (int) $p['id'] ?>').submit()})"><i class="ti ti-trash" style="font-size:12px;"></i></button>
                                <form id="fh-<?= (int) $p['id'] ?>" action="/peserta-rat/<?= (int) $p['id'] ?>/hapus" method="post" style="display:none;"><?= csrf_field() ?></form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pager->getPageCount() > 1): ?>
            <div style="padding:12px 16px;border-top:1px solid #e5e7eb;"><?= $pager->links('default', 'kpni_pager') ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Import -->
<div id="modal-import" style="display:none;" class="modal-overlay" onclick="if(event.target===this)tutupModal(this.id)">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-head">
            <span class="modal-head-icon"><i class="ti ti-upload"></i></span>
            <div>
                <h3>Import Peserta <?= esc($labelAktif) ?></h3>
                <p>File Excel/CSV dengan kolom <b>NIP</b> dan <b>Nama</b> (opsional: Departemen, Section, Shift, RFID). NIP yang sudah ada di sesi ini akan diperbarui.</p>
            </div>
        </div>
        <form method="POST" action="/peserta-rat/import" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id_sesi" value="<?= $idSesi ?>">
            <input type="file" name="file_excel" accept=".xlsx,.xls,.csv" required class="input-field" style="height:auto;padding:8px 14px;">
            <div class="modal-foot">
                <button type="button" onclick="tutupModal('modal-import')" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-upload"></i> Import</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modal-tambah" style="display:none;" class="modal-overlay" onclick="if(event.target===this)tutupModal(this.id)">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-head">
            <span class="modal-head-icon"><i class="ti ti-user-plus"></i></span>
            <div><h3>Tambah Peserta <?= esc($labelAktif) ?></h3><p>Ketik NIP — nama dan departemen terisi otomatis dari data induk.</p></div>
        </div>
        <form method="POST" action="/peserta-rat/store">
            <?= csrf_field() ?>
            <input type="hidden" name="id_sesi" value="<?= $idSesi ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div><label class="form-label">NIP *</label><input type="text" name="nip" required maxlength="10" class="input-field" inputmode="numeric"></div>
                <div><label class="form-label">Shift</label><select name="shift" class="input-field"><option value="">—</option><option>A</option><option>B</option><option>N</option></select></div>
            </div>
            <div style="margin-bottom:12px;"><label class="form-label">Nama *</label><input type="text" name="nama" required class="input-field"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><label class="form-label">Departemen</label><select name="departemen" class="input-field"><option value="">—</option><?php foreach ($deptList as $d): ?><option><?= esc($d) ?></option><?php endforeach; ?></select></div>
                <div><label class="form-label">Section</label><select name="section" class="input-field"><option value="">—</option><?php foreach ($sectionList as $sc): ?><option><?= esc($sc) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="modal-foot">
                <button type="button" onclick="tutupModal('modal-tambah')" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary">Tambahkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal RFID -->
<div id="modal-rfid" style="display:none;" class="modal-overlay" onclick="if(event.target===this)tutupModal(this.id)">
    <div class="modal-box">
        <div class="modal-head">
            <span class="modal-head-icon"><i class="ti ti-nfc"></i></span>
            <div><h3>Daftarkan RFID</h3><p id="rfid-nama"></p></div>
        </div>
        <form method="POST" id="form-rfid">
            <?= csrf_field() ?>
            <input type="text" name="uid_rfid" id="input-rfid" required placeholder="Tap kartu..." class="input-field" autocomplete="off">
            <p style="font-size:12px;color:#6b7280;margin:8px 0 0;">Kartu berlaku untuk semua sesi RAT ini.</p>
            <div class="modal-foot">
                <button type="button" onclick="tutupModal('modal-rfid')" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<style>
    .sesi-pill { display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 6px 0 14px;border-radius:9px;font-size:13px;font-weight:600;text-decoration:none;color:#064687;background:#fff;border:1.5px solid #dbe3ee;transition:all .2s; }
    .sesi-pill:hover { border-color:#0960A8;background:#E8F4FD; }
    .sesi-pill.is-active { background:linear-gradient(135deg,#0960A8,#009EE0);color:#fff;border-color:transparent;box-shadow:0 2px 10px rgba(9,96,168,.3); }
    .sesi-pill-time { font-size:11px;font-weight:500;opacity:.7; }
    .sesi-pill-count { min-width:26px;height:22px;padding:0 7px;border-radius:7px;font-size:11px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;background:#E8F4FD;color:#064687; }
    .sesi-pill.is-active .sesi-pill-count { background:rgba(255,255,255,.22);color:#fff; }
    .sesi-pill-count.is-empty { background:#fef3c7;color:#92400e; }
    .stat-card { border:none;padding:20px 24px;cursor:default;backdrop-filter:none;-webkit-backdrop-filter:none; }
    .stat-head { display:flex;align-items:center;gap:8px;margin-bottom:12px;font-size:11px;color:rgba(255,255,255,.65);font-weight:600;text-transform:uppercase;letter-spacing:.05em; }
    .stat-icon { width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;font-size:16px; }
    .stat-num { font-size:36px;font-weight:800;color:#fff;line-height:1; }
    .stat-sub { font-size:12px;margin-top:6px; }
    .rfid-chip { font-family:var(--font-mono);font-size:11px;background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:5px; }
    .modal-head { display:flex;align-items:flex-start;gap:14px;margin-bottom:18px; }
    .modal-head h3 { font-size:16px;font-weight:700;color:#022760;margin:0 0 4px; }
    .modal-head p { font-size:13px;color:#6b7280;margin:0;line-height:1.5; }
    .modal-head-icon { width:44px;height:44px;border-radius:12px;background:#E8F4FD;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#0960A8;font-size:20px; }
    .modal-foot { display:flex;gap:10px;justify-content:flex-end;margin-top:16px; }
</style>

<script>
var MODAL_IDS = ['modal-import', 'modal-tambah', 'modal-rfid'];
function bukaModal(id) { document.getElementById(id).style.display = 'flex'; }
function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
function bukaRfid(pid, nama) {
    document.getElementById('rfid-nama').textContent = 'Peserta: ' + nama;
    document.getElementById('form-rfid').action = '/peserta-rat/' + pid + '/rfid';
    document.getElementById('input-rfid').value = '';
    bukaModal('modal-rfid');
    setTimeout(function () { document.getElementById('input-rfid').focus(); }, 100);
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') MODAL_IDS.forEach(tutupModal); });
// Modal dipindah ke <body> supaya position:fixed tidak terganggu backdrop-filter parent
document.addEventListener('DOMContentLoaded', function () {
    MODAL_IDS.forEach(function (id) { var el = document.getElementById(id); if (el) document.body.appendChild(el); });
});
// Isi otomatis dari data induk saat NIP diketik
(function () {
    var ni = document.querySelector('#modal-tambah input[name="nip"]');
    if (!ni) return;
    var t = null;
    ni.addEventListener('input', function () {
        clearTimeout(t);
        var nip = this.value.trim();
        if (nip.length < 4) return;
        t = setTimeout(function () {
            fetch('/peserta-rat/lookup-nip?nip=' + encodeURIComponent(nip))
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d.found) return;
                    var n = document.querySelector('#modal-tambah input[name="nama"]');
                    if (n && !n.value) n.value = d.nama;
                    ['departemen', 'section', 'shift'].forEach(function (f) {
                        var el = document.querySelector('#modal-tambah [name="' + f + '"]');
                        if (!el || !d[f]) return;
                        if (el.tagName === 'SELECT') {
                            for (var i = 0; i < el.options.length; i++) { if (el.options[i].value === d[f]) { el.selectedIndex = i; break; } }
                        } else if (!el.value) { el.value = d[f]; }
                    });
                }).catch(function () {});
        }, 300);
    });
})();
</script>
<?= $this->endSection() ?>
