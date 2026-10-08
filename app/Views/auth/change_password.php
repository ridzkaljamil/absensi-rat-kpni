<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-md mx-auto mt-10 px-4">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn-ghost px-4 py-2 text-sm flex items-center gap-2">
            ← Kembali ke Dashboard
        </a>
    </div>

    <div class="card p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl gradient-blue flex items-center justify-center text-white text-lg">🔑</div>
            <div>
                <h1 class="text-lg font-bold text-ink">Ganti Password</h1>
                <p class="text-sm text-steel">Pastikan password baru minimal 6 karakter</p>
            </div>
        </div>

        <?php if (session()->getFlashdata('errors')): ?>
            <div class="bg-red-50 border border-red-200 border-l-4 border-l-danger rounded-xl px-4 py-3 text-sm text-danger mb-4">
                <ul class="list-disc list-inside space-y-1">
                    <?php foreach (session()->getFlashdata('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="/ganti-password" method="post" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-sm font-semibold text-kpni-navy mb-2">Password Lama</label>
                <div class="relative">
                    <input type="password" name="old_password" id="pwd-old"
                        class="input-field pr-12" placeholder="Masukkan password lama" required>
                    <button type="button" class="eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-steel hover:text-kpni-blue transition"
                        onmousedown="showPwd('pwd-old',true)" onmouseup="showPwd('pwd-old',false)" onmouseleave="showPwd('pwd-old',false)">
                        👁️
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-kpni-navy mb-2">Password Baru</label>
                <div class="relative">
                    <input type="password" name="new_password" id="pwd-new"
                        class="input-field pr-12" placeholder="Minimal 6 karakter" minlength="6" required>
                    <button type="button" class="eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-steel hover:text-kpni-blue transition"
                        onmousedown="showPwd('pwd-new',true)" onmouseup="showPwd('pwd-new',false)" onmouseleave="showPwd('pwd-new',false)">
                        👁️
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-kpni-navy mb-2">Konfirmasi Password Baru</label>
                <div class="relative">
                    <input type="password" name="confirm_password" id="pwd-confirm"
                        class="input-field pr-12" placeholder="Ulangi password baru" minlength="6" required>
                    <button type="button" class="eye-btn absolute right-3 top-1/2 -translate-y-1/2 text-steel hover:text-kpni-blue transition"
                        onmousedown="showPwd('pwd-confirm',true)" onmouseup="showPwd('pwd-confirm',false)" onmouseleave="showPwd('pwd-confirm',false)">
                        👁️
                    </button>
                </div>
                <p class="text-xs text-steel mt-1">Tahan 👁️ untuk melihat password</p>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary w-full h-11 text-sm">
                    Simpan Password Baru
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.eye-btn { background: none; border: none; cursor: pointer; font-size: 18px; padding: 4px; line-height: 1; }
</style>
<script>
function showPwd(id, show) {
    document.getElementById(id).type = show ? 'text' : 'password';
}
</script>

<?= $this->endSection() ?>
