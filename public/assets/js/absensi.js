/* Halaman Absensi: input RFID/NIP, kamera, notifikasi, batalkan, antrean offline. Konfigurasi dari window.KP_PAGE. */
var CFG=window.KP_PAGE||{};
var idSesi=CFG.idSesi,sesiAktif=CFG.sesiAktif;
var input=document.getElementById('input-absensi'),notif=document.getElementById('area-notif');
var jumlahEl=document.getElementById('jumlah-hadir'),tbody=document.querySelector('#tabel-terbaru tbody');
var nHadir=CFG.hadir,TOTAL_PESERTA=CFG.totalPeserta;
var QUEUE_KEY='absensi_queue_'+CFG.userId;

// ── Angka hadir & persentase ─────────────────────────────────────
function setHadir(n,total){
    if(typeof total==='number')TOTAL_PESERTA=total;
    nHadir=Math.max(0,n);
    kpSetNum(jumlahEl,nHadir);
    var p=TOTAL_PESERTA>0?Math.min(100,Math.round(nHadir/TOTAL_PESERTA*100)):0;
    kpSetNum(document.getElementById('persen-hadir'),p,'%');
    document.getElementById('bar-hadir').style.width=p+'%';
}

// ── Suara (satu AudioContext dipakai ulang) ─────────────────────
var audioCtx=null;
function beep(t){try{
    audioCtx=audioCtx||new(window.AudioContext||window.webkitAudioContext)();
    var c=audioCtx,o=c.createOscillator(),g=c.createGain(),now=c.currentTime;
    o.connect(g);g.connect(c.destination);o.type='sine';g.gain.setValueAtTime(.3,now);
    if(t==='success'){o.frequency.setValueAtTime(880,now);o.frequency.setValueAtTime(1100,now+.1);g.gain.exponentialRampToValueAtTime(.001,now+.3);o.start(now);o.stop(now+.3)}
    else{o.frequency.setValueAtTime(300,now);g.gain.exponentialRampToValueAtTime(.001,now+.5);o.start(now);o.stop(now+.5)}
}catch(e){}}

function E(s){var d=document.createElement('div');d.textContent=s==null?'':String(s);return d.innerHTML}
function jam(){return new Date().toTimeString().substr(0,8)}
function fotoEl(p,glow){var g=glow||'127,255,180',box='width:100px;height:100px;min-width:100px;border-radius:20px;border:3px solid rgba('+g+',0.3);box-shadow:0 0 24px rgba('+g+',0.15),inset 0 0 12px rgba('+g+',0.05);';return p?'<img src="/'+E(p)+'" alt="" style="'+box+'object-fit:cover;">':'<div style="'+box+'background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M20 21a8 8 0 10-16 0"/></svg></div>'}

// ── Kamera ──────────────────────────────────────────────────────
var kameraOn=false,stream=null;
document.getElementById('toggle-kamera').addEventListener('change',function(){toggleKamera(this.checked)});
function toggleKamera(on){kameraOn=on;if(stream){stream.getTracks().forEach(function(t){t.stop()});stream=null}var tr=document.getElementById('toggle-track'),dt=document.getElementById('toggle-dot'),st=document.getElementById('camera-status'),col=document.getElementById('col-kamera');if(on){tr.style.background='#0960A8';dt.style.left='18px';st.textContent='Memulai...';st.style.color='#0960A8';if(!navigator.mediaDevices){camFail('Kamera hanya bisa di laptop server (localhost)');return}navigator.mediaDevices.getUserMedia({video:true}).then(function(s){stream=s;document.getElementById('cam-video').srcObject=s;col.style.display='block';st.textContent='Kamera aktif'}).catch(function(){camFail()})}else{tr.style.background='#e5e7eb';dt.style.left='2px';st.textContent='Kamera mati';st.style.color='#9ca3af';col.style.display='none';if(stream){stream.getTracks().forEach(function(t){t.stop()});stream=null}}}
function camFail(pesan){var st=document.getElementById('camera-status');st.textContent=pesan||'Tidak tersedia';st.style.color='#A8295A';document.getElementById('toggle-kamera').checked=false;kameraOn=false;document.getElementById('toggle-track').style.background='#e5e7eb';document.getElementById('toggle-dot').style.left='2px';document.getElementById('col-kamera').style.display='none'}
function snap(){if(!kameraOn||!stream)return null;var v=document.getElementById('cam-video'),c=document.getElementById('cam-canvas');if(!v.videoWidth)return null;var w=Math.min(640,v.videoWidth),h=Math.round(v.videoHeight*w/v.videoWidth);c.width=w;c.height=h;c.getContext('2d').drawImage(v,0,0,w,h);return c.toDataURL('image/jpeg',0.7)}

// ── Input ───────────────────────────────────────────────────────
function modalTerbuka(){return Array.prototype.some.call(document.querySelectorAll('.modal-overlay,#modal-ki'),function(m){return m.style.display==='flex'})}
if(sesiAktif)input.focus();
document.addEventListener('click',function(e){if(sesiAktif&&e.target!==input&&!e.target.closest('button,a,input,select,label')&&!modalTerbuka())setTimeout(function(){input.focus()},50)});
input.addEventListener('keydown',function(e){
    if(e.key!=='Enter')return;e.preventDefault();
    var v=this.value.trim();
    if(!v||!sesiAktif){this.value='';return}
    if(document.getElementById('modal-ki').style.display==='flex'){
        // Jangan hilangkan tap: minta operator selesaikan konfirmasi data induk dulu
        beep('error');tampil('gagal',{judul:'Selesaikan konfirmasi dulu',pesan:'Tutup atau setujui jendela konfirmasi data induk, lalu tap ulang.'});return;
    }
    this.value='';
    if(modalTerbuka())closeModal(); // dialog batalkan ditutup, tap berikutnya tetap diproses
    proses(v);
});

// Sesi dikunci/dibuka atau RAT diselesaikan saat halaman terbuka
function aturStatus(terbuka,pesan){
    if(terbuka===sesiAktif)return;
    sesiAktif=terbuka;input.disabled=!terbuka;input.style.background=terbuka?'white':'#f8fafc';
    document.getElementById('banner-tutup').style.display=terbuka?'none':'';
    document.getElementById('banner-tutup-teks').textContent=pesan||'';
    if(terbuka)input.focus();
}

function kirim(url,data){
    var fd=new FormData();Object.keys(data).forEach(function(k){if(data[k]!=null)fd.append(k,data[k])});
    return kpFetch(url,{method:'POST',body:fd});
}
function proses(val){
    var f=snap();
    kirim('/absensi/proses',{nip:val,id_sesi:idSesi,foto:f}).then(tanganiHasil).catch(function(err){gagalKirim(err,{nip:val,id_sesi:idSesi,waktu:jam()})}).finally(function(){if(sesiAktif&&!modalTerbuka())input.focus()});
}
function gagalKirim(err,item){
    if(err.jenis==='jaringan'&&item){
        offlineQueue.push(item);saveQueue();
        tampil('antre',{nip:item.nip,jumlah:offlineQueue.length});
    }else{
        tampil('gagal',{judul:err.jenis==='sesi'?'Sesi Login Habis':'Gagal Menyimpan',pesan:err.message,tetap:err.jenis==='sesi'});
    }
}
function tanganiHasil(d){
    if(d.status==='konfirmasi_induk'){showKI(d);return}
    if(d.status==='success'){
        if(typeof d.jumlah==='number')setHadir(d.jumlah,d.total);else setHadir(nHadir+1);
        if(d.lima)updateTabel(d.lima);
        tampil('berhasil',d);
    }else if(d.kode==='sudah_absen'){tampil('sudah',d.detail||{})}
    else if(d.kode==='kartu_belum_terdaftar'){tampil('info',{judul:'Kartu Belum Terdaftar',pesan:d.message})}
    else if(d.kode==='bukan_peserta'){tampil('gagal',{judul:'Tidak Terdaftar',pesan:d.message})}
    else{tampil('gagal',{judul:'Ditolak',pesan:d.message})}
}

// ── Kartu notifikasi ────────────────────────────────────────────
var ntimer=null;
var GAYA={
    berhasil:['10,94,63','127,255,180'], sudah:['146,101,10','255,217,125'], info:['9,96,168','0,158,224'],
    gagal:['107,31,58','240,149,149'], antre:['146,101,10','255,217,125']
};
function kartu(jenis,isi){var w=GAYA[jenis];return '<div style="background:rgba('+w[0]+',0.95);backdrop-filter:blur(12px);border-radius:18px;padding:22px 24px;color:white;border-left:5px solid rgb('+w[1]+');position:relative;overflow:hidden;animation:slideUp .3s ease-out;box-shadow:0 8px 32px rgba('+w[0]+',0.3);"><div style="position:absolute;top:-50px;right:-50px;width:160px;height:160px;border-radius:50%;background:rgba('+w[1]+',0.06);"></div><div style="position:relative;">'+isi+'</div></div>'}
function chip(t,kuat,g){return '<span style="background:rgba('+(kuat?g+',0.15':'255,255,255,0.08')+');padding:5px 14px;border-radius:20px;'+(kuat?'font-family:var(--font-mono);font-weight:700;':'')+'">'+E(t)+'</span>'}
function blokOrang(d,g,lencana,warnaLencana,status,ekor){
    return '<div class="notif-orang" style="display:flex;align-items:center;gap:20px;">'
      +'<div style="position:relative;flex-shrink:0;">'+fotoEl(d.foto_path,g)+'<div style="position:absolute;bottom:-5px;right:-5px;width:28px;height:28px;background:'+warnaLencana+';border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid rgba(0,0,0,0.25);font-size:14px;font-weight:700;">'+lencana+'</div></div>'
      +'<div style="flex:1;min-width:0;"><div class="notif-nama" style="font-size:28px;font-weight:800;line-height:1.1;margin-bottom:8px;word-break:break-word;">'+E(d.nama)+'</div>'
      +'<div style="display:flex;flex-wrap:wrap;gap:6px;font-size:12px;">'+chip(d.nip,true,g)+chip(d.departemen||'-')+chip(d.section||'-')+(d.shift&&d.shift!=='-'?chip('Shift '+d.shift,true,g):'')+'</div>'
      +'<div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-top:10px;"><div style="display:flex;align-items:center;gap:8px;"><span style="font-size:11px;opacity:0.6;">'+E(status)+'</span></div>'+(ekor||'')+'</div></div></div>';
}
function tampil(jenis,d){
    if(ntimer)clearTimeout(ntimer);
    if(batalTimer){clearInterval(batalTimer);batalTimer=null}
    var h='',lama=4000;
    if(jenis==='berhasil'){
        beep('success');
        var tombol=d.id_absensi?'<button type="button" id="btn-batal" onclick="konfirmasiBatal('+(+d.id_absensi)+')" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.3);color:white;padding:5px 12px;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;">✗ Batalkan (<span id="batal-countdown">'+(d.batas_batal||10)+'</span>s)</button>':'';
        h=kartu(jenis,blokOrang(d,'127,255,180','✓','#16a34a',d.waktu+' WIB · '+(d.metode==='rfid'?'RFID':'Manual')+' · BERHASIL',tombol));
        if(d.id_absensi){startBatalCountdown(d.batas_batal||10);lama=(d.batas_batal||10)*1000+500}
    }else if(jenis==='sudah'){
        beep('error');
        h=kartu(jenis,blokOrang(d,'255,217,125','!','#C9920F','Sudah absen pukul '+(d.waktu||'-')+' WIB · '+String(d.metode||'-').toUpperCase()));
        lama=5000;
    }else if(jenis==='antre'){
        beep('error');
        h=kartu(jenis,'<div style="text-align:center;"><div style="font-size:20px;font-weight:800;margin-bottom:6px;">Koneksi terputus — disimpan sementara</div><div style="font-size:13px;opacity:.9;">'+E(d.nip)+' masuk antrean ('+d.jumlah+'). Akan dikirim otomatis saat koneksi kembali. Belum dihitung hadir.</div></div>');
        lama=5000;
    }else{
        beep('error');
        h=kartu(jenis,'<div style="text-align:center;"><div style="font-size:22px;font-weight:800;margin-bottom:6px;">'+E(d.judul)+'</div><div style="font-size:13px;opacity:.9;">'+E(d.pesan)+'</div>'+(d.tetap?'<button type="button" onclick="location.reload()" style="margin-top:12px;background:white;color:#6b1f3a;border:none;padding:8px 16px;border-radius:8px;font-weight:700;cursor:pointer;font-family:inherit;">Muat ulang halaman</button>':'')+'</div>');
        if(d.tetap)lama=0;
    }
    notif.innerHTML=h;
    if(lama)ntimer=setTimeout(function(){notif.innerHTML=''},lama);
}

function updateTabel(lima){
    if(!lima.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:24px;color:#9ca3af;">Belum ada absensi.</td></tr>';return}
    tbody.innerHTML=lima.map(function(r){return'<tr><td style="font-family:var(--font-mono);font-weight:600;color:#0960A8;">'+E(r.nip)+'</td><td style="font-weight:600;">'+E(r.nama)+'</td><td style="font-size:12px;color:#6b7280;">'+E(r.departemen||'-')+'</td><td style="font-size:12px;color:#6b7280;">'+E(r.section||'-')+'</td><td style="text-align:center;">'+(r.shift&&r.shift!=='-'?'<span class="badge badge-info">'+E(r.shift)+'</span>':'-')+'</td><td style="font-size:12px;color:#6b7280;">'+E(r.waktu)+'</td><td><span class="badge '+(r.metode==='rfid'?'badge-active':'badge-info')+'" style="font-size:10px;">'+E(String(r.metode).toUpperCase())+'</span></td></tr>'}).join('')
}
function refreshData(){
    kpFetch('/absensi/lima-terakhir?id_sesi='+idSesi).then(function(d){
        if(d.lima)updateTabel(d.lima);
        if(typeof d.jumlah==='number'&&d.jumlah!==nHadir)setHadir(d.jumlah);
    }).catch(function(){});
}

// ── Konfirmasi data induk ──────────────────────────────────────
function showKI(d){document.getElementById('ki-nama').textContent=d.peserta.nama;document.getElementById('ki-nip').textContent=d.peserta.nip;document.getElementById('ki-sesi').value=d.id_sesi;document.getElementById('ki-nipv').value=d.peserta.nip;document.getElementById('ki-met').value=d.metode;document.getElementById('modal-ki').style.display='flex'}
function tutupKI(){document.getElementById('modal-ki').style.display='none';if(sesiAktif)input.focus()}
function submitKI(){
    var data={id_sesi:document.getElementById('ki-sesi').value,nip:document.getElementById('ki-nipv').value,metode:document.getElementById('ki-met').value,foto:snap()};
    tutupKI();
    kirim('/absensi/konfirmasi-induk',data).then(tanganiHasil).catch(function(err){gagalKirim(err,null)}).finally(function(){if(sesiAktif)input.focus()});
}

// ── Batalkan (maks. 10 detik) ──────────────────────────────────
var batalTimer=null,batalSisa=0,modalCountdownInterval=null;
function startBatalCountdown(detik){
    batalSisa=detik;
    batalTimer=setInterval(function(){
        batalSisa--;
        var el=document.getElementById('batal-countdown');if(el)el.textContent=batalSisa;
        if(batalSisa<=0){clearInterval(batalTimer);batalTimer=null;var b=document.getElementById('btn-batal');if(b)b.remove()}
    },1000);
}
function konfirmasiBatal(id){
    if(batalSisa<=0)return;
    showModal({title:'Batalkan absensi ini?',message:'Sisa waktu: '+batalSisa+' detik',confirmText:'Ya, Batalkan',type:'danger',onConfirm:function(){doBatal(id)}});
    if(modalCountdownInterval)clearInterval(modalCountdownInterval);
    modalCountdownInterval=setInterval(function(){
        var m=document.getElementById('modal-message');if(m)m.textContent='Sisa waktu: '+Math.max(0,batalSisa)+' detik';
        var ov=document.getElementById('modal-overlay');
        if(!ov||ov.style.display==='none'){clearInterval(modalCountdownInterval);return} // dialog sudah ditutup
        if(batalSisa<=0){clearInterval(modalCountdownInterval);closeModal()}
    },500);
}
function doBatal(id){
    if(modalCountdownInterval)clearInterval(modalCountdownInterval);
    kirim('/absensi/batalkan',{id_absensi:id}).then(function(d){
        if(d.status==='success'){
            if(typeof d.jumlah==='number')setHadir(d.jumlah);else setHadir(nHadir-1);
            tampil('info',{judul:'✓ Dibatalkan',pesan:d.message});
            refreshData();
        }else{tampil('gagal',{judul:'Tidak bisa dibatalkan',pesan:d.message})}
    }).catch(function(err){tampil('gagal',{judul:'Gagal membatalkan',pesan:err.message,tetap:err.jenis==='sesi'})});
}

// ── Antrean offline (per akun di perangkat ini) ─────────────────
var offlineQueue=[];
try{
    offlineQueue=JSON.parse(localStorage.getItem(QUEUE_KEY)||'[]');
    var antreLama=JSON.parse(localStorage.getItem('absensi_queue')||'[]'); // format versi sebelumnya
    if(antreLama.length){offlineQueue=offlineQueue.concat(antreLama);localStorage.removeItem('absensi_queue');localStorage.setItem(QUEUE_KEY,JSON.stringify(offlineQueue))}
}catch(e){}
function saveQueue(){try{localStorage.setItem(QUEUE_KEY,JSON.stringify(offlineQueue))}catch(e){}tandaKoneksi(true)}
var sedangSync=false,ditolakSync=[];
function syncQueue(){
    if(sedangSync||!offlineQueue.length)return;
    sedangSync=true;
    var item=offlineQueue[0];
    kirim('/absensi/proses',{nip:item.nip,id_sesi:item.id_sesi}).then(function(d){
        offlineQueue.shift();saveQueue();
        if(d.status==='success'){if(item.id_sesi===idSesi)refreshData()}
        else if(d.kode!=='sudah_absen'){ditolakSync.push(item.nip+' ('+item.waktu+'): '+d.message)}
        sedangSync=false;
        if(offlineQueue.length)setTimeout(syncQueue,400);
        else if(ditolakSync.length){tampil('gagal',{judul:'Sebagian antrean offline ditolak',pesan:ditolakSync.join(' • '),tetap:false});ditolakSync=[]}
    }).catch(function(err){
        sedangSync=false;
        if(err.jenis==='sesi')tampil('gagal',{judul:'Sesi Login Habis',pesan:offlineQueue.length+' absensi offline belum terkirim. Muat ulang halaman, login, lalu antrean dikirim otomatis.',tetap:true});
    });
}

// ── Indikator koneksi ──────────────────────────────────────────
function tandaKoneksi(ok,teks){
    document.getElementById('dot-koneksi').style.background=ok?(offlineQueue.length?'#C9920F':'#16a34a'):'#A8295A';
    document.getElementById('label-koneksi').textContent=ok?(offlineQueue.length?offlineQueue.length+' antrean':'OK'):(teks||'Terputus!');
}
setInterval(function(){
    kpFetch('/absensi/lima-terakhir?id_sesi='+idSesi).then(function(d){
        tandaKoneksi(true);
        if(d.lima)updateTabel(d.lima);
        if(typeof d.jumlah==='number'&&(d.jumlah!==nHadir||d.total!==TOTAL_PESERTA))setHadir(d.jumlah,d.total); // absen dari perangkat lain ikut terlihat
        if(typeof d.terbuka==='boolean')aturStatus(d.terbuka,d.pesan);
        if(offlineQueue.length)syncQueue();
    }).catch(function(err){tandaKoneksi(false,err.jenis==='sesi'?'Login habis':'Terputus!')});
},10000);
tandaKoneksi(true);
if(offlineQueue.length)setTimeout(syncQueue,1000);

document.addEventListener('DOMContentLoaded',function(){var m=document.getElementById('modal-ki');if(m)document.body.appendChild(m)});
document.addEventListener('keydown',function(e){if(e.key==='Escape')tutupKI()});
