<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-6xl mx-auto px-5 py-8">
    <div class="page-header">
        <div>
            <h1 class="page-title">Log Aktivitas</h1>
            <p class="page-subtitle">Riwayat semua aksi penting yang dilakukan Admin</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <form action="/log" method="get" style="display:flex;gap:6px;align-items:center;">
                <input type="text" name="cari" value="<?= esc($keyword) ?>" placeholder="Cari aktivitas..."
                    class="input-field" style="height:30px;width:200px;font-size:12px;padding:0 10px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-search" style="font-size:14px;"></i></button>
                <?php if ($keyword): ?>
                    <a href="/log" class="btn btn-ghost btn-sm"><i class="ti ti-x" style="font-size:14px;"></i></a>
                <?php endif; ?>
            </form>
            <button onclick="showModal({
                title:'Bersihkan Log Aktivitas',
                message:'Semua log aktivitas akan dihapus permanen dan tidak bisa dikembalikan. Lanjutkan?',
                confirmText:'Ya, Bersihkan',
                type:'danger',
                onConfirm:()=>{ document.getElementById('form-bersihkan').submit(); }
            })" class="btn btn-danger btn-sm">
                <i class="ti ti-trash"></i> Bersihkan Log
            </button>
            <form id="form-bersihkan" action="/log/bersihkan" method="post" style="display:none;"><?= csrf_field() ?></form>
        </div>
    </div>

    <div class="card">
        <?php if (empty($daftarLog)): ?>
            <div style="padding:48px;text-align:center;color:#6b7280;">
                <i class="ti ti-file-off" style="font-size:40px;color:#e5e7eb;display:block;margin-bottom:12px;"></i>
                <?= $keyword ? 'Tidak ada log yang cocok dengan "' . esc($keyword) . '".' : 'Belum ada aktivitas yang tercatat.' ?>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead><tr><th style="width:160px;">Waktu</th><th style="width:180px;">Admin</th><th>Aktivitas</th></tr></thead>
                <tbody>
                    <?php foreach ($daftarLog as $log): ?>
                    <tr>
                        <td style="font-family:var(--font-mono);font-size:12px;color:#6b7280;">
                            <?= esc(date('d/m/Y', strtotime($log['waktu']))) ?><br>
                            <strong><?= esc(date('H:i:s', strtotime($log['waktu']))) ?></strong> WIB
                        </td>
                        <td>
                            <div style="font-size:13px;font-weight:600;color:#022760;"><?= esc($log['nama_lengkap']) ?></div>
                            <div style="font-size:11px;color:#6b7280;"><?= esc($log['username']) ?></div>
                        </td>
                        <td style="font-size:13px;"><?= esc($log['aksi']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($pager->getPageCount() > 1): ?>
                <div style="padding:12px 20px;border-top:1px solid #e5e7eb;"><?= $pager->links('default', 'kpni_pager') ?></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <p style="font-size:11px;color:#9ca3af;margin-top:8px;">Menampilkan 10 aktivitas terbaru per halaman.</p>
</div>

<?= $this->endSection() ?>
