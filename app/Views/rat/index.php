<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-6xl mx-auto px-5 py-8">

    <div class="page-header">
        <div>
            <h1 class="page-title">Konfigurasi RAT</h1>
            <p class="page-subtitle">Kelola Rapat Anggota Tahunan per tahun buku</p>
        </div>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
        <div class="card p-4 mb-5" style="border-left:4px solid #A8295A;background:#fdf2f5;">
            <ul style="margin:0;padding-left:16px;color:#6B1F3A;font-size:13px;">
                <?php foreach (session()->getFlashdata('errors') as $e): ?>
                    <li><?= esc($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Form Buat RAT Baru -->
    <div class="card p-6 mb-6">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <div style="width:36px;height:36px;background:#E8F4FD;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <i class="ti ti-plus" style="font-size:18px;color:#0960A8;"></i>
            </div>
            <div>
                <div style="font-size:15px;font-weight:700;color:#022760;">Buat RAT Baru</div>
                <div style="font-size:12px;color:#6b7280;">Sistem otomatis membuat 5 sesi (Sesi 1–5, bisa diubah di Kelola Sesi). RAT baru otomatis menjadi RAT aktif.</div>
            </div>
        </div>
        <form action="/rat" method="post">
            <?= csrf_field() ?>
            <div class="rat-form-grid">
                <div class="span-2">
                    <label class="form-label">Nama RAT</label>
                    <input type="text" name="nama_rat" value="<?= esc(old('nama_rat')) ?>" class="input-field" placeholder="Rapat Anggota Tahunan Tahun Buku <?= date('Y') ?>" required>
                </div>
                <div>
                    <label class="form-label">Tahun Buku</label>
                    <input type="number" name="tahun_buku" value="<?= esc(old('tahun_buku')) ?>" class="input-field" placeholder="<?= date('Y') ?>" min="2020" max="2100" required>
                </div>
                <div>
                    <label class="form-label">Tanggal (opsional)</label>
                    <input type="date" name="tanggal" value="<?= esc(old('tanggal')) ?>" class="input-field">
                </div>
                <div class="span-3">
                    <label class="form-label">Lokasi (opsional)</label>
                    <input type="text" name="lokasi" value="<?= esc(old('lokasi')) ?>" class="input-field" placeholder="Ruang Majapahit / Kantin PT NOK Indonesia">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="width:100%;height:42px;justify-content:center;">
                        <i class="ti ti-plus"></i> Buat RAT
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Histori RAT -->
    <div class="card">
        <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
            <div style="font-size:15px;font-weight:700;color:#022760;">Histori RAT</div>
        </div>
        <?php if (empty($daftarRat)): ?>
            <div style="padding:40px;text-align:center;color:#6b7280;font-size:14px;">Belum ada RAT yang dibuat.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tahun Buku</th>
                    <th>Nama RAT</th>
                    <th>Lokasi</th>
                    <th>Total Peserta RAT</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daftarRat as $r): ?>
                <?php
                    $sedangDipilih = session()->get('rat_dipilih') == $r['id'];
                    $totalPeserta  = isset($r['total_peserta']) ? $r['total_peserta'] : 0;
                ?>
                <tr style="<?= $sedangDipilih ? 'background:#E8F4FD;' : '' ?>">
                    <td style="font-weight:700;color:#022760;"><?= esc($r['tahun_buku']) ?></td>
                    <td style="font-size:13px;">
                        <?= esc($r['nama_rat']) ?>
                        <?php if ($sedangDipilih): ?>
                            <span class="badge badge-success" style="margin-left:6px;font-size:10px;">● Aktif</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:#6b7280;font-size:12px;"><?= esc($r['lokasi'] ?: '—') ?></td>
                    <td>
                        <?php if ($totalPeserta > 0): ?>
                            <span style="font-weight:600;color:#022760;"><?= $totalPeserta ?></span>
                            <span style="color:#6b7280;font-size:12px;"> peserta</span>
                        <?php else: ?>
                            <span style="color:#9ca3af;font-size:12px;">Belum diimport</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= $r['status']==='aktif' ? 'badge-active' : 'badge-locked' ?>">
                            <i class="ti <?= $r['status']==='aktif' ? 'ti-circle-check' : 'ti-circle-x' ?>" style="font-size:11px;"></i>
                            <?= $r['status']==='aktif' ? 'Aktif' : 'Selesai' ?>
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                            <?php if (!$sedangDipilih): ?>
                            <a href="/rat/pilih/<?= esc($r['id']) ?>" class="btn btn-primary btn-sm">
                                <i class="ti ti-cursor-text"></i> Pilih
                            </a>
                            <?php else: ?>
                            <span class="btn btn-ghost btn-sm" style="cursor:default;opacity:0.6;">
                                <i class="ti ti-check"></i> Dipilih
                            </span>
                            <?php endif; ?>

                            <?php if ($r['status']==='aktif'): ?>
                            <button class="btn btn-indigo btn-sm" onclick="showModal({
                                title: 'Selesaikan RAT?',
                                message: 'RAT <?= esc(addslashes($r['nama_rat'])) ?> akan ditandai selesai. Data tetap tersimpan.',
                                confirmText: 'Selesaikan',
                                type: 'warning',
                                onConfirm: () => document.getElementById('form-selesai-<?= $r['id'] ?>').submit()
                            })">
                                <i class="ti ti-check"></i> Selesaikan
                            </button>
                            <form id="form-selesai-<?= $r['id'] ?>" action="/rat/<?= $r['id'] ?>/selesaikan" method="post" style="display:none;">
                                <?= csrf_field() ?>
                            </form>
                            <?php endif; ?>

                            <button class="btn btn-danger btn-sm" onclick="showModal({
                                title: 'Hapus RAT?',
                                message: 'Menghapus RAT ini akan menghapus semua data sesi dan absensi terkait. Tindakan ini tidak bisa dibatalkan.',
                                confirmText: 'Hapus Permanen',
                                type: 'danger',
                                onConfirm: () => document.getElementById('form-hapus-<?= $r['id'] ?>').submit()
                            })">
                                <i class="ti ti-trash"></i>
                            </button>
                            <form id="form-hapus-<?= $r['id'] ?>" action="/rat/<?= $r['id'] ?>/hapus" method="post" style="display:none;">
                                <?= csrf_field() ?>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<style>
    .rat-form-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;align-items:end; }
    .rat-form-grid .span-2 { grid-column:span 2; }
    .rat-form-grid .span-3 { grid-column:span 3; }
    @media (max-width:768px) {
        .rat-form-grid { grid-template-columns:minmax(0,1fr) minmax(0,1fr); }
        .rat-form-grid .span-2, .rat-form-grid .span-3 { grid-column:1 / -1; }
        .rat-form-grid > div:last-child { grid-column:1 / -1; }
    }
</style>

<?= $this->endSection() ?>
