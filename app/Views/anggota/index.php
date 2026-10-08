<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-6xl mx-auto px-5 py-8">

    <div class="page-header">
        <div>
            <h1 class="page-title">Data Anggota</h1>
            <p class="page-subtitle"><?= esc($totalAktif) ?> anggota aktif terdaftar</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah')">
                <i class="ti ti-user-plus"></i> Tambah Anggota
            </button>
            <button class="btn btn-ghost btn-sm" onclick="openModal('modal-import')">
                <i class="ti ti-upload"></i> Import Excel
            </button>
            <a href="/anggota/bulk-rfid" class="btn btn-teal btn-sm">
                <i class="ti ti-nfc"></i> RFID Massal
            </a>
            <a href="/anggota/export" class="btn btn-amber btn-sm">
                <i class="ti ti-download"></i> Export Excel
            </a>
            <button class="btn btn-danger btn-sm" onclick="openModal('modal-hapus-semua')">
                <i class="ti ti-trash"></i> Hapus Semua
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('gagalImport')): ?>
        <div class="card p-4 mb-5" style="border-left:4px solid #C9920F;background:#fffbeb;">
            <div style="font-size:13px;font-weight:600;color:#92650A;margin-bottom:8px;"><i class="ti ti-alert-triangle"></i> Beberapa baris gagal diimpor:</div>
            <ul style="margin:0;padding-left:16px;color:#78400A;font-size:12px;">
                <?php foreach (session()->getFlashdata('gagalImport') as $g): ?>
                    <li><?= esc($g) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Search + filter -->
    <div class="card p-4 mb-5">
        <form action="/anggota" method="get">
            <div class="filter-row">
                <input type="text" name="cari" value="<?= esc($keyword ?? '') ?>" placeholder="Cari NIP/nama..." class="input-field" style="flex:2;min-width:0;height:36px;font-size:12px;">
                <select name="departemen" class="input-field" style="flex:1.5;min-width:0;height:36px;font-size:12px;"><option value="">Semua Dept</option><?php foreach ($daftarDept as $d): ?><option value="<?= esc($d) ?>" <?= ($filterDept??'')===$d?'selected':'' ?>><?= esc($d) ?></option><?php endforeach; ?></select>
                <select name="section" class="input-field" style="flex:1.5;min-width:0;height:36px;font-size:12px;"><option value="">Semua Section</option><?php foreach ($daftarSect as $s): ?><option value="<?= esc($s) ?>" <?= ($filterSect??'')===$s?'selected':'' ?>><?= esc($s) ?></option><?php endforeach; ?></select>
                <select name="shift" class="input-field" style="flex:0.8;min-width:0;height:36px;font-size:12px;"><option value="">Shift</option><option value="A" <?= ($filterShift??'')==='A'?'selected':'' ?>>A</option><option value="B" <?= ($filterShift??'')==='B'?'selected':'' ?>>B</option><option value="N" <?= ($filterShift??'')==='N'?'selected':'' ?>>N</option></select>
                <select name="rfid" class="input-field" style="flex:1;min-width:0;height:36px;font-size:12px;"><option value="">RFID</option><option value="ada" <?= ($filterRfid??'')==='ada'?'selected':'' ?>>Sudah</option><option value="belum" <?= ($filterRfid??'')==='belum'?'selected':'' ?>>Belum</option></select>
                <select name="status" class="input-field" style="flex:1;min-width:0;height:36px;font-size:12px;"><option value="">Status</option><option value="aktif" <?= ($filterStat??'')==='aktif'?'selected':'' ?>>Aktif</option><option value="nonaktif" <?= ($filterStat??'')==='nonaktif'?'selected':'' ?>>Non</option></select>
                <button type="submit" class="btn btn-primary" style="height:36px;padding:0 14px;flex-shrink:0;"><i class="ti ti-filter"></i> Filter</button>
                <?php if ($keyword || $filterDept || $filterSect || $filterShift || $filterStat || ($filterRfid??'')): ?><a href="/anggota" class="btn btn-ghost" style="height:36px;padding:0 10px;flex-shrink:0;"><i class="ti ti-x"></i> Reset</a><?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabel -->
    <div class="card">
        <?php if (empty($daftarAnggota)): ?>
            <div style="padding:48px;text-align:center;color:#6b7280;">
                <i class="ti ti-user-off" style="font-size:40px;color:#e5e7eb;display:block;margin-bottom:12px;"></i>
                <?= $keyword ? 'Tidak ada anggota yang cocok.' : 'Belum ada data anggota.' ?>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:45px;">No</th>
                    <th>NIP</th>
                    <th>Nama</th>
                    <th>Departemen</th>
                    <th>Section</th>
                    <th>Shift</th>
                    <th>RFID</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $perPage=20;$curPgA=(int)($_GET['page']??1); foreach ($daftarAnggota as $idxA => $a): ?>
                <tr>
                    <td style="color:#9ca3af;font-size:12px;"><?= ($curPgA-1)*$perPage+$idxA+1 ?></td>
                    <td style="font-family:var(--font-mono);font-weight:700;color:#022760;"><?= esc($a['nip']) ?></td>
                    <td style="font-weight:500;"><?= esc($a['nama']) ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?= esc($a['departemen'] ?? '-') ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?= esc($a['section'] ?? '-') ?></td>
                    <td style="text-align:center;">
                        <?php if ($a['shift']): ?>
                            <span class="badge badge-info"><?= esc($a['shift']) ?></span>
                        <?php else: ?>
                            <span style="color:#e5e7eb;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($a['uid_rfid']): ?>
                            <span class="badge badge-success" style="font-family:var(--font-mono);font-size:11px;">
                                <i class="ti ti-wifi" style="font-size:10px;"></i>
                                <?= esc($a['uid_rfid']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge badge-warning">
                                <i class="ti ti-wifi-off" style="font-size:10px;"></i>
                                Belum
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $a['status']==='aktif' ? 'badge-active' : 'badge-locked' ?>">
                            <?= $a['status']==='aktif' ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;justify-content:flex-end;">
                            <!-- Edit -->
                            <button class="btn btn-edit btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($a)) ?>)" title="Edit">
                                <i class="ti ti-edit"></i>
                            </button>
                            <!-- Daftarkan/Ganti RFID -->
                            <button class="btn btn-primary btn-sm" onclick="openRfidModal(<?= esc($a['id']) ?>, '<?= esc($a['nama']) ?>', '<?= esc($a['uid_rfid'] ?? '') ?>')" title="<?= $a['uid_rfid'] ? 'Ganti Kartu RFID' : 'Daftarkan Kartu RFID' ?>">
                                <i class="ti ti-wifi"></i>
                            </button>
                            <!-- Nonaktifkan / Aktifkan -->
                            <?php if ($a['status']==='aktif'): ?>
                            <button class="btn btn-indigo btn-sm" title="Nonaktifkan" onclick="showModal({
                                title: 'Nonaktifkan <?= esc(addslashes($a['nama'])) ?>?',
                                message: 'Anggota tidak bisa absen di RAT berikutnya. Data histori absensi tetap tersimpan.',
                                confirmText: 'Nonaktifkan',
                                type: 'warning',
                                onConfirm: () => document.getElementById('form-nonaktif-<?= $a['id'] ?>').submit()
                            })">
                                <i class="ti ti-user-off"></i>
                            </button>
                            <form id="form-nonaktif-<?= $a['id'] ?>" action="/anggota/<?= $a['id'] ?>/nonaktifkan" method="post" style="display:none;"><?= csrf_field() ?></form>
                            <?php else: ?>
                            <button class="btn btn-teal btn-sm" title="Aktifkan Kembali" onclick="document.getElementById('form-aktif-<?= $a['id'] ?>').submit()">
                                <i class="ti ti-user-check"></i>
                            </button>
                            <form id="form-aktif-<?= $a['id'] ?>" action="/anggota/<?= $a['id'] ?>/aktifkan" method="post" style="display:none;"><?= csrf_field() ?></form>
                            <?php endif; ?>
                            <!-- Hapus — histori absensi tetap ada via snapshot -->
                            <button class="btn btn-danger btn-sm" title="Hapus" onclick="showModal({
                                title: 'Hapus <?= esc(addslashes($a['nama'])) ?>?',
                                message: 'Data anggota dihapus. Histori absensi yang sudah tercatat tetap ada dan ditampilkan sebagai nama asli + keterangan (dihapus).',
                                confirmText: 'Hapus',
                                type: 'danger',
                                onConfirm: () => document.getElementById('form-hapus-<?= $a['id'] ?>').submit()
                            })">
                                <i class="ti ti-trash"></i>
                            </button>
                            <form id="form-hapus-<?= $a['id'] ?>" action="/anggota/<?= $a['id'] ?>/hapus" method="post" style="display:none;"><?= csrf_field() ?></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($pager && $pager->getPageCount() > 1): ?>
            <div style="padding:12px 20px;border-top:1px solid #e5e7eb;">
                <?= $pager->links('default', 'kpni_pager') ?>
            </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Tambah Anggota -->
<div id="modal-tambah" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeAllModals()">
    <div class="modal-box" style="max-width:520px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:16px;font-weight:700;color:#022760;margin:0;">Tambah Anggota Baru</h3>
            <button onclick="closeAllModals()" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:20px;">×</button>
        </div>
        <form action="/anggota" method="post" onsubmit="return validasiNip(this)">
            <?= csrf_field() ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div>
                    <label class="form-label">NIP (6 digit)</label>
                    <input type="text" name="nip" id="input-nip-tambah" maxlength="6" placeholder="217621" class="input-field" required
                        oninput="this.value=this.value.replace(/\D/g,'');cekNipInput(this)">
                    <p id="nip-error" style="font-size:11px;color:#b3262b;margin:4px 0 0;display:none;">
                        <i class="ti ti-alert-circle" style="font-size:11px;"></i> NIP harus tepat 6 digit angka
                    </p>
                </div>
                <div>
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="input-field" required>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 80px;gap:12px;margin-bottom:12px;">
                <div>
                    <label class="form-label">Departemen</label>
                    <select name="departemen" class="input-field">
                        <option value="">— Pilih —</option>
                        <?php foreach(['ACCOUNTING','HUMAN RESOURCE','MAINTENANCE','MANUFACTURING ENGINEERING','OFFICE','OPERATION CONTROL','OPERATION EFFICIENCY','PARTS PRODUCTION','PROCESS ENGINEERING','PRODUCTION','PURCHASING','QUALITY ASSURANCE','QUALITY ENGINEERING','SYSTEM'] as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Section</label>
                    <select name="section" class="input-field">
                        <option value="">— Pilih —</option>
                        <?php foreach(['ACCOUNTING','BONDING','BUSINESS CONTROL','DMP, BUSH, HCG, PRODUCTION','FACILITY MAINTENANCE','GENBA KAIZEN','GENERAL AFFAIRS','INFRASTRUCTURE','MACHINE ENGINEERING','MACHINE MAINTENANCE','MATERIAL ENGINEERING','MOLD CONTROL','OR, GKT PRODUCTION','OSP-1 (110T HP)','OSP-2 (60T HP AND TENSIONER)','OSP-3 (INJECTION, SEMI TRANSFER)','PROCESS ENGINEERING','PRODUCTION PLANING AND CONTROL','PURCHASING','QUALITY ASSURANCE','QUALITY ENGINEERING','QUALITY INSPECTION','RUBBER','SHE IS BCM OFFICE','SPRING','STAMPING','TECHNOLOGY CONTROL','TOOLING','TOTAL COST DOWN'] as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Shift</label>
                    <select name="shift" class="input-field">
                        <option value="">-</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="N">N</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label class="form-label">Kartu RFID (opsional)</label>
                <input type="text" name="uid_rfid" id="uid-tambah"
                    placeholder="Tap kartu RFID, atau kosongkan jika belum ada..."
                    class="input-field" autocomplete="off">
                <p style="font-size:11px;color:#6b7280;margin:4px 0 0;">Bisa didaftarkan nanti lewat tombol <i class="ti ti-wifi" style="font-size:11px;"></i> di tabel.</p>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeAllModals()" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-user-plus"></i> Tambah Anggota
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Excel -->
<div id="modal-import" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeAllModals()">
    <div class="modal-box" style="max-width:480px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:16px;font-weight:700;color:#022760;margin:0;">Import Anggota dari Excel</h3>
            <button onclick="closeAllModals()" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:20px;">×</button>
        </div>
        <div style="background:#E8F4FD;border-radius:8px;padding:12px 14px;margin-bottom:16px;font-size:12px;color:#064687;border-left:3px solid #009EE0;">
            <strong>Format kolom Excel:</strong> A = No, B = NIP (PR Number), C = Nama, D = RFID (opsional)<br>
            File hanya berisi anggota baru bulan ini — sistem selalu menambah, tidak menimpa.
        </div>
        <form action="/anggota/import" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div style="margin-bottom:16px;">
                <label class="form-label">Pilih File Excel</label>
                <input type="file" name="file_excel" accept=".xlsx,.xls"
                    style="width:100%;border:2px dashed #0960A8;border-radius:8px;padding:12px;font-size:13px;cursor:pointer;background:#F0F7FF;" required>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeAllModals()" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-upload"></i> Upload & Import
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Anggota -->
<div id="modal-edit" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeAllModals()">
    <div class="modal-box" style="max-width:520px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:16px;font-weight:700;color:#022760;margin:0;">Edit Anggota</h3>
            <button onclick="closeAllModals()" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:20px;">×</button>
        </div>
        <form id="form-edit" action="" method="post">
            <?= csrf_field() ?>
            <div style="margin-bottom:12px;">
                <label class="form-label">NIP</label>
                <input type="text" id="edit-pr" class="input-field" disabled style="background:#f8fafc;color:#6b7280;">
                <p style="font-size:11px;color:#6b7280;margin:4px 0 0;">NIP tidak bisa diubah karena menjadi acuan histori absensi.</p>
            </div>
            <div style="margin-bottom:12px;">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" id="edit-nama" class="input-field" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 80px;gap:10px;margin-bottom:16px;">
                <div>
                    <label class="form-label">Departemen</label>
                    <select name="departemen" id="edit-departemen" class="input-field">
                        <option value="">— Pilih —</option>
                        <?php foreach(['ACCOUNTING','HUMAN RESOURCE','MAINTENANCE','MANUFACTURING ENGINEERING','OFFICE','OPERATION CONTROL','OPERATION EFFICIENCY','PARTS PRODUCTION','PROCESS ENGINEERING','PRODUCTION','PURCHASING','QUALITY ASSURANCE','QUALITY ENGINEERING','SYSTEM'] as $d): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Section</label>
                    <select name="section" id="edit-section" class="input-field">
                        <option value="">— Pilih —</option>
                        <?php foreach(['ACCOUNTING','BONDING','BUSINESS CONTROL','DMP, BUSH, HCG, PRODUCTION','FACILITY MAINTENANCE','GENBA KAIZEN','GENERAL AFFAIRS','INFRASTRUCTURE','MACHINE ENGINEERING','MACHINE MAINTENANCE','MATERIAL ENGINEERING','MOLD CONTROL','OR, GKT PRODUCTION','OSP-1 (110T HP)','OSP-2 (60T HP AND TENSIONER)','OSP-3 (INJECTION, SEMI TRANSFER)','PROCESS ENGINEERING','PRODUCTION PLANING AND CONTROL','PURCHASING','QUALITY ASSURANCE','QUALITY ENGINEERING','QUALITY INSPECTION','RUBBER','SHE IS BCM OFFICE','SPRING','STAMPING','TECHNOLOGY CONTROL','TOOLING','TOTAL COST DOWN'] as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Shift</label>
                    <select name="shift" id="edit-shift" class="input-field">
                        <option value="">-</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="N">N</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeAllModals()" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-edit">
                    <i class="ti ti-device-floppy"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Daftarkan RFID -->
<div id="modal-rfid" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeAllModals()">
    <div class="modal-box" style="max-width:440px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <h3 style="font-size:16px;font-weight:700;color:#022760;margin:0;" id="rfid-modal-title">Daftarkan Kartu RFID</h3>
            <button onclick="closeAllModals()" style="background:none;border:none;cursor:pointer;color:#6b7280;font-size:20px;">×</button>
        </div>
        <div style="background:#E8F4FD;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:#064687;">
            <i class="ti ti-info-circle"></i>
            Anggota: <strong id="rfid-nama"></strong>
        </div>
        <form id="form-rfid" action="" method="post">
            <?= csrf_field() ?>
            <div style="margin-bottom:16px;">
                <label class="form-label">Tap kartu RFID</label>
                <input type="text" name="uid_rfid" id="input-rfid-modal"
                    class="input-field" placeholder="Tap kartu di sini..." autocomplete="off" autofocus
                    style="font-size:16px;text-align:center;letter-spacing:2px;">
                <p id="uid-preview" style="font-size:12px;color:#0960A8;margin:6px 0 0;min-height:18px;"></p>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeAllModals()" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-wifi"></i> Simpan Kartu
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el && el.parentElement !== document.body) {
        document.body.appendChild(el);
    }
    if (el) el.style.display = 'flex';
    if (id === 'modal-tambah') setTimeout(() => document.getElementById('uid-tambah')?.focus(), 100);
    if (id === 'modal-rfid')   setTimeout(() => document.getElementById('input-rfid-modal')?.focus(), 100);
}

function closeAllModals() {
    ['modal-tambah','modal-import','modal-edit','modal-rfid','modal-hapus-semua'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

// ESC untuk tutup modal
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllModals(); });

function cekNipInput(el) {
    const err = document.getElementById('nip-error');
    if (!err) return;
    const valid = el.value.length === 6 && /^\d{6}$/.test(el.value);
    err.style.display = el.value.length > 0 && !valid ? 'block' : 'none';
    el.style.borderColor = el.value.length > 0 && !valid ? '#b3262b' : '';
}

function validasiNip(form) {
    const nip = form.querySelector('[name="nip"]');
    if (!nip) return true;
    if (nip.value.length !== 6 || !/^\d{6}$/.test(nip.value)) {
        nip.focus();
        nip.style.borderColor = '#b3262b';
        const err = document.getElementById('nip-error');
        if (err) err.style.display = 'block';
        showToast('NIP harus tepat 6 digit angka!', 'error');
        return false;
    }
    return true;
}

function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.className = 'toast toast-' + (type==='error' ? 'error' : 'success');
    t.innerHTML = `<i class="ti ti-${type==='error'?'alert-circle':'circle-check'}" style="font-size:18px;flex-shrink:0;"></i><span>${msg}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity 0.4s'; setTimeout(()=>t.remove(),400); }, 4000);
}

function openEditModal(anggota) {
    document.getElementById('edit-pr').value   = anggota.nip;
    document.getElementById('edit-nama').value = anggota.nama;
    // Isi dropdown departemen, section, shift
    const dept  = document.getElementById('edit-departemen');
    const sect  = document.getElementById('edit-section');
    const shift = document.getElementById('edit-shift');
    if (dept)  dept.value  = anggota.departemen || '';
    if (sect)  sect.value  = anggota.section    || '';
    if (shift) shift.value = anggota.shift      || '';
    document.getElementById('form-edit').action = '/anggota/' + anggota.id;
    openModal('modal-edit');
}

function openRfidModal(id, nama, uidLama) {
    document.getElementById('rfid-nama').textContent = nama;
    document.getElementById('rfid-modal-title').textContent = uidLama ? 'Ganti Kartu RFID' : 'Daftarkan Kartu RFID';
    document.getElementById('input-rfid-modal').value = '';
    document.getElementById('uid-preview').textContent = uidLama ? 'Kartu sekarang: ' + uidLama : '';
    document.getElementById('form-rfid').action = '/anggota/' + id + '/rfid';
    openModal('modal-rfid');
}

// Preview UID saat user tap kartu di modal RFID
const rfidInput = document.getElementById('input-rfid-modal');
if (rfidInput) {
    rfidInput.addEventListener('input', function() {
        const preview = document.getElementById('uid-preview');
        if (this.value) {
            preview.textContent = 'UID terdeteksi: ' + this.value;
            preview.style.color = '#16a34a';
        }
    });
}
</script>

<!-- Modal Hapus Semua Anggota -->
<div id="modal-hapus-semua" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeAllModals()">
    <div class="modal-box" style="max-width:500px;">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
            <div style="width:48px;height:48px;border-radius:12px;background:#fee2e2;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">⚠️</div>
            <div>
                <h3 style="font-size:16px;font-weight:700;color:#6B1F3A;margin:0 0 8px;">Hapus Semua Data Anggota</h3>
                <p style="font-size:13px;color:#6b7280;margin:0;line-height:1.6;">
                    Tindakan ini akan menghapus <strong>seluruh <?= esc($totalAktif + $totalNonaktif) ?> data anggota</strong> secara permanen.<br>
                    Histori absensi tetap tersimpan via snapshot nama.<br><br>
                    <strong style="color:#b3262b;">Sangat disarankan untuk export data anggota terlebih dahulu sebelum melanjutkan.</strong>
                </p>
            </div>
        </div>

        <!-- Wajib export dulu -->
        <div style="background:#fffbeb;border:1.5px solid #C9920F;border-radius:10px;padding:14px;margin-bottom:16px;">
            <div style="font-size:13px;font-weight:600;color:#92650A;margin-bottom:10px;">
                <i class="ti ti-alert-triangle"></i> Backup data sebelum hapus:
            </div>
            <a href="/anggota/export" target="_blank" class="btn btn-amber btn-sm" style="width:100%;justify-content:center;">
                <i class="ti ti-download"></i> Download Backup Data Anggota (.xlsx)
            </a>
        </div>

        <!-- Konfirmasi ketik -->
        <form action="/anggota/hapus-semua" method="post">
            <?= csrf_field() ?>
            <div style="margin-bottom:16px;">
                <label style="font-size:13px;font-weight:600;color:#6B1F3A;display:block;margin-bottom:6px;">
                    Ketik <code style="background:#fee2e2;padding:2px 6px;border-radius:4px;font-size:12px;">HAPUS SEMUA DATA ANGGOTA</code> untuk konfirmasi:
                </label>
                <input type="text" name="konfirmasi" id="input-konfirmasi"
                    class="input-field" placeholder="Ketik teks konfirmasi di sini..."
                    autocomplete="off" oninput="cekKonfirmasi()">
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeAllModals()" class="btn btn-ghost">Batal</button>
                <button type="submit" id="btn-hapus-semua" class="btn btn-danger" disabled style="opacity:0.4;cursor:not-allowed;">
                    <i class="ti ti-trash"></i> Hapus Semua Data Anggota
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function cekKonfirmasi() {
    const val = document.getElementById('input-konfirmasi').value;
    const btn = document.getElementById('btn-hapus-semua');
    const match = val === 'HAPUS SEMUA DATA ANGGOTA';
    btn.disabled = !match;
    btn.style.opacity = match ? '1' : '0.4';
    btn.style.cursor  = match ? 'pointer' : 'not-allowed';
}
</script>

<?= $this->endSection() ?>
