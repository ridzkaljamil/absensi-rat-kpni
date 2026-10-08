<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-6xl mx-auto px-5 py-8">

    <div class="page-header">
        <div>
            <h1 class="page-title">Kelola User</h1>
            <p class="page-subtitle">Manajemen akun yang dapat mengakses sistem ini</p>
        </div>
        <div class="page-actions">
            <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah')">
                <i class="ti ti-user-plus"></i> Tambah Akun
            </button>
        </div>
    </div>

    <div class="card p-4 mb-5" style="background:#E8F4FD;border:none;display:flex;gap:12px;align-items:flex-start;">
        <i class="ti ti-info-circle" style="font-size:18px;color:#0960A8;flex-shrink:0;margin-top:1px;"></i>
        <div style="font-size:13px;color:#064687;">
            <strong>Admin</strong> — akses penuh ke semua fitur.<br>
            <strong>User</strong> — operator absensi: bisa membuka Dashboard dan halaman Absensi, tanpa akses pengaturan.
        </div>
    </div>

    <div class="card">
        <table class="data-table">
            <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th style="text-align:right;">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($daftarUser as $u): ?>
                <tr>
                    <td style="font-weight:600;color:#022760;"><?= esc($u['nama_lengkap']) ?></td>
                    <td style="font-family:var(--font-mono);font-size:13px;color:#6b7280;"><?= esc($u['username']) ?>
                        <?php if ($u['id'] == session()->get('user_id')): ?>
                            <span class="badge badge-active" style="margin-left:6px;font-size:10px;">Anda</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $u['role']==='admin' ? 'badge-active' : 'badge-info' ?>"><i class="ti <?= $u['role']==='admin' ? 'ti-shield' : 'ti-user' ?>" style="font-size:11px;"></i> <?= ucfirst($u['role']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;">
                            <?php if ($u['id'] != session()->get('user_id')): ?>
                            <button class="btn btn-edit btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($u)) ?>)"><i class="ti ti-edit"></i> Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="showModal({title:'Hapus Akun <?= esc(addslashes($u['username'])) ?>?',message:'Akun ini akan dihapus permanen.',confirmText:'Hapus Akun',type:'danger',onConfirm:()=>document.getElementById('form-hapus-<?= $u['id'] ?>').submit()})"><i class="ti ti-trash"></i></button>
                            <form id="form-hapus-<?= $u['id'] ?>" action="/users/<?= $u['id'] ?>/hapus" method="post" style="display:none;"><?= csrf_field() ?></form>
                            <?php else: ?>
                            <a href="/ganti-password" class="btn btn-ghost btn-sm"><i class="ti ti-key"></i> Ganti Password</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Akun -->
<div id="modal-tambah" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeAllModals()">
    <div class="modal-box" style="max-width:460px;">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
            <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#E8F4FD;"><i class="ti ti-user-plus" style="color:#0960A8;font-size:20px;"></i></div>
            <div style="flex:1;"><h3 style="font-size:16px;font-weight:700;color:#022760;margin:0 0 6px;">Tambah Akun Baru</h3><p style="font-size:13px;color:#6b7280;margin:0;">Buat akun Admin atau User baru.</p></div>
        </div>
        <form action="/users" method="post">
            <?= csrf_field() ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div><label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" class="input-field" required></div>
                <div><label class="form-label">Username</label><input type="text" name="username" class="input-field" required></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div><label class="form-label">Role</label><select name="role" class="input-field" required><option value="user">User (operator absensi)</option><option value="admin">Admin (akses penuh)</option></select></div>
                <div><label class="form-label">Password</label><div style="position:relative;"><input type="password" name="password" id="pwd-baru" class="input-field" style="padding-right:40px;" minlength="6" required><button type="button" class="eye-btn" onmousedown="showPwd('pwd-baru',true)" onmouseup="showPwd('pwd-baru',false)" onmouseleave="showPwd('pwd-baru',false)">👁️</button></div></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeAllModals()" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="ti ti-user-plus"></i> Buat Akun</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Akun -->
<div id="modal-edit" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeAllModals()">
    <div class="modal-box" style="max-width:460px;">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
            <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#E8F4FD;"><i class="ti ti-edit" style="color:#0960A8;font-size:20px;"></i></div>
            <div style="flex:1;"><h3 style="font-size:16px;font-weight:700;color:#022760;margin:0 0 6px;">Edit Akun</h3><p style="font-size:13px;color:#6b7280;margin:0;">Ubah data akun pengguna.</p></div>
        </div>
        <form id="form-edit-user" action="" method="post">
            <?= csrf_field() ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div><label class="form-label">Nama Lengkap</label><input type="text" name="nama_lengkap" id="edit-nama" class="input-field" required></div>
                <div><label class="form-label">Username</label><input type="text" name="username" id="edit-username" class="input-field" required></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
                <div><label class="form-label">Role</label><select name="role" id="edit-role" class="input-field"><option value="user">User (operator absensi)</option><option value="admin">Admin (akses penuh)</option></select></div>
                <div><label class="form-label">Password Baru <span style="font-size:11px;color:#9ca3af;">(kosongkan jika tidak diganti)</span></label><div style="position:relative;"><input type="password" name="password" id="pwd-edit" class="input-field" style="padding-right:40px;" minlength="6" placeholder="Opsional"><button type="button" class="eye-btn" onmousedown="showPwd('pwd-edit',true)" onmouseup="showPwd('pwd-edit',false)" onmouseleave="showPwd('pwd-edit',false)">👁️</button></div></div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="closeAllModals()" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-edit"><i class="ti ti-device-floppy"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeAllModals() { ['modal-tambah','modal-edit'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; }); }
function openEditModal(u) {
    document.getElementById('edit-nama').value = u.nama_lengkap;
    document.getElementById('edit-username').value = u.username;
    document.getElementById('edit-role').value = u.role;
    document.getElementById('pwd-edit').value = '';
    document.getElementById('form-edit-user').action = '/users/' + u.id;
    openModal('modal-edit');
}
document.addEventListener('keydown', e => { if (e.key==='Escape') closeAllModals(); });
</script>

<?= $this->endSection() ?>
