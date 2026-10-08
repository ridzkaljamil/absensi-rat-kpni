<?php
/**
 * @var array $rat
 * @var array $peserta
 * @var int $totalDone
 * @var int $totalAll
 * @var string $mode  'peserta' atau 'anggota'
 * @var string $backUrl
 */
$mode = $mode ?? 'peserta';
$backUrl = $backUrl ?? '/peserta-rat';
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div style="max-width:700px;margin:0 auto;padding:24px 20px;">
    <div class="page-header">
        <div>
            <h1 class="page-title">Pendaftaran RFID Massal</h1>
            <p class="page-subtitle"><?= esc($rat['nama_rat'] ?? 'Data Anggota') ?> · <?= $totalDone ?> / <?= $totalAll ?> terdaftar</p>
        </div>
        <a href="<?= $backUrl ?>" class="btn btn-ghost btn-sm"><i class="ti ti-arrow-left" style="font-size:13px;"></i> Kembali</a>
    </div>

    <!-- Progress -->
    <div class="card p-5 mb-4">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-size:13px;font-weight:600;color:#022760;">Progress Pendaftaran</span>
            <span style="font-size:14px;font-weight:700;color:#0960A8;"><span id="prog-done"><?= $totalDone ?></span> / <?= $totalAll ?></span>
        </div>
        <div style="background:#e5e7eb;border-radius:99px;height:10px;overflow:hidden;">
            <div id="prog-bar" style="background:linear-gradient(90deg,#0960A8,#009EE0);height:100%;border-radius:99px;transition:width 0.5s ease;width:<?= $totalAll > 0 ? round($totalDone/$totalAll*100) : 0 ?>%;"></div>
        </div>
        <div style="text-align:right;margin-top:4px;font-size:11px;color:#6b7280;"><?= $totalAll > 0 ? round($totalDone/$totalAll*100) : 0 ?>% selesai</div>
    </div>

    <?php if (empty($peserta)): ?>
        <div class="card p-8" style="text-align:center;">
            <div style="width:64px;height:64px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="ti ti-circle-check" style="font-size:32px;color:#16a34a;"></i>
            </div>
            <p style="font-size:18px;font-weight:700;color:#022760;margin-bottom:4px;">Semua Sudah Terdaftar!</p>
            <p style="font-size:13px;color:#6b7280;"><?= $totalAll ?> peserta sudah punya kartu RFID.</p>
        </div>
    <?php else: ?>

    <!-- Current peserta card -->
    <div class="card mb-4" id="card-current" style="border:2px solid #0960A8;overflow:hidden;">
        <div style="background:linear-gradient(135deg,#022760,#0960A8);padding:16px 20px;color:white;">
            <div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.6);margin-bottom:2px;">Peserta <span id="cur-idx">1</span> dari <?= count($peserta) ?></div>
            <div style="font-size:26px;font-weight:800;" id="cur-nama"><?= esc($peserta[0]['nama']) ?></div>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:10px;">
                <span style="background:rgba(0,158,224,0.2);padding:4px 14px;border-radius:20px;font-family:var(--font-mono);font-weight:700;font-size:14px;border:1px solid rgba(0,158,224,0.3);" id="cur-nip"><?= esc($peserta[0]['nip']) ?></span>
                <span style="background:rgba(255,255,255,0.08);padding:4px 14px;border-radius:20px;font-size:12px;" id="cur-dept"><?= esc($peserta[0]['departemen'] ?? '-') ?></span>
                <span style="background:rgba(255,255,255,0.08);padding:4px 14px;border-radius:20px;font-size:12px;" id="cur-section"><?= esc($peserta[0]['section'] ?? '-') ?></span>
                <?php if (!empty($peserta[0]['shift'])): ?>
                <span style="background:rgba(0,158,224,0.2);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;" id="cur-shift">Shift <?= esc($peserta[0]['shift']) ?></span>
                <?php else: ?>
                <span id="cur-shift"></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="padding:20px;">
            <div style="position:relative;">
                <i class="ti ti-nfc" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:22px;color:#0960A8;"></i>
                <input type="text" id="input-rfid" placeholder="Tap kartu RFID..." autofocus
                    style="width:100%;height:56px;border:2.5px solid #009EE0;border-radius:12px;padding:0 16px 0 48px;font-size:18px;text-align:center;letter-spacing:3px;outline:none;box-sizing:border-box;">
            </div>
            <div id="status-msg" style="text-align:center;margin-top:12px;font-size:14px;color:#6b7280;">
                <i class="ti ti-scan" style="font-size:16px;"></i> Menunggu tap kartu RFID...
            </div>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="card">
        <div style="padding:12px 16px;border-bottom:1px solid #e5e7eb;font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">Baru Didaftarkan</div>
        <div id="riwayat" style="max-height:240px;overflow-y:auto;"></div>
        <div id="riwayat-empty" style="padding:24px;text-align:center;color:#9ca3af;font-size:13px;">Belum ada pendaftaran. Tap kartu untuk mulai.</div>
    </div>

    <script>
    var peserta=<?= json_encode(array_map(function($p){return['id'=>$p['id'],'nip'=>$p['nip'],'nama'=>$p['nama'],'departemen'=>$p['departemen']??'-','section'=>$p['section']??'-','shift'=>$p['shift']??''];}, $peserta)) ?>;
    var idx=0,totalDone=<?=$totalDone?>,totalAll=<?=$totalAll?>,processing=false;
    var inputEl=document.getElementById('input-rfid');
    var saveUrl='<?= $mode==="anggota" ? "/anggota/bulk-rfid-save" : "/peserta-rat/bulk-rfid-save" ?>';

    inputEl.addEventListener('keydown',function(e){
        if(e.key!=='Enter')return;e.preventDefault();
        var uid=this.value.trim();this.value='';
        if(!uid||processing)return;
        saveRfid(peserta[idx],uid);
    });
    document.addEventListener('click',function(e){if(e.target!==inputEl)setTimeout(function(){inputEl.focus()},50)});

    function saveRfid(p,uid){
        processing=true;
        document.getElementById('status-msg').innerHTML='<span style="color:#0960A8;"><i class="ti ti-loader-2" style="font-size:16px;animation:spin 0.8s linear infinite;"></i> Menyimpan...</span>';
        var fd=new FormData();fd.append('id',p.id);fd.append('uid_rfid',uid);fd.append('<?=csrf_token()?>','<?=csrf_hash()?>');
        fetch(saveUrl,{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
            if(d.status==='success'){
                document.getElementById('status-msg').innerHTML='<span style="color:#16a34a;font-weight:700;font-size:16px;"><i class="ti ti-circle-check" style="font-size:18px;"></i> Tersimpan!</span>';
                document.getElementById('riwayat-empty').style.display='none';
                var rw=document.getElementById('riwayat');
                rw.innerHTML='<div style="padding:10px 16px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;animation:slideUp .2s ease-out;"><div><span style="font-weight:600;color:#022760;font-size:13px;">'+p.nama+'</span><span style="font-family:var(--font-mono);color:#0960A8;margin-left:8px;font-size:12px;">'+p.nip+'</span><span style="color:#6b7280;margin-left:8px;font-size:11px;">'+p.departemen+' · '+p.section+'</span></div><span style="color:#16a34a;font-size:11px;font-weight:600;background:#dcfce7;padding:2px 8px;border-radius:4px;">✓ '+uid.substring(0,10)+'</span></div>'+rw.innerHTML;
                totalDone++;
                document.getElementById('prog-done').textContent=totalDone;
                var pct=Math.round(totalDone/totalAll*100);
                document.getElementById('prog-bar').style.width=pct+'%';
                idx++;
                if(idx<peserta.length){setTimeout(function(){showPeserta(idx);processing=false;},800);}
                else{document.getElementById('card-current').innerHTML='<div style="text-align:center;padding:32px;"><div style="width:64px;height:64px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><i class="ti ti-circle-check" style="font-size:32px;color:#16a34a;"></i></div><p style="font-size:20px;font-weight:700;color:#022760;margin-bottom:4px;">Selesai!</p><p style="font-size:13px;color:#6b7280;">Semua peserta sudah didaftarkan.</p></div>';processing=false;}
            }else{
                document.getElementById('status-msg').innerHTML='<span style="color:#A8295A;font-weight:600;"><i class="ti ti-alert-circle" style="font-size:16px;"></i> '+d.message+'</span>';
                processing=false;setTimeout(function(){inputEl.focus()},100);
            }
        }).catch(function(){document.getElementById('status-msg').innerHTML='<span style="color:#A8295A;">Koneksi gagal. Coba lagi.</span>';processing=false;});
    }

    function showPeserta(i){
        var p=peserta[i];
        document.getElementById('cur-idx').textContent=i+1;
        document.getElementById('cur-nama').textContent=p.nama;
        document.getElementById('cur-nip').textContent=p.nip;
        document.getElementById('cur-dept').textContent=p.departemen;
        document.getElementById('cur-section').textContent=p.section;
        document.getElementById('cur-shift').textContent=p.shift?'Shift '+p.shift:'';
        document.getElementById('status-msg').innerHTML='<i class="ti ti-scan" style="font-size:16px;"></i> Menunggu tap kartu RFID...';
        inputEl.focus();
    }
    </script>
    <?php endif; ?>
</div>
<style>
@keyframes slideUp{0%{opacity:0;transform:translateY(8px)}100%{opacity:1;transform:translateY(0)}}
@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
</style>
<?= $this->endSection() ?>
