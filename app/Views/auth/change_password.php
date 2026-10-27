<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div style="max-width:460px;margin:0 auto;padding:28px 16px;">
    <a href="<?= session()->get('role') === 'admin' ? '/dashboard' : '/absensi' ?>" class="btn btn-ghost btn-sm" style="margin-bottom:16px;"><i class="ti ti-arrow-left"></i> Kembali</a>

    <div class="card" style="padding:26px;">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <span style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,#0960A8,#009EE0);display:flex;align-items:center;justify-content:center;color:white;font-size:20px;flex:0 0 auto;"><i class="ti ti-key"></i></span>
            <div>
                <h1 style="font-size:18px;font-weight:700;color:#022760;margin:0;">Ganti Password</h1>
                <p style="font-size:13px;color:#6b7280;margin:2px 0 0;">Akun: <?= esc(session()->get('username')) ?></p>
            </div>
        </div>

        <?php if ($errors = session()->getFlashdata('errors')): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #A8295A;border-radius:10px;padding:10px 14px;font-size:13px;color:#991b1b;margin-bottom:16px;">
            <?php foreach ($errors as $error): ?><div><?= esc($error) ?></div><?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form action="/ganti-password" method="post">
            <?= csrf_field() ?>
            <?php foreach ([
                ['old_password', 'pwd-old', 'Password lama', 'Masukkan password lama', 'current-password'],
                ['new_password', 'pwd-new', 'Password baru', 'Minimal 6 karakter', 'new-password'],
                ['confirm_password', 'pwd-confirm', 'Ulangi password baru', 'Ketik ulang password baru', 'new-password'],
            ] as [$name, $id, $label, $ph, $ac]): ?>
            <div style="margin-bottom:14px;">
                <label class="form-label" for="<?= $id ?>"><?= $label ?></label>
                <div style="position:relative;">
                    <input type="password" name="<?= $name ?>" id="<?= $id ?>" class="input-field" style="padding-right:44px;" placeholder="<?= $ph ?>" autocomplete="<?= $ac ?>" <?= $name !== 'old_password' ? 'minlength="6"' : '' ?> required>
                    <button type="button" class="lihat-pwd" data-target="<?= $id ?>" aria-label="Tampilkan password"
                        style="position:absolute;right:6px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:none;background:none;cursor:pointer;color:#6b7280;font-size:18px;display:flex;align-items:center;justify-content:center;">
                        <i class="ti ti-eye"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;height:44px;margin-top:6px;"><i class="ti ti-device-floppy"></i> Simpan Password Baru</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
