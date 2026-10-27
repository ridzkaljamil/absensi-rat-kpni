/* Halaman login: latar animasi dan login via AJAX. */
// ── CSS wave sync ─────────────────────────────────────────────────
(function() {
    const t = parseFloat(localStorage.getItem('kpni_bcd_t')||'0') / 1000;
    const durations = [18, 24, 30];
    document.querySelectorAll('.login-wave').forEach((el,i) => {
        el.style.animationDelay = `-${(t % durations[i]).toFixed(2)}s`;
    });
    document.querySelectorAll('.login-grid').forEach(el => {
        el.style.animationDelay = `-${(t % 12).toFixed(2)}s`;
    });
    document.querySelectorAll('.login-glow').forEach((el,i) => {
        el.style.animationDelay = `-${(t % [5,7,6][i]).toFixed(2)}s`;
    });
})();

// ── Dots canvas ──────────────────────────────────────────────────
(function() {
    const canvas = document.getElementById('login-dots');
    if (!canvas) return;
    const dpr = Math.min(window.devicePixelRatio||1, 2);
    function resize() {
        canvas.width  = window.innerWidth  * dpr;
        canvas.height = window.innerHeight * dpr;
        canvas.style.width  = window.innerWidth  + 'px';
        canvas.style.height = window.innerHeight + 'px';
    }
    resize();
    window.addEventListener('resize', resize);
    const ctx = canvas.getContext('2d');
    const KEY = 'kpni_bcd_t';
    let t = parseFloat(localStorage.getItem(KEY)||'0');
    let last = performance.now(), save = 0;

    const dots = Array.from({length:10},(_,i)=>({
        rx:(i*0.137+0.08)%0.92, ry:(i*0.211+0.12)%0.88,
        r:2+(i%3), spd:3000+(i%5)*500, ph:(i*0.7)%(Math.PI*2)
    }));
    const lines = [[0,2],[2,5],[3,7],[1,4],[6,9]];

    // Ripple canvas
    const rc  = document.getElementById('ripple-canvas');
    const rct = rc ? rc.getContext('2d') : null;
    const DUR = 2500;
    if(rct) {
        const dpr2 = Math.min(window.devicePixelRatio||1,2);
        rc.width  = 220*dpr2; rc.height = 220*dpr2;
        rc.style.width='220px'; rc.style.height='220px';
        rct.scale(dpr2,dpr2);
    }

    function tick(now) {
        const dt = Math.min(now-last,50); last=now; t+=dt;
        const W=window.innerWidth, H=window.innerHeight;

        ctx.clearRect(0,0,canvas.width,canvas.height);
        ctx.save(); ctx.scale(dpr,dpr);
        lines.forEach(([a,b])=>{
            const da=dots[a],db=dots[b];
            const op=((Math.sin((t/4000)*Math.PI*2+(da.ph+db.ph)/2)+1)/2)*0.2;
            ctx.strokeStyle=`rgba(0,158,224,${op.toFixed(3)})`;
            ctx.lineWidth=1;
            ctx.beginPath(); ctx.moveTo(da.rx*W,da.ry*H); ctx.lineTo(db.rx*W,db.ry*H); ctx.stroke();
        });
        dots.forEach(d=>{
            const p=Math.sin((t/d.spd)*Math.PI*2+d.ph);
            const r=d.r*(1+p*0.8), op=0.2+0.3*((p+1)/2);
            ctx.fillStyle=`rgba(0,158,224,${op.toFixed(3)})`;
            ctx.beginPath(); ctx.arc(d.rx*W,d.ry*H,r,0,Math.PI*2); ctx.fill();
        });
        ctx.restore();

        // Ripple
        if(rct) {
            rct.clearRect(0,0,220,220);
            [0,DUR/3,DUR/3*2].forEach(phase=>{
                const p=((t+phase)%DUR)/DUR;
                const r=65+(105-65)*p;
                const fin=p<0.2?(p/0.2)**2:1, fout=p>0.6?Math.max(0,1-(p-0.6)/0.4):1;
                const op=fin*fout*0.65;
                rct.beginPath(); rct.arc(110,110,r,0,Math.PI*2);
                rct.strokeStyle=`rgba(9,96,168,${op.toFixed(3)})`; rct.lineWidth=1.5; rct.stroke();
            });
        }

        save+=dt;
        if(save>500){localStorage.setItem(KEY,t.toString());save=0;}
        requestAnimationFrame(tick);
    }
    window.addEventListener('beforeunload',()=>localStorage.setItem(KEY,t.toString()));
    requestAnimationFrame(tick);
})();

// ── Login Form ───────────────────────────────────────────────────
(function() {
    const form   = document.getElementById('login-form');
    const btn    = document.getElementById('login-btn');
    const card   = document.getElementById('login-card');
    const errBox = document.getElementById('login-error');

    const s = document.createElement('style');
    s.textContent = '@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}';
    document.head.appendChild(s);

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader-2" style="animation:spin 0.8s linear infinite;margin-right:6px;font-size:16px;"></i>Masuk...';
        const fd = new FormData(form);
        try {
            const res  = await fetch('/login', {method:'POST',body:fd,headers:{'X-Requested-With':'kpni-ajax'}});
            if (!(res.headers.get('content-type') || '').includes('application/json')) {
                // Token keamanan halaman ini kedaluwarsa (halaman login dibiarkan terbuka lama)
                if(errBox){pesanError('Halaman sudah terlalu lama terbuka. Memuat ulang, silakan login lagi.');}
                setTimeout(()=>location.reload(), 1500);
                return;
            }
            const data = await res.json();
            if (!data.ok) {
                if(errBox){pesanError(data.msg);}
                btn.disabled=false;
                btn.innerHTML='<i class="ti ti-login" style="margin-right:6px;"></i>Masuk ke Sistem';
                return;
            }
            localStorage.setItem('kpni_last_login', Date.now().toString());
            sessionStorage.removeItem('kpni_splash_done');
            if(card){card.style.transition='transform 0.4s cubic-bezier(0.4,0,1,1),opacity 0.35s ease';card.style.transform='scale(0.88) translateY(-16px)';card.style.opacity='0';}
            setTimeout(()=>{ window.location.href = data.redirect||'/dashboard'; }, 420);
        } catch(err) {
            btn.disabled=false;
            btn.innerHTML='<i class="ti ti-login" style="margin-right:6px;"></i>Masuk ke Sistem';
            if(errBox){pesanError('Koneksi bermasalah.');}
        }
    });
})();

// Pesan error tanpa menghapus ikon di dalam kotak
function pesanError(teks){var box=document.getElementById('login-error');if(!box)return;var sp=box.querySelector('span');if(!sp){sp=document.createElement('span');box.appendChild(sp);}sp.textContent=teks;box.style.display='flex';}
// Tampilkan/sembunyikan password dengan klik (juga di layar sentuh)
(function(){var b=document.getElementById('lihat-pwd-login'),i=document.getElementById('pwd-login');if(!b||!i)return;b.addEventListener('click',function(){i.type=i.type==='password'?'text':'password';b.style.color=i.type==='text'?'#0960A8':'';});})();
