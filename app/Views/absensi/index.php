<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div style="max-width:960px;margin:0 auto;padding:24px 20px;">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#022760,#102A83,#0960A8);border-radius:16px;padding:22px 26px;margin-bottom:20px;color:white;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-30px;right:-30px;width:120px;height:120px;border-radius:50%;background:rgba(0,158,224,0.08);"></div>
        <div style="position:absolute;bottom:-20px;left:30%;width:80px;height:80px;border-radius:50%;background:rgba(127,255,180,0.05);"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;position:relative;">
            <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.5);"><?= esc($rat['nama_rat']) ?></div>
            <a href="/dashboard" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;text-decoration:none;background:rgba(255,255,255,0.12);color:white;border:1.5px solid rgba(255,255,255,0.3);transition:transform 0.2s ease,background 0.2s ease;" onmouseover="this.style.transform='translateY(-2px) scale(1.05)';this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.transform='';this.style.background='rgba(255,255,255,0.12)'"><i class="ti ti-arrow-left" style="font-size:13px;"></i> Dashboard</a>
        </div>
        <div style="display:flex;align-items:baseline;gap:12px;position:relative;">
            <div style="font-size:26px;font-weight:800;"><?= esc(strtoupper(label_sesi($sesi['nama_sesi']))) ?></div>
            <span style="font-size:14px;font-weight:400;opacity:0.7;"><?= esc(substr($sesi['waktu_mulai'],0,5)) ?> – <?= esc(substr($sesi['waktu_selesai'],0,5)) ?> WIB</span>
        </div>
        <div style="display:flex;align-items:center;gap:12px;margin-top:10px;position:relative;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:30px;height:30px;border-radius:8px;background:rgba(0,158,224,0.15);display:flex;align-items:center;justify-content:center;"><i class="ti ti-users" style="font-size:14px;color:#009EE0;"></i></div>
                <span id="jumlah-hadir" style="font-size:28px;font-weight:800;"><?= esc($jumlahHadir) ?></span>
                <span style="font-size:13px;color:#009EE0;">hadir dari <?= esc($totalAnggota) ?> peserta sesi ini</span>
            </div>
            <span style="font-size:12px;padding:3px 10px;border-radius:20px;font-weight:600;<?= $sesi['status']==='aktif' ? 'background:rgba(0,158,224,0.25);color:#009EE0;' : 'background:rgba(255,255,255,0.15);color:rgba(255,255,255,0.6);' ?>"><?= $sesi['status']==='aktif' ? '● Aktif' : '🔒 Terkunci' ?></span>
        </div>
    </div>
    <!-- Pilih sesi -->
    <div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;">
        <?php foreach ($daftarSesi as $s): ?>
        <a href="/absensi/sesi/<?= esc($s['id']) ?>" class="sesi-btn" style="flex:1 1 120px;text-align:center;padding:8px 4px;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:transform 0.2s ease,box-shadow 0.2s ease;<?= (int)$s['id']==(int)$sesi['id'] ? 'background:#022760;color:#009EE0;border:2px solid #009EE0;' : 'background:white;color:#6b7280;border:2px solid #e5e7eb;' ?>"><?= esc(label_sesi($s['nama_sesi'])) ?><?= $s['status']==='terkunci' ? ' 🔒' : '' ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ((int) $totalAnggota === 0 && $sesi['status'] !== 'terkunci'): ?>
    <div class="card p-4 mb-4" style="border-left:4px solid #C9920F;background:#fffbeb;color:#92650A;font-size:13px;">
        <i class="ti ti-alert-triangle"></i> <?= esc(label_sesi($sesi['nama_sesi'])) ?> belum punya daftar peserta, jadi semua absen akan ditolak.
        <?php if (session()->get('role')==='admin'): ?><a href="/peserta-rat?sesi=<?= esc($sesi['id']) ?>" style="color:#0960A8;font-weight:600;">Atur peserta sesi ini →</a><?php else: ?>Hubungi Admin.<?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($sesi['status']==='terkunci'): ?>
    <div class="card p-4 mb-4" style="border-left:4px solid #C9920F;background:#fffbeb;color:#92650A;font-size:13px;"><i class="ti ti-lock"></i> Sesi terkunci. <?php if (session()->get('role')==='admin'): ?><a href="/sesi" style="color:#0960A8;font-weight:600;">Buka kunci →</a><?php endif; ?></div>
    <?php endif; ?>

    <!-- Notifikasi -->
    <div id="area-notif" style="margin-bottom:12px;"></div>

    <!-- Card input + kamera compact -->
    <div class="card p-5" style="margin-bottom:16px;">
        <div id="input-layout" style="display:flex;gap:14px;align-items:stretch;">

            <!-- Kamera compact (hidden default) -->
            <div id="col-kamera" style="display:none;flex:0 0 160px;">
                <div style="border-radius:10px;overflow:hidden;background:#022760;position:relative;height:100%;min-height:120px;">
                    <video id="cam-video" autoplay playsinline muted style="width:100%;height:100%;object-fit:cover;display:block;"></video>
                    <canvas id="cam-canvas" style="display:none;"></canvas>
                    <div style="position:absolute;bottom:4px;left:4px;background:rgba(0,0,0,0.6);color:#7FFFB4;font-size:9px;padding:2px 6px;border-radius:3px;font-weight:600;">● LIVE</div>
                </div>
            </div>

            <!-- Input + toggle -->
            <div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:center;">
                <div style="font-size:12px;font-weight:600;color:#6b7280;text-align:center;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.05em;">Tap kartu RFID atau ketik NIP — Enter untuk absen</div>
                <div style="position:relative;">
                    <i class="ti ti-scan" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:20px;color:#0960A8;"></i>
                    <input type="text" id="input-absensi" placeholder="Tap kartu atau ketik NIP..." autocomplete="off" <?= $sesi['status']==='terkunci' ? 'disabled' : 'autofocus' ?> style="width:100%;height:52px;border:2.5px solid #0960A8;border-radius:10px;padding:0 16px 0 46px;font-size:16px;text-align:center;letter-spacing:2px;outline:none;box-sizing:border-box;background:<?= $sesi['status']==='terkunci' ? '#f8fafc' : 'white' ?>;">
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px;color:#6b7280;" onclick="var cb=document.getElementById('toggle-kamera');cb.checked=!cb.checked;toggleKamera(cb.checked);">
                        <div style="position:relative;width:36px;height:20px;"><input type="checkbox" id="toggle-kamera" style="opacity:0;width:0;height:0;position:absolute;"><span id="toggle-track" style="position:absolute;inset:0;background:#e5e7eb;border-radius:10px;transition:0.3s;"></span><span id="toggle-dot" style="position:absolute;top:2px;left:2px;width:16px;height:16px;background:white;border-radius:50%;transition:0.3s;box-shadow:0 1px 3px rgba(0,0,0,0.2);"></span></div>
                        <i class="ti ti-camera" style="font-size:14px;"></i> Verifikasi Foto
                    </label>
                    <span id="camera-status" style="font-size:11px;color:#9ca3af;">Kamera mati</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 5 terakhir -->
    <div class="card">
        <div style="padding:10px 16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:12px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;">5 Terakhir Absen</span>
            <div style="display:flex;align-items:center;gap:5px;"><div id="dot-koneksi" style="width:7px;height:7px;border-radius:50%;background:#16a34a;"></div><span id="label-koneksi" style="font-size:11px;color:#6b7280;">OK</span></div>
        </div>
        <div style="overflow-x:auto;">
        <table class="data-table" id="tabel-terbaru">
            <thead><tr><th>NIP</th><th>Nama</th><th>Departemen</th><th>Section</th><th>Shift</th><th>Waktu</th><th>Metode</th></tr></thead>
            <tbody>
                <?php if (empty($terbaruAbsen)): ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af;">Belum ada absensi.</td></tr>
                <?php else: foreach ($terbaruAbsen as $t): ?>
                <tr>
                    <td style="font-family:var(--font-mono);font-weight:600;color:#0960A8;"><?= esc($t['nip']) ?></td>
                    <td style="font-weight:600;"><?= esc($t['nama']) ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?= esc($t['departemen'] ?? '-') ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?= esc($t['section'] ?? '-') ?></td>
                    <td style="text-align:center;"><?php if(!empty($t['shift'])): ?><span class="badge badge-info"><?= esc($t['shift']) ?></span><?php else: ?>-<?php endif; ?></td>
                    <td style="font-size:12px;color:#6b7280;"><?= esc($t['waktu']) ?></td>
                    <td><span class="badge <?= $t['metode']==='rfid'?'badge-active':'badge-info' ?>" style="font-size:10px;"><?= strtoupper($t['metode']) ?></span></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal konfirmasi data induk -->
<div id="modal-ki" style="display:none;position:fixed;inset:0;background:rgba(2,39,96,0.6);backdrop-filter:blur(8px);z-index:9999;align-items:center;justify-content:center;" onclick="if(event.target===this)tutupKI()">
    <div style="background:white;border-radius:20px;padding:24px;width:90%;max-width:380px;box-shadow:0 24px 60px rgba(2,39,96,0.3);">
        <div style="text-align:center;margin-bottom:16px;"><div style="width:48px;height:48px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;"><i class="ti ti-alert-triangle" style="font-size:22px;color:#92400e;"></i></div><h2 style="font-size:15px;font-weight:700;color:#111;margin:0 0 6px;">NIP Tidak Ada di Data Induk</h2></div>
        <div style="background:#E8F4FD;border-radius:10px;padding:12px;margin-bottom:16px;font-size:13px;"><div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span style="color:#6b7280;">Nama</span><span style="font-weight:600;color:#022760;" id="ki-nama"></span></div><div style="display:flex;justify-content:space-between;"><span style="color:#6b7280;">NIP</span><span style="font-family:var(--font-mono);font-weight:600;" id="ki-nip"></span></div></div>
        <input type="hidden" id="ki-sesi"><input type="hidden" id="ki-nipv"><input type="hidden" id="ki-met">
        <div style="display:flex;gap:8px;"><button onclick="tutupKI()" style="flex:1;height:38px;border:1.5px solid #0960A8;background:#E8F4FD;color:#064687;border-radius:8px;font-weight:600;cursor:pointer;">Batal</button><button onclick="submitKI()" style="flex:1;height:38px;background:linear-gradient(135deg,#0960A8,#009EE0);color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Tambahkan & Absen</button></div>
    </div>
</div>

<script>
var idSesi=<?=(int)$sesi['id']?>,sesiAktif=<?=$sesi['status']==='aktif'?'true':'false'?>;
var input=document.getElementById('input-absensi'),notif=document.getElementById('area-notif');
var jumlahEl=document.getElementById('jumlah-hadir'),tbody=document.querySelector('#tabel-terbaru tbody');
var kameraOn=false,stream=null,ntimer=null;
function beep(t){try{var c=new(window.AudioContext||window.webkitAudioContext)(),o=c.createOscillator(),g=c.createGain();o.connect(g);g.connect(c.destination);o.type='sine';if(t==='success'){o.frequency.setValueAtTime(880,c.currentTime);o.frequency.setValueAtTime(1100,c.currentTime+.1);g.gain.setValueAtTime(.3,c.currentTime);g.gain.exponentialRampToValueAtTime(.001,c.currentTime+.3);o.start();o.stop(c.currentTime+.3)}else{o.frequency.setValueAtTime(300,c.currentTime);g.gain.setValueAtTime(.3,c.currentTime);g.gain.exponentialRampToValueAtTime(.001,c.currentTime+.5);o.start();o.stop(c.currentTime+.5)}}catch(e){}}
function E(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML}
function fotoEl(p,glow){var g=glow||'127,255,180';return p?'<img src="/'+p+'" style="width:100px;height:100px;min-width:100px;border-radius:20px;object-fit:cover;border:3px solid rgba('+g+',0.3);box-shadow:0 0 24px rgba('+g+',0.15),inset 0 0 12px rgba('+g+',0.05);">':'<div style="width:100px;height:100px;min-width:100px;border-radius:20px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;border:3px solid rgba('+g+',0.3);box-shadow:0 0 24px rgba('+g+',0.15),inset 0 0 12px rgba('+g+',0.05);"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 10-16 0"/></svg></div>'}

function toggleKamera(on){kameraOn=on;var tr=document.getElementById('toggle-track'),dt=document.getElementById('toggle-dot'),st=document.getElementById('camera-status'),col=document.getElementById('col-kamera');if(on){tr.style.background='#0960A8';dt.style.left='18px';st.textContent='Memulai...';st.style.color='#0960A8';if(!navigator.mediaDevices){camFail();return}navigator.mediaDevices.getUserMedia({video:true}).then(function(s){stream=s;document.getElementById('cam-video').srcObject=s;col.style.display='block';st.textContent='Kamera aktif'}).catch(function(){camFail()})}else{tr.style.background='#e5e7eb';dt.style.left='2px';st.textContent='Kamera mati';st.style.color='#9ca3af';col.style.display='none';if(stream){stream.getTracks().forEach(function(t){t.stop()});stream=null}}}
function camFail(){var st=document.getElementById('camera-status');st.textContent='Tidak tersedia';st.style.color='#A8295A';document.getElementById('toggle-kamera').checked=false;kameraOn=false;document.getElementById('toggle-track').style.background='#e5e7eb';document.getElementById('toggle-dot').style.left='2px';document.getElementById('col-kamera').style.display='none'}
function snap(){if(!kameraOn||!stream)return null;var v=document.getElementById('cam-video'),c=document.getElementById('cam-canvas');if(!v.videoWidth)return null;c.width=v.videoWidth;c.height=v.videoHeight;c.getContext('2d').drawImage(v,0,0);return c.toDataURL('image/jpeg',0.7)}

if(sesiAktif){input.focus();document.addEventListener('click',function(e){if(e.target!==input&&!e.target.closest('button')&&!e.target.closest('a'))setTimeout(function(){input.focus()},50)});input.addEventListener('keydown',function(e){if(e.key!=='Enter')return;e.preventDefault();var v=this.value.trim();this.value='';if(!v)return;proses(v,/^\d{6}$/.test(v)?'manual':'rfid')})}

function proses(val,metode){var fd=new FormData();fd.append('nip',val);fd.append('id_sesi',idSesi);fd.append('metode',metode);fd.append('<?=csrf_token()?>','<?=csrf_hash()?>');var f=snap();if(f)fd.append('foto',f);fetch('/absensi/proses',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){showNotif(d)}).catch(function(){
        offlineQueue.push({nip:val,id_sesi:idSesi,metode:metode,waktu:new Date().toTimeString().substr(0,8)});saveQueue();
        showNotif({status:'success',nama:'Disimpan offline ('+offlineQueue.length+' antrian)',nip:val,waktu:new Date().toTimeString().substr(0,8),metode:metode,departemen:'-',section:'-',shift:'-'})
    }).finally(function(){if(sesiAktif)input.focus()})}

function showNotif(d){
    if(ntimer)clearTimeout(ntimer);notif.innerHTML='';
    if(d.status==='konfirmasi_induk'){showKI(d);return}
    var h='';
    if(d.status==='success'){
        beep('success');
        h='<div style="background:rgba(10,94,63,0.95);backdrop-filter:blur(12px);border-radius:18px;padding:24px;color:white;border-left:5px solid #7FFFB4;position:relative;overflow:hidden;animation:slideUp .3s ease-out;box-shadow:0 8px 32px rgba(10,94,63,0.3);">'
        +'<div style="position:absolute;top:-50px;right:-50px;width:160px;height:160px;border-radius:50%;background:rgba(127,255,180,0.06);"></div>'
        +'<div style="display:flex;align-items:center;gap:20px;position:relative;">'
        +'<div style="position:relative;flex-shrink:0;">'+fotoEl(d.foto_path,'127,255,180')+'<div style="position:absolute;bottom:-5px;right:-5px;width:28px;height:28px;background:#16a34a;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid rgba(10,94,63,0.95);font-size:14px;font-weight:700;">✓</div></div>'
        +'<div style="flex:1;">'
        +'<div style="font-size:28px;font-weight:800;line-height:1.1;margin-bottom:8px;">'+E(d.nama)+'</div>'
        +'<div style="display:flex;flex-wrap:wrap;gap:6px;font-size:12px;">'
        +'<span style="background:rgba(127,255,180,0.15);padding:5px 14px;border-radius:20px;font-family:var(--font-mono);font-weight:700;border:1px solid rgba(127,255,180,0.25);">'+E(d.nip)+'</span>'
        +'<span style="background:rgba(255,255,255,0.08);padding:5px 14px;border-radius:20px;">'+E(d.departemen||'-')+'</span>'
        +'<span style="background:rgba(255,255,255,0.08);padding:5px 14px;border-radius:20px;">'+E(d.section||'-')+'</span>'
        +(d.shift&&d.shift!=='-'?'<span style="background:rgba(127,255,180,0.15);padding:5px 12px;border-radius:20px;font-weight:700;">Shift '+E(d.shift)+'</span>':'')
        +'</div>'
        +'<div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;">'
        +'<div style="display:flex;align-items:center;gap:8px;"><span style="font-size:11px;opacity:0.5;">'+d.waktu+' WIB · '+(d.metode==='rfid'?'RFID':'Manual')+'</span><span style="background:rgba(127,255,180,0.2);color:#7FFFB4;padding:3px 12px;border-radius:20px;font-size:10px;font-weight:700;">BERHASIL</span></div>'
        +(d.id_absensi?'<span id="btn-batal" data-id="'+d.id_absensi+'" onclick="konfirmasiBatal('+d.id_absensi+')" style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);padding:4px 12px;border-radius:6px;font-size:10px;cursor:pointer;"><span id="batal-countdown">10</span>s ✗ Batalkan</span>':'')
        +'</div>'
        +'</div></div></div>';
        kpSetNum(jumlahEl,parseInt(jumlahEl.textContent)+1);
        if(d.id_absensi)startBatalCountdown();
        if(d.lima&&tbody){updateTabel(d.lima)}
    }else if(d.kode==='sudah_absen'){
        beep('error');var det=d.detail||{};
        h='<div style="background:rgba(146,101,10,0.95);backdrop-filter:blur(12px);border-radius:18px;padding:24px;color:white;border-left:5px solid #FFD97D;position:relative;overflow:hidden;animation:slideUp .3s ease-out;box-shadow:0 8px 32px rgba(146,101,10,0.3);">'
        +'<div style="position:absolute;top:-50px;right:-50px;width:160px;height:160px;border-radius:50%;background:rgba(255,217,125,0.06);"></div>'
        +'<div style="display:flex;align-items:center;gap:20px;position:relative;">'
        +'<div style="position:relative;flex-shrink:0;">'+fotoEl(det.foto_path,'255,217,125')+'<div style="position:absolute;bottom:-5px;right:-5px;width:28px;height:28px;background:#C9920F;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid rgba(146,101,10,0.95);font-size:14px;">⚠</div></div>'
        +'<div style="flex:1;">'
        +'<div style="font-size:28px;font-weight:800;line-height:1.1;margin-bottom:8px;">'+E(det.nama||'?')+'</div>'
        +'<div style="display:flex;flex-wrap:wrap;gap:6px;font-size:12px;">'
        +'<span style="background:rgba(255,217,125,0.15);padding:5px 14px;border-radius:20px;font-family:var(--font-mono);font-weight:700;border:1px solid rgba(255,217,125,0.2);">'+(det.nip||'?')+'</span>'
        +'<span style="background:rgba(255,255,255,0.08);padding:5px 14px;border-radius:20px;">'+(det.departemen||'-')+'</span>'
        +'<span style="background:rgba(255,255,255,0.08);padding:5px 14px;border-radius:20px;">'+(det.section||'-')+'</span>'
        +(det.shift&&det.shift!=='-'?'<span style="background:rgba(255,217,125,0.15);padding:5px 12px;border-radius:20px;font-weight:700;">Shift '+E(det.shift)+'</span>':'')
        +'</div>'
        +'<div style="display:flex;align-items:center;gap:8px;margin-top:10px;">'
        +'<span style="font-size:11px;opacity:0.5;">Absen: '+(det.waktu||'?')+' WIB · '+((det.metode||'?').toUpperCase())+'</span>'
        +'<span style="background:rgba(255,217,125,0.2);color:#FFD97D;padding:3px 12px;border-radius:20px;font-size:10px;font-weight:700;">SUDAH ABSEN</span>'
        +'</div>'
        +'</div></div></div>';
    }else if(d.kode==='kartu_belum_terdaftar'){
        beep('error');h='<div style="background:rgba(9,96,168,0.95);backdrop-filter:blur(12px);border-radius:18px;padding:24px;color:white;border-left:5px solid #009EE0;position:relative;overflow:hidden;animation:slideUp .3s ease-out;box-shadow:0 8px 32px rgba(9,96,168,0.3);"><div style="position:absolute;top:-50px;right:-50px;width:160px;height:160px;border-radius:50%;background:rgba(0,158,224,0.06);"></div><div style="position:relative;text-align:center;"><div style="font-size:22px;font-weight:800;margin-bottom:6px;">ℹ Kartu Belum Terdaftar</div><div style="font-size:13px;opacity:.85;">'+E(d.message)+'</div></div></div>';
    }else if(d.kode==='bukan_peserta'){
        beep('error');
        h='<div style="background:rgba(107,31,58,0.95);backdrop-filter:blur(12px);border-radius:18px;padding:24px;color:white;border-left:5px solid #F09595;position:relative;overflow:hidden;animation:slideUp .3s ease-out;box-shadow:0 8px 32px rgba(107,31,58,0.3);">'
        +'<div style="position:absolute;top:-50px;right:-50px;width:160px;height:160px;border-radius:50%;background:rgba(240,149,149,0.06);"></div>'
        +'<div style="position:relative;text-align:center;">'
        +'<div style="font-size:22px;font-weight:800;margin-bottom:6px;">Tidak Terdaftar</div>'
        +'<div style="font-size:13px;opacity:.85;">'+E(d.message)+'</div>'
        +'</div></div>';
    }else{
        beep('error');h='<div style="background:rgba(107,31,58,0.95);backdrop-filter:blur(12px);border-radius:18px;padding:24px;color:white;border-left:5px solid #F09595;position:relative;overflow:hidden;animation:slideUp .3s ease-out;box-shadow:0 8px 32px rgba(107,31,58,0.3);"><div style="position:absolute;top:-50px;right:-50px;width:160px;height:160px;border-radius:50%;background:rgba(240,149,149,0.06);"></div><div style="position:relative;text-align:center;"><div style="font-size:22px;font-weight:800;margin-bottom:6px;">✕ Ditolak</div><div style="font-size:13px;opacity:.85;">'+E(d.message)+'</div></div></div>';
    }
    notif.innerHTML=h;ntimer=setTimeout(function(){notif.innerHTML=''},4000);
}

function updateTabel(lima){tbody.innerHTML=lima.map(function(r){return'<tr><td style="font-family:var(--font-mono);font-weight:600;color:#0960A8;">'+E(r.nip)+'</td><td style="font-weight:600;">'+E(r.nama)+'</td><td style="font-size:12px;color:#6b7280;">'+E(r.departemen||'-')+'</td><td style="font-size:12px;color:#6b7280;">'+E(r.section||'-')+'</td><td style="text-align:center;">'+(r.shift&&r.shift!=='-'?'<span class="badge badge-info">'+E(r.shift)+'</span>':'-')+'</td><td style="font-size:12px;color:#6b7280;">'+E(r.waktu)+'</td><td><span class="badge '+(r.metode==='rfid'?'badge-active':'badge-info')+'" style="font-size:10px;">'+r.metode.toUpperCase()+'</span></td></tr>'}).join('')}

function showKI(d){document.getElementById('ki-nama').textContent=d.peserta.nama;document.getElementById('ki-nip').textContent=d.peserta.nip;document.getElementById('ki-sesi').value=d.id_sesi;document.getElementById('ki-nipv').value=d.peserta.nip;document.getElementById('ki-met').value=d.metode;document.getElementById('modal-ki').style.display='flex'}
function tutupKI(){document.getElementById('modal-ki').style.display='none';if(sesiAktif)input.focus()}
function submitKI(){var fd=new FormData();fd.append('id_sesi',document.getElementById('ki-sesi').value);fd.append('nip',document.getElementById('ki-nipv').value);fd.append('metode',document.getElementById('ki-met').value);fd.append('<?=csrf_token()?>','<?=csrf_hash()?>');var f=snap();if(f)fd.append('foto',f);tutupKI();fetch('/absensi/konfirmasi-induk',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){showNotif(d)}).catch(function(){
        offlineQueue.push({nip:val,id_sesi:idSesi,metode:metode,waktu:new Date().toTimeString().substr(0,8)});saveQueue();
        showNotif({status:'success',nama:'Disimpan offline ('+offlineQueue.length+' antrian)',nip:val,waktu:new Date().toTimeString().substr(0,8),metode:metode,departemen:'-',section:'-',shift:'-'})
    }).finally(function(){if(sesiAktif)input.focus()})}
var batalTimer=null,batalSisa=10;
function startBatalCountdown(){
    batalSisa=10;
    if(batalTimer)clearInterval(batalTimer);
    batalTimer=setInterval(function(){
        batalSisa--;
        var el=document.getElementById('batal-countdown');
        if(el)el.textContent=batalSisa;
        if(batalSisa<=0){clearInterval(batalTimer);batalTimer=null;
            var btn=document.getElementById('btn-batal');
            if(btn)btn.style.display='none';
        }
    },1000);
}
var modalCountdownInterval=null;
function konfirmasiBatal(id){
    if(batalSisa<=0)return;
    showModal({title:'Batalkan Absensi?',message:'<span id="modal-countdown-msg">Sisa waktu: '+batalSisa+' detik</span>',confirmText:'Ya, Batalkan ('+batalSisa+'s)',type:'danger',onConfirm:function(){doBatal(id)}});
    // Update countdown di dalam modal
    if(modalCountdownInterval)clearInterval(modalCountdownInterval);
    modalCountdownInterval=setInterval(function(){
        var msgEl=document.getElementById('modal-countdown-msg');
        var btnEl=document.querySelector('#modal-overlay .btn-primary, #modal-overlay [style*="danger"], #modal-overlay button:last-child');
        if(msgEl)msgEl.textContent='Sisa waktu: '+batalSisa+' detik';
        if(btnEl&&btnEl.textContent.indexOf('Batalkan')>-1)btnEl.textContent='Ya, Batalkan ('+batalSisa+'s)';
        if(batalSisa<=0){clearInterval(modalCountdownInterval);
            document.getElementById('modal-overlay').style.display='none';
        }
    },1000);
}
function doBatal(id){
    if(modalCountdownInterval)clearInterval(modalCountdownInterval);
    var fd=new FormData();fd.append('id_absensi',id);fd.append('<?=csrf_token()?>','<?=csrf_hash()?>');
    fetch('/absensi/batalkan',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){
        if(d.status==='success'){
            kpSetNum(jumlahEl,Math.max(0,parseInt(jumlahEl.textContent)-1));
            if(batalTimer){clearInterval(batalTimer);batalTimer=null;}
            notif.innerHTML='<div style="background:rgba(9,96,168,0.95);backdrop-filter:blur(12px);border-radius:18px;padding:20px;color:white;border-left:5px solid #009EE0;text-align:center;animation:slideUp .3s ease-out;"><div style="font-size:18px;font-weight:700;">✓ '+E(d.message)+'</div></div>';
            setTimeout(function(){notif.innerHTML=''},3000);
            // Refresh tabel 5 terakhir
            refreshTabel();
        }else{showModal({title:'Gagal',message:d.message,confirmText:'OK',type:'warning',onConfirm:function(){}})}
    }).catch(function(){showModal({title:'Error',message:'Koneksi gagal',confirmText:'OK',type:'danger',onConfirm:function(){}})})
}
function refreshTabel(){
    fetch('/absensi/lima-terakhir?id_sesi='+idSesi).then(function(r){return r.json()}).then(function(d){
        if(d.lima)updateTabel(d.lima);
    }).catch(function(){});
}
// ═══ Offline Queue ═══
var offlineQueue=JSON.parse(localStorage.getItem('absensi_queue')||'[]');
function saveQueue(){localStorage.setItem('absensi_queue',JSON.stringify(offlineQueue))}
function syncQueue(){if(!offlineQueue.length)return;var item=offlineQueue[0];var fd=new FormData();fd.append('nip',item.nip);fd.append('id_sesi',item.id_sesi);fd.append('metode',item.metode);fd.append('<?=csrf_token()?>','<?=csrf_hash()?>');fetch('/absensi/proses',{method:'POST',body:fd}).then(function(r){return r.json()}).then(function(d){offlineQueue.shift();saveQueue();if(d.status==='success')kpSetNum(jumlahEl,parseInt(jumlahEl.textContent)+1);if(offlineQueue.length)setTimeout(syncQueue,500);}).catch(function(){});}
setInterval(function(){if(offlineQueue.length)syncQueue()},15000);

setInterval(function(){fetch('/dashboard/polling',{signal:AbortSignal.timeout(3000)}).then(function(){document.getElementById('dot-koneksi').style.background='#16a34a';document.getElementById('label-koneksi').textContent='OK'}).catch(function(){document.getElementById('dot-koneksi').style.background='#A8295A';document.getElementById('label-koneksi').textContent='Terputus!'})},10000);
document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('modal-ki');if(m)document.body.appendChild(m)});
document.addEventListener('keydown',function(e){if(e.key==='Escape')tutupKI()});
</script>
<style>@media(max-width:640px){#col-kamera{flex:0 0 120px !important;}#col-kamera video{min-height:100px !important;}}
.sesi-btn:hover{transform:translateY(-2px) scale(1.05);box-shadow:0 4px 12px rgba(0,0,0,0.15);}
@keyframes slideUp{0%{opacity:0;transform:translateY(8px)}100%{opacity:1;transform:translateY(0)}}</style>
<?= $this->endSection() ?>
