<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-6xl mx-auto px-5 py-8">
    <div class="page-header">
        <div>
            <h1 class="page-title">Kelola Sesi</h1>
            <p class="page-subtitle"><?= esc($rat['nama_rat']) ?> · jam memakai format 24 jam (WIB)</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn btn-primary btn-sm" onclick="bukaTambahSesi()"><i class="ti ti-plus"></i> Tambah Sesi</button>
            <a href="/rat" class="btn btn-ghost btn-sm"><i class="ti ti-refresh"></i> Ganti RAT</a>
        </div>
    </div>

    <?php if (empty($daftarSesi)): ?>
        <div class="card p-10" style="text-align:center;">
            <i class="ti ti-clock-off" style="font-size:48px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
            <p style="color:#6b7280;font-size:14px;margin-bottom:16px;">Belum ada sesi untuk RAT ini.</p>
            <button type="button" class="btn btn-primary" onclick="bukaTambahSesi()"><i class="ti ti-plus"></i> Tambah Sesi Pertama</button>
        </div>
    <?php else: ?>
        <div class="grid-resp grid-resp-3">
            <?php foreach ($daftarSesi as $sesi):
                $id      = (int) $sesi['id'];
                $label   = label_sesi($sesi['nama_sesi']);
                $kunci   = $sesi['status'] === 'terkunci';
                $nAbsen  = (int) ($jmlAbsensi[$id] ?? 0);
                $nPes    = (int) ($jmlPeserta[$id] ?? 0);
            ?>
                <div class="card p-5" style="display:flex;flex-direction:column;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;">
                        <div style="font-size:18px;font-weight:800;color:#022760;line-height:1.25;"><?= esc($label) ?></div>
                        <span class="badge <?= $kunci ? 'badge-locked' : 'badge-active' ?>" style="flex-shrink:0;">
                            <i class="ti <?= $kunci ? 'ti-lock' : 'ti-circle-check' ?>" style="font-size:11px;"></i> <?= $kunci ? 'Terkunci' : 'Aktif' ?>
                        </span>
                    </div>
                    <div style="display:flex;gap:12px;font-size:12px;color:#6b7280;margin-bottom:14px;">
                        <span><i class="ti ti-users" style="font-size:12px;"></i> <?= $nPes ?> peserta</span>
                        <span><i class="ti ti-checkup-list" style="font-size:12px;"></i> <?= $nAbsen ?> hadir</span>
                    </div>

                    <form action="/sesi/<?= $id ?>" method="post" style="margin-bottom:12px;">
                        <?= csrf_field() ?>
                        <div style="margin-bottom:8px;">
                            <label class="form-label" style="font-size:11px;">Nama Sesi</label>
                            <input type="text" name="nama_sesi" value="<?= esc($sesi['nama_sesi']) ?>" class="input-field input-sm" required <?= $kunci ? 'disabled' : '' ?>>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
                            <div>
                                <label class="form-label" style="font-size:11px;">Mulai</label>
                                <input type="text" name="waktu_mulai" value="<?= esc(substr($sesi['waktu_mulai'], 0, 5)) ?>" class="input-field input-sm jam-24" <?= $kunci ? 'disabled' : '' ?>>
                            </div>
                            <div>
                                <label class="form-label" style="font-size:11px;">Selesai</label>
                                <input type="text" name="waktu_selesai" value="<?= esc(substr($sesi['waktu_selesai'], 0, 5)) ?>" class="input-field input-sm jam-24" <?= $kunci ? 'disabled' : '' ?>>
                            </div>
                        </div>
                        <?php if (! $kunci): ?>
                            <button type="submit" class="btn btn-edit btn-sm" style="width:100%;justify-content:center;"><i class="ti ti-device-floppy"></i> Simpan Perubahan</button>
                        <?php endif; ?>
                    </form>

                    <?php if ($kunci && ! empty($sesi['locked_at'])): ?>
                        <div style="background:#F0F7FF;border-radius:8px;padding:8px 12px;margin-bottom:10px;font-size:11px;color:#064687;border-left:3px solid #009EE0;">
                            <i class="ti ti-lock" style="font-size:11px;"></i> Dikunci <strong><?= esc(date('d/m/Y H:i', strtotime($sesi['locked_at']))) ?></strong> WIB
                        </div>
                    <?php endif; ?>

                    <div style="display:flex;gap:6px;margin-top:auto;">
                        <?php if (! $kunci): ?>
                            <button type="button" class="btn btn-indigo btn-sm" style="flex:1;justify-content:center;"
                                onclick="showModal({title:'Kunci <?= esc($label, 'js') ?>?',message:'Absensi tidak bisa dilakukan setelah dikunci.',confirmText:'Kunci Sesi',type:'warning',onConfirm:()=>document.getElementById('form-lock-<?= $id ?>').submit()})"><i class="ti ti-lock"></i> Kunci</button>
                            <form id="form-lock-<?= $id ?>" action="/sesi/<?= $id ?>/lock" method="post" style="display:none;"><?= csrf_field() ?></form>
                        <?php else: ?>
                            <button type="button" class="btn btn-teal btn-sm" style="flex:1;justify-content:center;"
                                onclick="showModal({title:'Buka kunci <?= esc($label, 'js') ?>?',message:'Absensi akan bisa dilakukan kembali.',confirmText:'Buka Kunci',type:'primary',onConfirm:()=>document.getElementById('form-unlock-<?= $id ?>').submit()})"><i class="ti ti-lock-open"></i> Buka</button>
                            <form id="form-unlock-<?= $id ?>" action="/sesi/<?= $id ?>/unlock" method="post" style="display:none;"><?= csrf_field() ?></form>
                        <?php endif; ?>
                        <?php if ($nAbsen === 0): ?>
                            <button type="button" class="btn btn-danger btn-sm" title="Hapus sesi"
                                onclick="showModal({title:'Hapus <?= esc($label, 'js') ?>?',message:'Daftar peserta sesi ini (<?= $nPes ?> orang) ikut terhapus.',confirmText:'Hapus Sesi',type:'danger',onConfirm:()=>document.getElementById('form-hapus-sesi-<?= $id ?>').submit()})"><i class="ti ti-trash"></i></button>
                            <form id="form-hapus-sesi-<?= $id ?>" action="/sesi/<?= $id ?>/hapus" method="post" style="display:none;"><?= csrf_field() ?></form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah Sesi -->
<div id="modal-tambah-sesi" style="display:none;" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box" style="max-width:400px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:16px;font-weight:700;color:#022760;margin:0;">Tambah Sesi Baru</h3>
            <button type="button" onclick="document.getElementById('modal-tambah-sesi').style.display='none'" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:20px;">×</button>
        </div>
        <form action="/sesi" method="post">
            <?= csrf_field() ?>
            <div style="margin-bottom:12px;">
                <label class="form-label">Nama Sesi</label>
                <input type="text" name="nama_sesi" class="input-field" placeholder="Contoh: Sesi <?= count($daftarSesi) + 1 ?>" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:6px;">
                <div><label class="form-label">Jam Mulai</label><input type="text" name="waktu_mulai" class="input-field jam-24" value="08:00"></div>
                <div><label class="form-label">Jam Selesai</label><input type="text" name="waktu_selesai" class="input-field jam-24" value="09:30"></div>
            </div>
            <p style="font-size:11px;color:#6b7280;margin:0 0 16px;">Format 24 jam, contoh 13:00 untuk jam 1 siang.</p>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-tambah-sesi').style.display='none'" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-plus"></i> Tambah Sesi</button>
            </div>
        </form>
    </div>
</div>

<style>
    .input-sm { height:36px; font-size:13px; }
    .jam-24 { font-variant-numeric:tabular-nums; letter-spacing:0.04em; }
</style>

<script>
function bukaTambahSesi() { document.getElementById('modal-tambah-sesi').style.display = 'flex'; }
document.addEventListener('DOMContentLoaded', function () {
    var m = document.getElementById('modal-tambah-sesi');
    if (m) document.body.appendChild(m);
});

// Input jam 24 jam (HH:MM) — tidak bergantung pada format AM/PM bawaan browser
document.querySelectorAll('.jam-24').forEach(function (el) {
    el.setAttribute('inputmode', 'numeric');
    el.setAttribute('maxlength', '5');
    el.setAttribute('placeholder', 'HH:MM');
    el.setAttribute('pattern', '([01][0-9]|2[0-3]):[0-5][0-9]');
    el.setAttribute('title', 'Format 24 jam, contoh 08:00 atau 13:30');
    el.setAttribute('autocomplete', 'off');
    if (!el.disabled) el.required = true;

    el.addEventListener('input', function () {
        var d = el.value.replace(/\D/g, '').slice(0, 4);
        el.value = d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d;
    });
    el.addEventListener('blur', function () {
        var d = el.value.replace(/\D/g, '');
        if (d.length === 1 || d.length === 2) d = ('0' + d).slice(-2) + '00';   // "8" → 08:00
        else if (d.length === 3) d = '0' + d;                                    // "830" → 08:30
        if (d.length === 4) el.value = d.slice(0, 2) + ':' + d.slice(2);
    });
});
</script>

<?= $this->endSection() ?>
