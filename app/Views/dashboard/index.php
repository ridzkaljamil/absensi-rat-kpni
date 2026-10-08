<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-6xl mx-auto px-5 py-8">

    <?php if (! $rat): ?>
        <div class="card p-10 text-center">
            <div style="width:64px;height:64px;background:#E8F4FD;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="ti ti-clipboard-list" style="font-size:32px;color:#0960A8;"></i>
            </div>
            <h2 style="font-size:18px;font-weight:700;color:#022760;margin:0 0 8px;">Belum Ada RAT Dipilih</h2>
            <p style="color:#6b7280;font-size:14px;margin:0 0 20px;">Buat atau pilih RAT terlebih dahulu untuk mulai mencatat absensi.</p>
            <?php if (session('role') === 'admin'): ?>
                <a href="/rat" class="btn btn-primary"><i class="ti ti-plus"></i> Konfigurasi RAT</a>
            <?php endif; ?>
        </div>
    <?php else:
        $isAdmin     = session('role') === 'admin';
        $jumlahSesi  = count($sesi_data);
        $semuaCapai  = $jumlahSesi > 0 && $sesi_capai === $jumlahSesi;
    ?>

        <div class="page-header">
            <div>
                <h1 class="page-title"><?= esc($rat['nama_rat']) ?></h1>
                <p class="page-subtitle"><?= esc($total_peserta) ?> peserta RAT · <?= $jumlahSesi ?> sesi · kuorum dihitung per sesi (50% + 1)</p>
            </div>
            <div class="page-actions">
                <a href="/absensi" class="btn btn-primary btn-sm"><i class="ti ti-checkup-list"></i> Buka Absensi</a>
                <?php if ($isAdmin): ?>
                    <a href="/rat" class="btn btn-ghost btn-sm"><i class="ti ti-refresh"></i> Ganti RAT</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="grid-resp grid-resp-3" style="margin-bottom:20px;">
            <div class="card card-hover p-5 card-solid" style="background:linear-gradient(135deg,#022760,#102A83);">
                <div class="stat-label">Hadir Terbanyak</div>
                <div id="total-hadir" class="stat-value"><?= esc($max_hadir) ?></div>
                <div class="stat-note" style="color:#7DD3FC;">dari <?= esc($total_peserta) ?> peserta RAT</div>
            </div>
            <div class="card card-hover p-5 card-solid" style="background:linear-gradient(135deg,#064687,#0960A8);">
                <div class="stat-label">Status Kuorum</div>
                <div style="display:flex;align-items:baseline;gap:8px;">
                    <span id="kuorum-capai" class="stat-value" style="color:<?= $semuaCapai ? '#7FFFB4' : '#FFD97D' ?>;"><?= $sesi_capai ?></span>
                    <span class="stat-value-sub">/ <?= $jumlahSesi ?> sesi tercapai</span>
                </div>
                <div id="kuorum-ket" class="stat-note" style="color:rgba(255,255,255,0.75);">
                    <?php if ($semuaCapai): ?>
                        Semua sesi sudah memenuhi kuorum
                    <?php elseif ($sesi_dinilai < $jumlahSesi): ?>
                        <?= $jumlahSesi - $sesi_dinilai ?> sesi belum punya daftar peserta
                    <?php else: ?>
                        <?= $jumlahSesi - $sesi_capai ?> sesi belum memenuhi kuorum
                    <?php endif; ?>
                </div>
            </div>
            <div class="card card-hover p-5 card-solid" style="background:linear-gradient(135deg,#0960A8,#009EE0);">
                <div class="stat-label">Diperbarui</div>
                <div id="last-update" class="stat-value" style="font-size:32px;"><?= date('H:i:s') ?></div>
                <div class="stat-note" style="display:flex;align-items:center;gap:6px;color:rgba(255,255,255,0.75);">
                    <span class="live-dot"></span> Live · refresh 5 detik
                </div>
            </div>
        </div>

        <!-- Kartu per sesi -->
        <div class="grid-resp grid-resp-3" id="grid-sesi" style="margin-bottom:20px;">
            <?php foreach ($sesi_data as $s):
                $sesi    = $s['sesi'];
                $kosong  = $s['total'] === 0;
                $aktif   = $sesi['status'] === 'aktif';
            ?>
                <div class="card card-hover p-5 card-solid sesi-card" id="kartu-sesi-<?= esc($sesi['id']) ?>" data-total="<?= $s['total'] ?>">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:14px;">
                        <div style="min-width:0;">
                            <div style="font-size:17px;font-weight:800;color:#022760;line-height:1.25;"><?= esc(label_sesi($sesi['nama_sesi'])) ?></div>
                            <div style="font-size:12px;color:#6b7280;margin-top:3px;">
                                <i class="ti ti-clock" style="font-size:12px;"></i>
                                <?= esc(substr($sesi['waktu_mulai'], 0, 5)) ?> – <?= esc(substr($sesi['waktu_selesai'], 0, 5)) ?> WIB
                            </div>
                        </div>
                        <span class="badge-status badge <?= $aktif ? 'badge-active' : 'badge-locked' ?>">
                            <i class="ti <?= $aktif ? 'ti-circle-check' : 'ti-lock' ?>" style="font-size:11px;"></i>
                            <?= $aktif ? 'Aktif' : 'Terkunci' ?>
                        </span>
                    </div>

                    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:10px;">
                        <div>
                            <span class="jumlah-hadir" style="font-size:36px;font-weight:800;color:#022760;line-height:1;"><?= esc($s['jumlah']) ?></span>
                            <span style="font-size:12px;color:#6b7280;margin-left:2px;">hadir</span>
                        </div>
                        <span class="persen" style="font-size:30px;font-weight:800;line-height:1;color:<?= $kosong ? '#cbd5e1' : ($s['kuorum_capai'] ? '#15803d' : '#0960A8') ?>;"><?= $kosong ? '–' : esc($s['persen']) . '%' ?></span>
                    </div>
                    <div class="progress-track" style="margin-bottom:10px;">
                        <div class="progress-fill <?= $s['kuorum_capai'] ? 'is-ok' : '' ?>" style="width:<?= esc(min($s['persen'], 100)) ?>%;"></div>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;font-size:12px;">
                        <span class="total-ket" style="color:#6b7280;">
                            <?= $kosong ? 'Belum ada daftar peserta' : 'dari ' . esc($s['total']) . ' peserta' ?>
                        </span>
                        <?php if ($kosong): ?>
                            <?php if ($isAdmin): ?>
                                <a href="/peserta-rat?sesi=<?= esc($sesi['id']) ?>" class="kuorum-label" style="font-weight:600;color:#0960A8;text-decoration:none;">Atur peserta <i class="ti ti-arrow-right" style="font-size:11px;"></i></a>
                            <?php else: ?>
                                <span class="kuorum-label" style="font-weight:600;color:#94a3b8;">—</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="kuorum-label" style="font-weight:600;color:<?= $s['kuorum_capai'] ? '#15803d' : '#d97706' ?>;">
                                <?= $s['kuorum_capai'] ? '✓ Kuorum' : '⚠ Kuorum' ?> <?= esc($s['kuorum']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($sesi_data)): ?>
                <div class="card p-8 text-center" style="grid-column:1/-1;">
                    <p style="color:#6b7280;margin:0 0 12px;">Belum ada sesi untuk RAT ini.</p>
                    <?php if ($isAdmin): ?><a href="/sesi" class="btn btn-primary">Buat Sesi</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($isAdmin): ?>
            <div class="card p-4" style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="/absensi" class="btn btn-primary btn-sm"><i class="ti ti-checkup-list"></i> Halaman Absensi</a>
                <a href="/peserta-rat" class="btn btn-edit btn-sm"><i class="ti ti-clipboard-check"></i> Peserta RAT</a>
                <a href="/sesi" class="btn btn-indigo btn-sm"><i class="ti ti-clock"></i> Kelola Sesi</a>
                <a href="/rekap" class="btn btn-amber btn-sm"><i class="ti ti-chart-bar"></i> Download Rekap</a>
                <a href="/anggota" class="btn btn-ghost btn-sm"><i class="ti ti-users"></i> Data Anggota</a>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<style>
    .card-solid { border:none; cursor:default; backdrop-filter:none; -webkit-backdrop-filter:none; }
    .card-solid:not(.sesi-card) { color:white; }
    .sesi-card { background:rgba(255,255,255,0.92); }
    .stat-label { font-size:12px; color:rgba(255,255,255,0.7); margin-bottom:8px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; }
    .stat-value { font-size:40px; font-weight:800; color:white; line-height:1; }
    .stat-value-sub { font-size:14px; color:rgba(255,255,255,0.75); font-weight:600; }
    .stat-note { font-size:12px; margin-top:8px; }
    .live-dot { width:8px; height:8px; border-radius:50%; background:#7FFFB4; animation:pulse 2s infinite; display:inline-block; }
    @keyframes pulse { 0%, 100% { opacity:.5 } 50% { opacity:1 } }
    .progress-track { height:8px; background:#e5e7eb; border-radius:99px; overflow:hidden; }
    .progress-fill  { height:8px; background:linear-gradient(90deg,#0960A8,#009EE0); border-radius:99px; transition:width 0.5s ease; }
    .progress-fill.is-ok { background:linear-gradient(90deg,#15803d,#22c55e); }
</style>

<?php if ($rat): ?>
<script>
(function () {
    var JUMLAH_SESI = <?= count($sesi_data) ?>;

    function renderSesi(s) {
        var k = document.getElementById('kartu-sesi-' + s.id_sesi);
        if (!k) return;
        var kosong = s.total === 0;
        kpSetNum(k.querySelector('.jumlah-hadir'), s.jumlah);

        var p = k.querySelector('.persen');
        if (kosong) p.textContent = '–'; else kpSetNum(p, s.persen, '%');
        p.style.color = kosong ? '#cbd5e1' : (s.kuorum_capai ? '#15803d' : '#0960A8');

        var bar = k.querySelector('.progress-fill');
        bar.style.width = Math.min(s.persen, 100) + '%';
        bar.classList.toggle('is-ok', !!s.kuorum_capai);

        k.querySelector('.total-ket').textContent = kosong ? 'Belum ada daftar peserta' : 'dari ' + s.total + ' peserta';

        // Label kuorum hanya diganti kalau status "ada/tidak ada peserta" tidak berubah;
        // kalau berubah, muat ulang supaya tombol "Atur peserta" ikut menyesuaikan.
        if ((parseInt(k.dataset.total, 10) === 0) !== kosong) { location.reload(); return; }
        if (!kosong) {
            var kl = k.querySelector('.kuorum-label');
            kl.textContent = (s.kuorum_capai ? '✓ Kuorum ' : '⚠ Kuorum ') + s.kuorum;
            kl.style.color = s.kuorum_capai ? '#15803d' : '#d97706';
        }

        var b = k.querySelector('.badge-status');
        var aktif = s.status === 'aktif';
        b.className = 'badge-status badge ' + (aktif ? 'badge-active' : 'badge-locked');
        b.innerHTML = '<i class="ti ' + (aktif ? 'ti-circle-check' : 'ti-lock') + '" style="font-size:11px;"></i> ' + (aktif ? 'Aktif' : 'Terkunci');
    }

    function poll() {
        fetch('/dashboard/polling', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.error) return;
                document.getElementById('last-update').textContent = d.waktu;
                kpSetNum(document.getElementById('total-hadir'), d.max_hadir);
                (d.sesi || []).forEach(renderSesi);

                var semua = JUMLAH_SESI > 0 && d.sesi_capai === JUMLAH_SESI;
                var kc = document.getElementById('kuorum-capai');
                kpSetNum(kc, d.sesi_capai);
                kc.style.color = semua ? '#7FFFB4' : '#FFD97D';
                document.getElementById('kuorum-ket').textContent = semua
                    ? 'Semua sesi sudah memenuhi kuorum'
                    : (d.sesi_dinilai < JUMLAH_SESI
                        ? (JUMLAH_SESI - d.sesi_dinilai) + ' sesi belum punya daftar peserta'
                        : (JUMLAH_SESI - d.sesi_capai) + ' sesi belum memenuhi kuorum');
            })
            .catch(function () {});
    }
    setInterval(poll, 5000);

    // Hitung naik saat halaman dibuka
    document.querySelectorAll('#total-hadir, #kuorum-capai, .jumlah-hadir, .persen').forEach(function (el) {
        var teks = el.textContent.trim(), persen = teks.endsWith('%'), nilai = parseInt(teks, 10);
        if (isNaN(nilai)) return;
        el.textContent = '0' + (persen ? '%' : '');
        setTimeout(function () { kpSetNum(el, nilai, persen ? '%' : ''); el.classList.remove('kp-pop'); }, 150);
    });
})();
</script>
<?php endif; ?>

<?= $this->endSection() ?>
