/* Daftar RFID massal: tap kartu satu per satu untuk peserta/anggota yang belum punya kartu. */
    var CFG=window.KP_PAGE||{};
    var peserta=CFG.peserta||[];
    var idx=0,totalDone=CFG.totalDone||0,totalAll=CFG.totalAll||0,processing=false;
    var inputEl=document.getElementById('input-rfid');
    function E(s){var d=document.createElement('div');d.textContent=s==null?'':String(s);return d.innerHTML}
    var saveUrl=CFG.saveUrl;

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
        var fd=new FormData();fd.append('id',p.id);fd.append('uid_rfid',uid);
        kpFetch(saveUrl,{method:'POST',body:fd}).then(function(d){
            if(d.status==='success'){
                document.getElementById('status-msg').innerHTML='<span style="color:#16a34a;font-weight:700;font-size:16px;"><i class="ti ti-circle-check" style="font-size:18px;"></i> Tersimpan!</span>';
                document.getElementById('riwayat-empty').style.display='none';
                var rw=document.getElementById('riwayat');
                rw.innerHTML='<div style="padding:10px 16px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;animation:slideUp .2s ease-out;"><div><span style="font-weight:600;color:#022760;font-size:13px;">'+E(p.nama)+'</span><span style="font-family:var(--font-mono);color:#0960A8;margin-left:8px;font-size:12px;">'+E(p.nip)+'</span><span style="color:#6b7280;margin-left:8px;font-size:11px;">'+E(p.departemen||'-')+' · '+E(p.section||'-')+'</span></div><span style="color:#16a34a;font-size:11px;font-weight:600;background:#dcfce7;padding:2px 8px;border-radius:4px;">✓ '+E(uid.substring(0,12))+'</span></div>'+rw.innerHTML;
                totalDone++;
                document.getElementById('prog-done').textContent=totalDone;
                var pct=Math.round(totalDone/totalAll*100);
                document.getElementById('prog-bar').style.width=pct+'%';
                idx++;
                if(idx<peserta.length){setTimeout(function(){showPeserta(idx);processing=false;},800);}
                else{document.getElementById('card-current').innerHTML='<div style="text-align:center;padding:32px;"><div style="width:64px;height:64px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><i class="ti ti-circle-check" style="font-size:32px;color:#16a34a;"></i></div><p style="font-size:20px;font-weight:700;color:#022760;margin-bottom:4px;">Selesai!</p><p style="font-size:13px;color:#6b7280;">Semua peserta sudah didaftarkan.</p></div>';processing=false;}
            }else{
                document.getElementById('status-msg').innerHTML='<span style="color:#A8295A;font-weight:600;"><i class="ti ti-alert-circle" style="font-size:16px;"></i> '+E(d.message)+'</span>';
                processing=false;setTimeout(function(){inputEl.focus()},100);
            }
        }).catch(function(err){document.getElementById('status-msg').innerHTML='<span style="color:#A8295A;">'+E(err.message)+'</span>';processing=false;});
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

    // Lewati orang yang belum datang / belum punya kartu, lanjut ke berikutnya
    document.getElementById('btn-lewati').addEventListener('click',function(){
        if(processing||idx>=peserta.length)return;
        idx++;
        if(idx<peserta.length){showPeserta(idx);document.getElementById('status-msg').innerHTML='<span style="color:#6b7280;">Dilewati. Menunggu tap kartu berikutnya...</span>';}
        else{document.getElementById('card-current').innerHTML='<div style="text-align:center;padding:32px;"><p style="font-size:18px;font-weight:700;color:#022760;margin-bottom:4px;">Daftar selesai</p><p style="font-size:13px;color:#6b7280;">Peserta yang dilewati bisa didaftarkan nanti (muat ulang halaman ini).</p></div>';}
        setTimeout(function(){inputEl.focus()},50);
    });
