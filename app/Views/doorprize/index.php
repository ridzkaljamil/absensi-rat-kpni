<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="max-w-6xl mx-auto px-5 py-8 page-enter">

    <div class="page-header">
        <div>
            <h1 class="page-title">Doorprize</h1>
            <p class="page-subtitle"><?= esc($rat['nama_rat']) ?> · <?= $totalEligible ?> peserta eligible dari <?= $totalPeserta ?> peserta RAT</p>
        </div>
    </div>

    <!-- Tab selector -->
    <div style="display:flex;gap:4px;margin-bottom:20px;background:rgba(255,255,255,0.6);border-radius:10px;padding:4px;">
        <button onclick="switchTab('barang')" id="tab-barang" class="btn btn-sm" style="flex:1;border-radius:8px;justify-content:center;"><i class="ti ti-gift"></i> Barang</button>
        <button onclick="switchTab('eligible')" id="tab-eligible" class="btn btn-sm" style="flex:1;border-radius:8px;justify-content:center;"><i class="ti ti-circle-check"></i> Eligible</button>
        <button onclick="switchTab('picker')" id="tab-picker" class="btn btn-sm" style="flex:1;border-radius:8px;justify-content:center;"><i class="ti ti-arrows-shuffle"></i> Random Picker</button>
    </div>

    <!-- ═══ TAB BARANG ═══ -->
    <div id="sec-barang" class="tab-section">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="font-size:16px;font-weight:700;color:#022760;margin:0;">Daftar Hadiah Doorprize</h2>
            <button onclick="document.getElementById('modal-tambah-barang').style.display='flex'" class="btn btn-primary btn-sm"><i class="ti ti-plus"></i> Tambah Barang</button>
        </div>
        <?php if (empty($items)): ?>
            <div class="card p-8 text-center"><p style="color:#6b7280;">Belum ada barang. Tambahkan hadiah doorprize terlebih dahulu.</p></div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:16px;">
            <?php foreach ($items as $item): ?>
            <div class="card p-4 card-hover">
                <?php if ($item['gambar_path']): ?>
                    <img src="/<?= esc($item['gambar_path']) ?>" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:10px;margin-bottom:12px;">
                <?php else: ?>
                    <div style="width:100%;height:140px;background:#E8F4FD;border-radius:10px;margin-bottom:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="ti ti-gift" style="font-size:40px;color:#0960A8;opacity:0.4;"></i>
                    </div>
                <?php endif; ?>
                <div style="font-size:15px;font-weight:700;color:#022760;margin-bottom:4px;"><?= esc($item['nama_barang']) ?></div>
                <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;">
                    <span class="badge <?= $item['kategori']==='utama' ? 'badge-active' : 'badge-info' ?>"><?= ucfirst($item['kategori']) ?></span>
                    <span style="font-size:12px;color:#6b7280;">× <?= esc($item['jumlah']) ?> unit</span>
                </div>
                <div style="display:flex;gap:6px;">
                    <button onclick="openEditBarang(<?= htmlspecialchars(json_encode($item)) ?>)" class="btn btn-edit btn-sm" style="flex:1;"><i class="ti ti-edit"></i> Edit</button>
                    <button onclick="showModal({title:'Hapus Barang?',message:'<?= esc(addslashes($item['nama_barang'])) ?> akan dihapus.',confirmText:'Hapus',type:'danger',onConfirm:()=>document.getElementById('fhi-<?= $item['id'] ?>').submit()})" class="btn btn-danger btn-sm" style="flex:1;"><i class="ti ti-trash"></i> Hapus</button>
                </div>
                <form id="fhi-<?= $item['id'] ?>" action="/doorprize/item/<?= $item['id'] ?>/hapus" method="post" style="display:none;"><?= csrf_field() ?></form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ═══ TAB ELIGIBLE ═══ -->
    <div id="sec-eligible" class="tab-section" style="display:none;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="font-size:16px;font-weight:700;color:#022760;margin:0;">Peserta Eligible Doorprize <span style="font-weight:400;color:#6b7280;font-size:13px;">— wajib hadir di seluruh <?= count($daftarSesi) ?> sesi</span></h2>
            <span style="font-size:13px;color:#0960A8;font-weight:600;"><?= $totalEligible ?> eligible</span>
        </div>

        <!-- Filter card -->
        <div class="card p-4 mb-4">
            <div style="display:flex;gap:6px;align-items:center;">
                    <input type="text" id="elig-search" placeholder="Cari NIP/nama..." class="input-field" style="flex:2;min-width:0;height:36px;font-size:12px;">
                    <select id="elig-dept" class="input-field" style="flex:1.5;min-width:0;height:36px;font-size:12px;"><option value="">Semua Dept</option><?php foreach ($eligDeptList as $d): ?><option value="<?= esc($d) ?>"><?= esc($d) ?></option><?php endforeach; ?></select>
                    <select id="elig-sect" class="input-field" style="flex:1.5;min-width:0;height:36px;font-size:12px;"><option value="">Semua Section</option><?php foreach ($eligSectList as $s): ?><option value="<?= esc($s) ?>"><?= esc($s) ?></option><?php endforeach; ?></select>
                    <select id="elig-shift" class="input-field" style="flex:0.8;min-width:0;height:36px;font-size:12px;"><option value="">Shift</option><option>A</option><option>B</option><option>N</option></select>
                    <button type="button" onclick="filterElig()" class="btn btn-primary" style="height:36px;padding:0 14px;flex-shrink:0;"><i class="ti ti-filter"></i> Filter</button>
                    <button type="button" onclick="resetEligFilter()" class="btn btn-ghost" style="height:36px;padding:0 10px;flex-shrink:0;"><i class="ti ti-x"></i> Reset</button>
            </div>
        </div>

        <div class="card" style="overflow:hidden;">
            <div style="overflow-x:auto;">
            <table class="data-table" id="tabel-eligible">
                <thead><tr><th style="width:45px;">No</th><th style="width:90px;">NIP</th><th>Nama</th><th>Departemen</th><th>Section</th><th style="text-align:center;width:60px;">Shift</th>
                    <?php foreach ($daftarSesi as $s): ?><th style="text-align:center;width:70px;"><?= esc($s['nama_sesi']) ?></th><?php endforeach; ?>
                    <th style="text-align:center;width:80px;">Status</th></tr></thead>
                <tbody>
                    <?php if (empty($eligible)): ?>
                        <tr><td colspan="<?= 7 + count($daftarSesi) ?>" style="text-align:center;padding:40px;color:#6b7280;">Belum ada peserta eligible.</td></tr>
                    <?php else: foreach ($eligible as $i => $e): ?>
                    <tr class="elig-row" data-dept="<?= esc($e['departemen'] ?? '') ?>" data-sect="<?= esc($e['section'] ?? '') ?>" data-shift="<?= esc($e['shift'] ?? '') ?>" style="<?= $e['sudah_menang'] ? 'opacity:0.5;' : '' ?>">
                        <td class="elig-no" style="color:#9ca3af;font-size:12px;"><?= $i + 1 ?></td>
                        <td style="font-family:var(--font-mono);font-weight:600;color:#0960A8;"><?= esc($e['nip']) ?></td>
                        <td style="font-weight:500;"><?= esc($e['nama']) ?></td>
                        <td style="font-size:12px;color:#6b7280;"><?= esc($e['departemen'] ?? '-') ?></td>
                        <td style="font-size:12px;color:#6b7280;"><?= esc($e['section'] ?? '-') ?></td>
                        <td style="text-align:center;"><?php if(!empty($e['shift'])): ?><span class="badge badge-info"><?= esc($e['shift']) ?></span><?php else: ?>-<?php endif; ?></td>
                        <?php foreach ($daftarSesi as $s): ?>
                        <td style="text-align:center;"><?= ($e['hadir_'.$s['id']] ?? false) ? '<span style="color:#16a34a;font-weight:700;">✓</span>' : '<span style="color:#d1d5db;">—</span>' ?></td>
                        <?php endforeach; ?>
                        <td style="text-align:center;"><?= $e['sudah_menang'] ? '<span class="badge badge-warning">🏆 Menang</span>' : '<span class="badge badge-success">Eligible</span>' ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
            <!-- Pagination JS -->
            <div id="elig-pager" style="padding:12px 16px;border-top:1px solid #e5e7eb;display:none;"></div>
        </div>
    </div>

<!-- ═══ TAB RANDOM PICKER ═══ -->
    <div id="sec-picker" class="tab-section" style="display:none;">
        <?php $totalSisaUnit = array_sum(array_column($sisaItems, 'sisa')); ?>
        <div class="picker-layout">

            <!-- Panggung undian -->
            <div id="picker-stage" class="picker-stage">
                <div class="stage-deco stage-deco-1"></div>
                <div class="stage-deco stage-deco-2"></div>

                <div class="stage-top">
                    <span class="stage-eyebrow"><i class="ti ti-gift"></i> Undian Doorprize</span>
                    <button type="button" onclick="toggleFullscreen()" class="stage-icon-btn" title="Layar penuh (untuk proyektor)"><i class="ti ti-maximize" id="ico-fullscreen"></i></button>
                </div>

                <!-- Hadiah terpilih -->
                <div id="stage-prize" class="stage-prize">
                    <div id="stage-prize-img" class="stage-prize-img"><i class="ti ti-gift"></i></div>
                    <div style="min-width:0;">
                        <div id="stage-prize-nama" class="stage-prize-nama">Pilih hadiah di samping</div>
                        <div id="stage-prize-meta" class="stage-prize-meta">Hadiah yang dipilih akan tampil di sini</div>
                    </div>
                </div>

                <!-- Slot -->
                <div class="slot-window">
                    <div id="slot-reel" class="slot-reel"><span class="slot-name slot-idle">?</span></div>
                    <div class="slot-fade"></div>
                </div>
                <div id="slot-label" class="slot-label"><?= $totalEligible > 0 ? $totalEligible . ' peserta eligible siap diundi' : 'Belum ada peserta eligible' ?></div>

                <!-- Pemenang -->
                <div id="winner-display" class="winner-box" style="display:none;">
                    <div id="winner-eyebrow" class="winner-eyebrow">Selamat!</div>
                    <div id="winner-detail" class="winner-detail"></div>
                </div>

                <button id="btn-undi" type="button" onclick="mulaiUndi()" class="btn-undi" disabled>
                    <i class="ti ti-arrows-shuffle"></i> <span id="btn-undi-text">Pilih hadiah dulu</span>
                </button>
            </div>

            <!-- Panel samping -->
            <div class="picker-side">
                <div class="picker-stats">
                    <div class="pstat"><div class="pstat-num" id="pstat-eligible"><?= $totalEligible ?></div><div class="pstat-lbl">Eligible belum menang</div></div>
                    <div class="pstat"><div class="pstat-num"><?= count($winners) ?></div><div class="pstat-lbl">Sudah menang</div></div>
                    <div class="pstat"><div class="pstat-num"><?= $totalSisaUnit ?></div><div class="pstat-lbl">Unit belum diundi</div></div>
                </div>

                <div class="card prize-list-card">
                    <div class="prize-list-head">Pilih Hadiah <span><?= count($sisaItems) ?> barang</span></div>
                    <?php if (empty($sisaItems)): ?>
                        <div class="prize-empty">
                            <i class="ti ti-gift-off"></i>
                            <p>Semua hadiah sudah diundi atau belum ada barang.</p>
                            <button type="button" onclick="switchTab('barang')" class="btn btn-ghost btn-sm">Ke tab Barang</button>
                        </div>
                    <?php else: ?>
                        <div class="prize-list">
                            <?php foreach ($sisaItems as $item): ?>
                                <button type="button" class="prize-item" data-id="<?= (int) $item['id'] ?>"
                                    data-nama="<?= esc($item['nama_barang']) ?>" data-img="<?= esc($item['gambar_path'] ?? '') ?>"
                                    data-kat="<?= esc($item['kategori'] ?? 'utama') ?>" data-sisa="<?= (int) $item['sisa'] ?>" data-jumlah="<?= (int) $item['jumlah'] ?>"
                                    onclick="pilihHadiah(this)">
                                    <span class="prize-thumb">
                                        <?php if (! empty($item['gambar_path'])): ?>
                                            <img src="/<?= esc($item['gambar_path']) ?>" alt="" loading="lazy" onerror="this.remove()">
                                        <?php endif; ?>
                                        <i class="ti ti-gift"></i>
                                    </span>
                                    <span class="prize-info">
                                        <span class="prize-name"><?= esc($item['nama_barang']) ?></span>
                                        <span class="prize-sub">
                                            <span class="badge <?= ($item['kategori'] ?? 'utama') === 'utama' ? 'badge-active' : 'badge-info' ?>" style="padding:1px 8px;font-size:10px;"><?= esc(ucfirst($item['kategori'] ?? 'utama')) ?></span>
                                            sisa <?= (int) $item['sisa'] ?> dari <?= (int) $item['jumlah'] ?>
                                        </span>
                                    </span>
                                    <i class="ti ti-chevron-right prize-arrow"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Riwayat Pemenang -->
        <div class="card" style="overflow:hidden;margin-top:20px;">
            <div style="padding:14px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                <span style="font-size:14px;font-weight:700;color:#022760;">Riwayat Pemenang <span style="font-weight:500;color:#6b7280;font-size:12px;">(<?= count($winners) ?>)</span></span>
                <?php if (!empty($winners)): ?>
                <a href="/doorprize/export-winners" class="btn btn-amber btn-sm"><i class="ti ti-download"></i> Export Excel</a>
                <?php endif; ?>
            </div>
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th style="width:44px;">No</th><th>Barang</th><th>Kategori</th><th>NIP</th><th>Pemenang</th><th>Departemen</th><th>Section</th><th>Waktu</th><th style="text-align:center;">Aksi</th></tr></thead>
                <tbody>
                    <?php if (empty($winners)): ?>
                        <tr><td colspan="9" style="text-align:center;padding:32px;color:#6b7280;">Belum ada pemenang.</td></tr>
                    <?php else: ?>
                        <?php foreach ($winners as $i => $w): ?>
                        <tr>
                            <td style="color:#9ca3af;"><?= $i+1 ?></td>
                            <td style="font-weight:600;color:#022760;"><?= esc($w['nama_barang']) ?></td>
                            <td><span class="badge <?= $w['kategori']==='utama' ? 'badge-active' : 'badge-info' ?>"><?= esc(ucfirst($w['kategori'])) ?></span></td>
                            <td style="font-family:var(--font-mono);color:#0960A8;"><?= esc($w['nip']) ?></td>
                            <td style="font-weight:500;"><?= esc($w['nama']) ?></td>
                            <td style="font-size:12px;color:#6b7280;"><?= esc($w['dept_live'] ?? '-') ?></td>
                            <td style="font-size:12px;color:#6b7280;"><?= esc($w['section_live'] ?? '-') ?></td>
                            <td style="font-size:12px;color:#6b7280;"><?= esc(date('d/m H:i', strtotime($w['waktu_undi']))) ?></td>
                            <td style="text-align:center;">
                                <button type="button" title="Batalkan pemenang" onclick="showModal({title:'Batalkan Pemenang?',message:'<?= esc($w['nama'], 'js') ?> akan dihapus dari daftar pemenang dan bisa diundi lagi.',confirmText:'Batalkan',type:'danger',onConfirm:()=>document.getElementById('fhw-<?= (int) $w['id'] ?>').submit()})" class="btn btn-danger btn-sm" style="height:26px;font-size:11px;padding:0 8px;"><i class="ti ti-x" style="font-size:12px;"></i></button>
                                <form id="fhw-<?= (int) $w['id'] ?>" action="/doorprize/winner/<?= (int) $w['id'] ?>/hapus" method="post" style="display:none;"><?= csrf_field() ?></form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<style>
    .picker-layout { display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start; }
    @media (max-width:900px) { .picker-layout { grid-template-columns:minmax(0,1fr); } .picker-side { order:-1; } }

    .picker-stage { position:relative;overflow:hidden;border-radius:18px;padding:22px 26px 26px;color:#fff;
        background:linear-gradient(140deg,#022760 0%,#102A83 55%,#0960A8 100%);box-shadow:0 10px 30px rgba(2,39,96,.25);
        display:flex;flex-direction:column;gap:18px;min-height:460px; }
    .stage-deco { position:absolute;border-radius:50%;pointer-events:none; }
    .stage-deco-1 { width:240px;height:240px;top:-90px;right:-70px;background:rgba(0,158,224,.12); }
    .stage-deco-2 { width:160px;height:160px;bottom:-60px;left:-40px;background:rgba(127,255,180,.06); }
    .stage-top { display:flex;justify-content:space-between;align-items:center;position:relative; }
    .stage-eyebrow { font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.6);display:flex;gap:6px;align-items:center; }
    .stage-icon-btn { width:34px;height:34px;border-radius:9px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:background .2s; }
    .stage-icon-btn:hover { background:rgba(255,255,255,.18); }

    .stage-prize { position:relative;display:flex;align-items:center;gap:16px;padding:14px;border-radius:14px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12); }
    .stage-prize-img { width:76px;height:76px;flex-shrink:0;border-radius:12px;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;overflow:hidden;font-size:34px;color:rgba(125,211,252,.8); }
    .stage-prize-img img { width:100%;height:100%;object-fit:cover; }
    .stage-prize-nama { font-size:20px;font-weight:800;line-height:1.2;overflow:hidden;text-overflow:ellipsis; }
    .stage-prize-meta { font-size:12px;color:rgba(255,255,255,.65);margin-top:4px; }

    .slot-window { position:relative;height:120px;border-radius:14px;background:rgba(0,0,0,.25);border:1px solid rgba(0,158,224,.35);display:flex;align-items:center;justify-content:center;overflow:hidden; }
    .slot-window::before, .slot-window::after { content:'';position:absolute;left:18px;right:18px;height:1px;background:rgba(0,158,224,.35); }
    .slot-window::before { top:28px; } .slot-window::after { bottom:28px; }
    .slot-reel { position:relative;z-index:1;text-align:center;padding:0 16px;max-width:100%; }
    .slot-name { display:block;font-size:30px;font-weight:800;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .slot-idle { color:rgba(255,255,255,.35); }
    .slot-win { color:#7FFFB4;text-shadow:0 0 22px rgba(127,255,180,.45); }
    .slot-fade { position:absolute;inset:0;pointer-events:none;background:linear-gradient(180deg,rgba(2,39,96,.85) 0%,transparent 30%,transparent 70%,rgba(2,39,96,.85) 100%); }
    .slot-label { text-align:center;font-size:13px;color:rgba(255,255,255,.7);margin-top:-6px; }

    .winner-box { text-align:center;padding:14px;border-radius:14px;background:rgba(127,255,180,.08);border:1px solid rgba(127,255,180,.3);animation:bounceIn .4s ease-out; }
    .winner-eyebrow { font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#7FFFB4;font-weight:700; }
        .winner-detail { font-size:14px;color:rgba(255,255,255,.85);margin-top:6px; }

    .btn-undi { margin-top:auto;align-self:center;display:inline-flex;align-items:center;gap:8px;height:52px;padding:0 34px;border:none;border-radius:14px;cursor:pointer;
        font-size:16px;font-weight:800;color:#022760;background:linear-gradient(135deg,#7FFFB4,#4ade80);box-shadow:0 8px 24px rgba(74,222,128,.35);transition:transform .2s,box-shadow .2s,opacity .2s; }
    .btn-undi:hover:not(:disabled) { transform:translateY(-2px) scale(1.03);box-shadow:0 12px 30px rgba(74,222,128,.45); }
    .btn-undi:disabled { cursor:not-allowed;opacity:.45;box-shadow:none;background:rgba(255,255,255,.25);color:#fff; }

    .picker-side { display:flex;flex-direction:column;gap:14px; }
    .picker-stats { display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px; }
    .pstat { background:rgba(255,255,255,.9);border-radius:12px;padding:12px 10px;text-align:center;box-shadow:0 2px 12px rgba(2,39,96,.06); }
    .pstat-num { font-size:24px;font-weight:800;color:#022760;line-height:1; }
    .pstat-lbl { font-size:10.5px;color:#6b7280;margin-top:5px;line-height:1.25; }

    .prize-list-card { overflow:hidden; }
    .prize-list-head { padding:12px 16px;font-size:13px;font-weight:700;color:#022760;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center; }
    .prize-list-head span { font-size:11px;font-weight:600;color:#6b7280; }
    .prize-list { max-height:330px;overflow-y:auto;padding:6px; }
    .prize-item { width:100%;display:flex;align-items:center;gap:12px;padding:8px 10px;border:1.5px solid transparent;border-radius:10px;background:none;cursor:pointer;text-align:left;transition:background .15s,border-color .15s; }
    .prize-item:hover { background:#F0F7FF; }
    .prize-item.is-active { background:#E8F4FD;border-color:#0960A8; }
    .prize-thumb { position:relative;width:44px;height:44px;flex-shrink:0;border-radius:9px;background:#E8F4FD;display:flex;align-items:center;justify-content:center;overflow:hidden;color:#0960A8;font-size:20px; }
    .prize-thumb img { position:absolute;inset:0;width:100%;height:100%;object-fit:cover; }
    .prize-info { min-width:0;flex:1;display:flex;flex-direction:column;gap:3px; }
    .prize-name { font-size:13px;font-weight:700;color:#022760;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
    .prize-sub { font-size:11px;color:#6b7280;display:flex;align-items:center;gap:6px; }
    .prize-arrow { color:#cbd5e1;font-size:16px; }
    .prize-item.is-active .prize-arrow { color:#0960A8; }
    .prize-empty { padding:28px 16px;text-align:center;color:#6b7280;font-size:13px; }
    .prize-empty i { font-size:36px;color:#cbd5e1; }
    .prize-empty p { margin:8px 0 12px; }

    /* Mode layar penuh untuk proyektor */
    .picker-stage:fullscreen { border-radius:0;padding:40px 8vw;justify-content:center;gap:28px; }
    .picker-stage:fullscreen .stage-prize-img { width:140px;height:140px;font-size:60px; }
    .picker-stage:fullscreen .stage-prize-nama { font-size:40px; }
    .picker-stage:fullscreen .stage-prize-meta { font-size:18px; }
    .picker-stage:fullscreen .slot-window { height:200px; }
    .picker-stage:fullscreen .slot-name { font-size:64px; }
    .picker-stage:fullscreen .slot-label { font-size:18px; }
    .picker-stage:fullscreen .winner-eyebrow { font-size:18px; }
    .picker-stage:fullscreen .winner-detail { font-size:20px; }
    .picker-stage:fullscreen .btn-undi { height:68px;font-size:22px;padding:0 48px; }
    @media (max-width:640px) {
        .picker-stage { padding:18px;min-height:0; }
        .slot-name { font-size:22px; }
        .stage-prize-img { width:60px;height:60px; }
        .stage-prize-nama { font-size:17px; }
        .btn-undi { width:100%;justify-content:center; }
        .prize-list { max-height:240px; }
    }
</style>

<!-- Modal Tambah Barang -->
<div id="modal-tambah-barang" style="display:none;" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box" style="max-width:460px;">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
            <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#E8F4FD;">
                <i class="ti ti-gift" style="color:#0960A8;font-size:20px;"></i>
            </div>
            <div><h3 style="font-size:16px;font-weight:700;color:#022760;margin:0 0 6px;">Tambah Barang Doorprize</h3>
                <p style="font-size:13px;color:#6b7280;margin:0;">Tambahkan hadiah untuk diundi.</p></div>
        </div>
        <form method="POST" action="/doorprize/item/store" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div style="margin-bottom:12px;">
                <label class="form-label">Nama Barang <span style="color:red;">*</span></label>
                <input type="text" name="nama_barang" required placeholder="Contoh: Kompor Gas Rinnai" class="input-field">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                <div><label class="form-label">Jumlah</label><input type="number" name="jumlah" value="1" min="1" class="input-field"></div>
                <div><label class="form-label">Kategori</label><select name="kategori" class="input-field"><option value="utama">Utama</option><option value="hiburan">Hiburan</option></select></div>
            </div>
            <div style="margin-bottom:16px;">
                <label class="form-label">Gambar Barang (opsional)</label>
                <input type="file" name="gambar" accept="image/*" class="input-field" style="height:auto;padding:8px 14px;">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" onclick="document.getElementById('modal-tambah-barang').style.display='none'" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary">Tambahkan</button>
            </div>
        </form>
    </div>
</div>


<!-- Modal Edit Barang -->
<div id="modal-edit-barang" style="display:none;" class="modal-overlay" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-box" style="max-width:460px;">
        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;"><div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#E8F4FD;"><i class="ti ti-edit" style="color:#0960A8;font-size:20px;"></i></div><div><h3 style="font-size:16px;font-weight:700;color:#022760;margin:0 0 6px;">Edit Barang Doorprize</h3></div></div>
        <form method="POST" id="form-edit-barang" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div style="margin-bottom:12px;"><label class="form-label">Nama Barang *</label><input type="text" name="nama_barang" id="edit-nama-barang" required class="input-field"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;"><div><label class="form-label">Jumlah</label><input type="number" name="jumlah" id="edit-jumlah" value="1" min="1" class="input-field"></div><div><label class="form-label">Kategori</label><select name="kategori" id="edit-kategori" class="input-field"><option value="utama">Utama</option><option value="hiburan">Hiburan</option></select></div></div>
            <div style="margin-bottom:16px;"><label class="form-label">Ganti Gambar (opsional)</label><input type="file" name="gambar" accept="image/*" class="input-field" style="height:auto;padding:8px 14px;"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;"><button type="button" onclick="this.closest('.modal-overlay').style.display='none'" class="btn btn-ghost">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>
<script>
function openEditBarang(item) {
    document.getElementById('edit-nama-barang').value = item.nama_barang;
    document.getElementById('edit-jumlah').value = item.jumlah;
    document.getElementById('edit-kategori').value = item.kategori;
    document.getElementById('form-edit-barang').action = '/doorprize/item/' + item.id + '/update';
    document.getElementById('modal-edit-barang').style.display = 'flex';
}

// ═══ Eligible filter + pagination ═══
var eligPerPage = 20;
function filterElig() {
    var q = (document.getElementById('elig-search').value || '').toLowerCase();
    var dept = document.getElementById('elig-dept').value;
    var sect = document.getElementById('elig-sect').value;
    var shift = document.getElementById('elig-shift').value;
    var rows = document.querySelectorAll('.elig-row');
    var visible = [];
    rows.forEach(function(tr) {
        var txt = tr.textContent.toLowerCase();
        var show = true;
        if (q && txt.indexOf(q) === -1) show = false;
        if (dept && tr.dataset.dept !== dept) show = false;
        if (sect && tr.dataset.sect !== sect) show = false;
        if (shift && tr.dataset.shift !== shift) show = false;
        tr.style.display = show ? '' : 'none';
        if (show) visible.push(tr);
    });
    // Re-number + paginate
    eligPaginate(visible, 1);
}
function eligPaginate(visibleRows, page) {
    var total = visibleRows.length;
    var pages = Math.ceil(total / eligPerPage);
    var start = (page - 1) * eligPerPage;
    visibleRows.forEach(function(tr, i) {
        tr.style.display = (i >= start && i < start + eligPerPage) ? '' : 'none';
        tr.querySelector('.elig-no').textContent = i + 1;
    });
    var pager = document.getElementById('elig-pager');
    if (pages <= 1) { pager.style.display = 'none'; return; }
    pager.style.display = '';
    var sN = 'display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 10px;border-radius:6px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid;transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1);cursor:pointer;background:transparent;color:#064687;border-color:#e5e7eb;';
    var sA = 'display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 10px;border-radius:6px;font-size:13px;font-weight:700;text-decoration:none;border:1.5px solid;background:#0960A8;color:white;border-color:#0960A8;';
    var sD = 'display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 10px;border-radius:6px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid;background:transparent;color:#c4c4c4;border-color:#f0f0f0;cursor:not-allowed;';
    var ho = ' onmouseover="this.style.background=\'#E8F4FD\';this.style.borderColor=\'#0960A8\';this.style.transform=\'translateY(-2px) scale(1.08)\'" onmouseout="this.style.background=\'transparent\';this.style.borderColor=\'#e5e7eb\';this.style.transform=\'\'"';
    var h = '<div style="display:flex;gap:4px;justify-content:center;flex-wrap:wrap;align-items:center;">';
    // First + Prev
    if (page > 1) {
        h += '<a onclick="eligPaginate(getVisibleElig(),1)" style="' + sN + '"' + ho + '>«</a>';
        h += '<a onclick="eligPaginate(getVisibleElig(),' + (page-1) + ')" style="' + sN + '"' + ho + '>‹ Prev</a>';
    } else {
        h += '<span style="' + sD + '">«</span><span style="' + sD + '">‹ Prev</span>';
    }
    // Page numbers (surround 2)
    var startP = Math.max(1, page - 2), endP = Math.min(pages, page + 2);
    for (var i = startP; i <= endP; i++) {
        if (i === page) {
            h += '<span style="' + sA + '">' + i + '</span>';
        } else {
            h += '<a onclick="eligPaginate(getVisibleElig(),' + i + ')" style="' + sN + '"' + ho + '>' + i + '</a>';
        }
    }
    // Next + Last
    if (page < pages) {
        h += '<a onclick="eligPaginate(getVisibleElig(),' + (page+1) + ')" style="' + sN + '"' + ho + '>Next ›</a>';
        h += '<a onclick="eligPaginate(getVisibleElig(),' + pages + ')" style="' + sN + '"' + ho + '>»</a>';
    } else {
        h += '<span style="' + sD + '">Next ›</span><span style="' + sD + '">»</span>';
    }
    h += '</div>';
    pager.innerHTML = h;
}
function getVisibleElig() {
    var q = (document.getElementById('elig-search').value || '').toLowerCase();
    var dept = document.getElementById('elig-dept').value;
    var sect = document.getElementById('elig-sect').value;
    var shift = document.getElementById('elig-shift').value;
    var rows = document.querySelectorAll('.elig-row');
    var visible = [];
    rows.forEach(function(tr) {
        var txt = tr.textContent.toLowerCase();
        var show = true;
        if (q && txt.indexOf(q) === -1) show = false;
        if (dept && tr.dataset.dept !== dept) show = false;
        if (sect && tr.dataset.sect !== sect) show = false;
        if (shift && tr.dataset.shift !== shift) show = false;
        if (show) visible.push(tr);
    });
    return visible;
}
function resetEligFilter() {
    document.getElementById('elig-search').value = '';
    document.getElementById('elig-dept').value = '';
    document.getElementById('elig-sect').value = '';
    document.getElementById('elig-shift').value = '';
    filterElig();
}
// Init: show all, paginate
document.addEventListener('DOMContentLoaded', function() {
    var allRows = document.querySelectorAll('.elig-row');
    allRows.forEach(function(tr) { tr.dataset.visible = '1'; });
    eligPaginate(Array.from(allRows), 1);
});

// ═══ Tab Switching ═══
function switchTab(tab) {
    ['barang','eligible','picker'].forEach(t => {
        document.getElementById('sec-'+t).style.display = t===tab ? '' : 'none';
        document.getElementById('tab-'+t).className = 'btn btn-sm ' + (t===tab ? 'btn-primary' : 'btn-ghost');
        document.getElementById('tab-'+t).style.flex = '1';
        document.getElementById('tab-'+t).style.borderRadius = '8px';
    });
    location.hash = tab;
}
// Restore tab dari URL hash
var savedTab = location.hash.replace('#','');
switchTab(['barang','eligible','picker'].indexOf(savedTab) > -1 ? savedTab : 'barang');

// ═══ Sound Effects ═══
function tadaSound(){
    try{var ctx=new(window.AudioContext||window.webkitAudioContext)();
    [523,659,784,1047].forEach(function(f,i){
    var o=ctx.createOscillator(),g=ctx.createGain();
    o.connect(g);g.connect(ctx.destination);o.type='sine';
    o.frequency.setValueAtTime(f,ctx.currentTime+i*0.12);
    g.gain.setValueAtTime(0,ctx.currentTime+i*0.12);
    g.gain.linearRampToValueAtTime(0.3,ctx.currentTime+i*0.12+0.05);
    g.gain.exponentialRampToValueAtTime(0.001,ctx.currentTime+i*0.12+0.6);
    o.start(ctx.currentTime+i*0.12);o.stop(ctx.currentTime+i*0.12+0.6);});}catch(e){}}

// ═══ Random Picker ═══
var hadiahDipilih = null;
var isSpinning = false;

function pilihHadiah(el) {
    if (isSpinning) return;
    document.querySelectorAll('.prize-item').forEach(function (b) { b.classList.toggle('is-active', b === el); });
    hadiahDipilih = { id: el.dataset.id, nama: el.dataset.nama, img: el.dataset.img, kat: el.dataset.kat, sisa: parseInt(el.dataset.sisa, 10), jumlah: parseInt(el.dataset.jumlah, 10) };

    var box = document.getElementById('stage-prize-img');
    box.innerHTML = '<i class="ti ti-gift"></i>';
    if (hadiahDipilih.img) {
        var im = new Image();
        im.alt = '';
        im.onload = function () { box.innerHTML = ''; box.appendChild(im); };
        im.src = '/' + hadiahDipilih.img;
    }
    document.getElementById('stage-prize-nama').textContent = hadiahDipilih.nama;
    document.getElementById('stage-prize-meta').textContent =
        'Kategori ' + hadiahDipilih.kat.charAt(0).toUpperCase() + hadiahDipilih.kat.slice(1) + ' · sisa ' + hadiahDipilih.sisa + ' dari ' + hadiahDipilih.jumlah + ' unit';

    document.getElementById('winner-display').style.display = 'none';
    document.getElementById('slot-reel').innerHTML = '<span class="slot-name slot-idle">?</span>';
    var eligible = parseInt(document.getElementById('pstat-eligible').textContent, 10) || 0;
    document.getElementById('btn-undi').disabled = eligible === 0;
    document.getElementById('btn-undi-text').textContent = eligible === 0 ? 'Tidak ada peserta eligible' : 'Mulai Undi';
    document.getElementById('slot-label').textContent = eligible + ' peserta eligible siap diundi';
    // Di layar sempit panggung ada di bawah daftar hadiah — gulir ke sana
    if (window.innerWidth <= 900) document.getElementById('picker-stage').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function setLabel(teks, isError) {
    var l = document.getElementById('slot-label');
    l.textContent = teks;
    l.style.color = isError ? '#FFB4B4' : '';
}

function mulaiUndi() {
    if (!hadiahDipilih || isSpinning) return;
    isSpinning = true;
    document.getElementById('btn-undi').disabled = true;
    document.getElementById('btn-undi-text').textContent = 'Mengundi...';
    document.getElementById('winner-display').style.display = 'none';
    setLabel('Mengundi ' + hadiahDipilih.nama + '...');

    var fd = new FormData();
    fd.append('id_item', hadiahDipilih.id);
    fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    fetch('/doorprize/pick', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.status !== 'success') { gagalUndi(data.message || 'Undian gagal.'); return; }
            animasiSlot(data.pool || [], data.winner, data.barang, data.sisa);
        })
        .catch(function () { gagalUndi('Koneksi ke server gagal. Coba lagi.'); });
}

function gagalUndi(pesan) {
    isSpinning = false;
    setLabel(pesan, true);
    document.getElementById('btn-undi').disabled = false;
    document.getElementById('btn-undi-text').textContent = 'Coba Lagi';
}

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function animasiSlot(pool, winner, barang, sisa) {
    var reel = document.getElementById('slot-reel');
    var names = pool.length ? pool.slice() : [winner.nama];
    while (names.length < 24) names = names.concat(pool.length ? pool : [winner.nama]); // putaran minimal ±3 detik
    names = names.slice(0, 30).concat([winner.nama]);
    var step = 0, total = names.length;

    (function tick() {
        reel.innerHTML = '<span class="slot-name">' + escHtml(names[step]) + '</span>';
        step++;
        if (step < total) {
            setTimeout(tick, 45 + Math.pow(step / total, 3) * 420); // makin lama makin pelan
            return;
        }
        reel.innerHTML = '<span class="slot-name slot-win">' + escHtml(winner.nama) + '</span>';
        document.getElementById('winner-eyebrow').textContent = 'Selamat! Pemenang ' + barang;
        document.getElementById('winner-detail').textContent =
            'NIP ' + winner.nip + (winner.departemen ? ' · ' + winner.departemen : '') + (winner.section ? ' · ' + winner.section : '');
        document.getElementById('winner-display').style.display = '';
        setLabel(sisa > 0 ? 'Sisa ' + sisa + ' unit ' + barang + ' lagi' : 'Semua unit ' + barang + ' sudah diundi');
        tadaSound();
        launchConfetti();
        isSpinning = false;
        document.getElementById('btn-undi-text').textContent = 'Memuat ulang...';
        // Muat ulang supaya daftar sisa & riwayat pemenang ter-update (tab tetap di #picker).
        // Di mode layar penuh jeda lebih lama agar nama pemenang sempat dibacakan.
        setTimeout(function () { location.reload(); }, document.fullscreenElement ? 8000 : 4000);
    })();
}

// ═══ Fullscreen panggung undian (untuk proyektor) ═══
function toggleFullscreen() {
    var el = document.getElementById('picker-stage');
    if (!document.fullscreenElement) { el.requestFullscreen && el.requestFullscreen().catch(function () {}); }
    else { document.exitFullscreen(); }
}
document.addEventListener('fullscreenchange', function () {
    var ico = document.getElementById('ico-fullscreen');
    if (ico) ico.className = 'ti ' + (document.fullscreenElement ? 'ti-minimize' : 'ti-maximize');
});


// ═══ Confetti ═══
function launchConfetti(){
    var canvas=document.createElement('canvas');
    canvas.style.cssText='position:fixed;inset:0;z-index:99999;pointer-events:none;';
    document.body.appendChild(canvas);
    var ctx=canvas.getContext('2d');
    canvas.width=window.innerWidth;canvas.height=window.innerHeight;
    var pieces=[],colors=['#7FFFB4','#FFD97D','#009EE0','#F09595','#AFA9EC','#5DCAA5','#F0997B'];
    for(var i=0;i<120;i++)pieces.push({x:canvas.width/2,y:canvas.height/2,vx:(Math.random()-0.5)*16,vy:Math.random()*-14-4,w:Math.random()*8+4,h:Math.random()*6+3,c:colors[i%colors.length],r:Math.random()*6.28,rv:(Math.random()-0.5)*0.3,g:0.25});
    function draw(){ctx.clearRect(0,0,canvas.width,canvas.height);var alive=false;
    pieces.forEach(function(p){p.x+=p.vx;p.vy+=p.g;p.y+=p.vy;p.r+=p.rv;p.vx*=0.99;
    if(p.y<canvas.height+20){alive=true;ctx.save();ctx.translate(p.x,p.y);ctx.rotate(p.r);
    ctx.fillStyle=p.c;ctx.fillRect(-p.w/2,-p.h/2,p.w,p.h);ctx.restore();}});
    if(alive)requestAnimationFrame(draw);else document.body.removeChild(canvas);}
    requestAnimationFrame(draw);
}

// ESC tutup modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('modal-tambah-barang').style.display = 'none';
});

// Pindah modal ke body
document.addEventListener('DOMContentLoaded', function() {
    var m = document.getElementById('modal-tambah-barang');
    if (m) document.body.appendChild(m);
    m = document.getElementById('modal-edit-barang');
    if (m) document.body.appendChild(m);
});
</script>

<?= $this->endSection() ?>
