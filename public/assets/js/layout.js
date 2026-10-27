/* Sistem Absensi RAT KPNI — latar animasi, splash, toast, modal, menu HP. Dimuat di akhir <body> layout. */
// ── BCD Dots — canvas kecil untuk dots+lines saja ────────────────
(function() {
    const canvas = document.getElementById('bcd-dots');
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
    // Waktu animasi = sejak titik awal yang sama dengan CSS (window.KPNI_BG_EPOCH),
    // jadi titik & garis melanjutkan posisinya saat pindah halaman.
    const EPOCH = window.KPNI_BG_EPOCH || Date.now();
    let t = Date.now() - EPOCH;

    const dots = Array.from({length:12},(_,i)=>({
        rx:(i*0.137+0.08)%0.92, ry:(i*0.211+0.12)%0.88,
        r:2+(i%3), spd:3000+(i%5)*500, ph:(i*0.7)%(Math.PI*2)
    }));
    const lines = [[0,2],[2,5],[5,8],[3,7],[1,4],[6,9]];

    // Splash elements
    const sw1=document.getElementById('sw1'), sw2=document.getElementById('sw2');
    const sgrid=document.getElementById('sgrid');
    const sgl=[document.getElementById('sgl1'),document.getElementById('sgl2'),document.getElementById('sgl3')];
    const sglCfg=[{spd:5000,dx:30,dy:-20},{spd:7000,dx:-20,dy:25},{spd:6000,dx:25,dy:10}];

    // Splash ripple canvas
    const splashEl = document.getElementById('splash');
    // Pengguna yang mematikan animasi di OS: gambar latar sekali saja (statis)
    const diam = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const src = document.getElementById('splash-ripple-canvas');
    const sctx = src ? src.getContext('2d') : null;

    function tick(now) {
        t = Date.now() - EPOCH;
        const W=window.innerWidth, H=window.innerHeight;

        // Dots canvas
        ctx.clearRect(0,0,canvas.width,canvas.height);
        ctx.save(); ctx.scale(dpr,dpr);

        // Lines
        lines.forEach(([a,b])=>{
            const da=dots[a],db=dots[b];
            const op=((Math.sin((t/4000)*Math.PI*2+(da.ph+db.ph)/2)+1)/2)*0.12;
            ctx.strokeStyle=`rgba(9,96,168,${op.toFixed(3)})`;
            ctx.lineWidth=1;
            ctx.beginPath(); ctx.moveTo(da.rx*W,da.ry*H); ctx.lineTo(db.rx*W,db.ry*H); ctx.stroke();
        });
        // Dots
        dots.forEach(d=>{
            const p=Math.sin((t/d.spd)*Math.PI*2+d.ph);
            const r=d.r*(1+p*0.8), op=0.15+0.3*((p+1)/2);
            ctx.fillStyle=`rgba(9,96,168,${op.toFixed(3)})`;
            ctx.beginPath(); ctx.arc(d.rx*W,d.ry*H,r,0,Math.PI*2); ctx.fill();
        });
        ctx.restore();

        // Elemen splash hanya dianimasikan selama splash tampil
        const splashTampil = splashEl && splashEl.style.display !== 'none';
        if (!splashTampil) { if (!diam) requestAnimationFrame(tick); return; }
        if(sw1) sw1.style.transform=`rotate(${(t/18000*360)%360}deg)`;
        if(sw2) sw2.style.transform=`rotate(${-(t/24000*360)%360}deg)`;
        if(sgrid){const p=(t/300)%40; sgrid.style.backgroundPosition=`${p}px ${p}px`;}
        sgl.forEach((el,i)=>{
            if(!el)return;
            const cfg=sglCfg[i], p=(t%cfg.spd)/cfg.spd;
            const sc=1+0.35*Math.sin(p*Math.PI*2);
            el.style.transform=`scale(${sc}) translate(${cfg.dx*Math.sin(p*Math.PI*2)}px,${cfg.dy*Math.cos(p*Math.PI*2)}px)`;
        });
        // Splash ripple
        if(sctx){
            const sz=280, DUR=2500;
            sctx.clearRect(0,0,sz,sz);
            [0,DUR/3,DUR/3*2].forEach(phase=>{
                const p=((t+phase)%DUR)/DUR;
                const r=75+(138-75)*p;
                const fin=p<0.2?(p/0.2)**2:1, fout=p>0.6?Math.max(0,1-(p-0.6)/0.4):1;
                const op=fin*fout*0.8;
                sctx.beginPath(); sctx.arc(sz/2,sz/2,r,0,Math.PI*2);
                sctx.strokeStyle=`rgba(0,158,224,${op.toFixed(3)})`; sctx.lineWidth=1.5; sctx.stroke();
            });
        }

        if (!diam) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
})();

// ── Splash ───────────────────────────────────────────────────────
(function() {
    const splash = document.getElementById('splash');
    const bar    = document.getElementById('splash-bar');
    const pct    = document.getElementById('splash-pct');
    const status = document.getElementById('splash-status');
    if (!splash) return;

    const phases = [
        {at:20,msg:'Menghubungkan ke server...'},
        {at:45,msg:'Memverifikasi sesi...'},
        {at:70,msg:'Memuat data...'},
        {at:90,msg:'Menyiapkan dashboard...'},
        {at:100,msg:'Siap!'},
    ];

    const lastLogin  = localStorage.getItem('kpni_last_login');
    const isAfterLogin = lastLogin && (Date.now()-parseInt(lastLogin))<15000;
    const isFirstLoad  = !sessionStorage.getItem('kpni_splash_done');

    if (!isFirstLoad && !isAfterLogin) {
        splash.style.display = 'none';
        document.body.classList.remove('splash-active');
        const bcd = document.getElementById('bcd-bg');
        if (bcd) { bcd.style.transition='none'; bcd.style.opacity='1'; }
        return;
    }

    splash.style.display = 'flex';
    splash.style.opacity = '1';
    const sc = document.getElementById('splash-content');
    if(sc) requestAnimationFrame(()=>requestAnimationFrame(()=>{
        sc.style.opacity='1'; sc.style.transform='translateY(0)';
    }));

    let w=0, phIdx=0, preloaded=false;
    setTimeout(()=>{
        const iv = setInterval(()=>{
            const spd = w<60?2.5:w<85?1.5:w<95?0.6:0.25;
            w = Math.min(w+spd,100);
            bar.style.width=w+'%';
            if(pct) pct.textContent=Math.floor(w)+'%';
            if(w>=70&&!preloaded){preloaded=true;const l=document.createElement('link');l.rel='prefetch';l.href='/dashboard';document.head.appendChild(l);}
            while(phIdx<phases.length&&w>=phases[phIdx].at){
                if(status){const m=phases[phIdx].msg;status.style.opacity='0';setTimeout(()=>{if(status){status.textContent=m;status.style.opacity='1';}},150);}
                phIdx++;
            }
            if(w>=100){
                clearInterval(iv);
                const bcd=document.getElementById('bcd-bg');
                if(bcd){bcd.style.opacity='1';}
                setTimeout(()=>{
                    splash.style.transition='opacity 0.7s ease,transform 0.7s ease';
                    splash.style.opacity='0'; splash.style.transform='scale(1.04)';
                    setTimeout(()=>{
                        document.body.style.background='transparent';
                        document.body.classList.remove('splash-active');
                        localStorage.removeItem('kpni_last_login');
                        sessionStorage.setItem('kpni_splash_done','1');
                    },400);
                    setTimeout(()=>{splash.style.display='none';splash.style.transform='';},750);
                },300);
            }
        },40);
    },0);
})();

// ── Toast ────────────────────────────────────────────────────────
['toast-ok','toast-err'].forEach(id=>{
    const el=document.getElementById(id);
    if(el) setTimeout(()=>{el.style.opacity='0';el.style.transform='translateX(40px)';setTimeout(()=>el.remove(),450);},4000);
});

// ── Modal: animasi saat ditutup ─────────────────────────────────
// Semua modal ditutup dengan style.display='none'. Observer ini menahan sebentar
// supaya modal memudar dulu, tanpa perlu mengubah kode di setiap halaman.
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const amati = new MutationObserver(list => list.forEach(m => {
        const el = m.target;
        if (el._kpTutup) return;
        if (el.style.display === 'none') {
            if (!el._kpBuka) return;
            el._kpTutup = true; el._kpBuka = false;
            el.style.display = 'flex';
            el.classList.add('is-closing');
            setTimeout(() => { el.classList.remove('is-closing'); el.style.display = 'none'; el._kpTutup = false; }, 180);
        } else {
            el._kpBuka = true;
        }
    }));
    function pasang() {
        document.querySelectorAll('.modal-overlay').forEach(el => {
            if (el._kpDiamati) return;
            el._kpDiamati = true;
            el._kpBuka = el.style.display !== 'none';
            amati.observe(el, { attributes: true, attributeFilter: ['style'] });
        });
    }
    pasang();
    document.addEventListener('DOMContentLoaded', pasang);
})();

// ── Modal ────────────────────────────────────────────────────────
let _modalCb=null;
// message berupa teks biasa (aman untuk nama dari data import); html:true hanya untuk markup buatan kode sendiri
function showModal({title,message,confirmText='Hapus',type='danger',onConfirm,html=false}){
    document.getElementById('modal-title').textContent=title;
    document.getElementById('modal-message')[html?'innerHTML':'textContent']=message;
    const btn=document.getElementById('modal-btn'),icon=document.getElementById('modal-icon');
    btn.textContent=confirmText;
    btn.className='btn '+(type==='danger'?'btn-danger':type==='warning'?'btn-indigo':'btn-primary');
    icon.textContent=type==='danger'?'⚠️':type==='warning'?'⚠️':'ℹ️';
    icon.style.background=type==='danger'?'#fee2e2':type==='warning'?'#e0e7ff':'#dbeafe';
    _modalCb=onConfirm;
    document.getElementById('modal-overlay').style.display='flex';
}
function closeModal(){document.getElementById('modal-overlay').style.display='none';_modalCb=null;}
document.getElementById('modal-btn').addEventListener('click',()=>{if(typeof _modalCb==='function')_modalCb();closeModal();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});

// ── Mobile menu ──────────────────────────────────────────────────
function toggleMobileMenu(){
    const m=document.getElementById('mobile-menu');
    m.style.display=m.style.display==='none'||m.style.display===''?'grid':'none';
}

function showPwd(id,show){const el=document.getElementById(id);if(el)el.type=show?'text':'password';}

// Tombol mata (.lihat-pwd, data-target=id input): tampilkan/sembunyikan password dengan klik (juga di layar sentuh)
document.addEventListener('click', function (e) {
    var b = e.target.closest('.lihat-pwd');
    if (!b) return;
    var el = document.getElementById(b.dataset.target);
    if (!el) return;
    el.type = el.type === 'password' ? 'text' : 'password';
    b.style.color = el.type === 'text' ? '#0960A8' : '#6b7280';
});
