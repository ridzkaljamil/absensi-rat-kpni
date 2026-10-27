/* Doorprize: filter eligible, tab, undian (animasi slot, confetti, layar penuh), pembaruan tanpa reload. */
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
        h += '<a href="#" onclick="event.preventDefault();eligPaginate(getVisibleElig(),1)" style="' + sN + '"' + ho + '>«</a>';
        h += '<a href="#" onclick="event.preventDefault();eligPaginate(getVisibleElig(),' + (page-1) + ')" style="' + sN + '"' + ho + '>‹ Sebelumnya</a>';
    } else {
        h += '<span style="' + sD + '">«</span><span style="' + sD + '">‹ Sebelumnya</span>';
    }
    // Page numbers (surround 2)
    var startP = Math.max(1, page - 2), endP = Math.min(pages, page + 2);
    for (var i = startP; i <= endP; i++) {
        if (i === page) {
            h += '<span style="' + sA + '">' + i + '</span>';
        } else {
            h += '<a href="#" onclick="event.preventDefault();eligPaginate(getVisibleElig(),' + i + ')" style="' + sN + '"' + ho + '>' + i + '</a>';
        }
    }
    // Next + Last
    if (page < pages) {
        h += '<a href="#" onclick="event.preventDefault();eligPaginate(getVisibleElig(),' + (page+1) + ')" style="' + sN + '"' + ho + '>Berikutnya ›</a>';
        h += '<a href="#" onclick="event.preventDefault();eligPaginate(getVisibleElig(),' + pages + ')" style="' + sN + '"' + ho + '>»</a>';
    } else {
        h += '<span style="' + sD + '">Berikutnya ›</span><span style="' + sD + '">»</span>';
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
var audioCtx=null;
function tadaSound(){
    try{var ctx=audioCtx=audioCtx||new(window.AudioContext||window.webkitAudioContext)();
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

    kpFetch('/doorprize/pick', { method: 'POST', body: fd })
        .then(function (data) {
            if (data.status !== 'success') { gagalUndi(data.message || 'Undian gagal.'); return; }
            animasiSlot(data);
        })
        .catch(function (err) {
            // Jaringan putus setelah server menyimpan? Cek riwayat dulu sebelum mengundi lagi.
            gagalUndi(err.jenis === 'jaringan' ? 'Koneksi terputus. Muat ulang halaman dan cek Riwayat Pemenang sebelum mengundi lagi.' : err.message);
        });
}

function gagalUndi(pesan) {
    isSpinning = false;
    setLabel(pesan, true);
    document.getElementById('btn-undi').disabled = false;
    document.getElementById('btn-undi-text').textContent = 'Coba Lagi';
}

function escHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function animasiSlot(data) {
    var pool = data.pool || [], winner = data.winner, barang = data.barang, sisa = data.sisa;
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
            'NIP ' + winner.nip + (winner.departemen && winner.departemen !== '-' ? ' · ' + winner.departemen : '') + (winner.section && winner.section !== '-' ? ' · ' + winner.section : '');
        document.getElementById('winner-display').style.display = '';
        setLabel(sisa > 0 ? 'Sisa ' + sisa + ' unit ' + barang + ' lagi' : 'Semua unit ' + barang + ' sudah diundi');
        tadaSound();
        launchConfetti();
        perbaruiTampilan(data);
        isSpinning = false;
    })();
}

// Perbarui angka, daftar hadiah, riwayat, dan tab eligible tanpa memuat ulang halaman
// (layar penuh di proyektor tetap terbuka). d = ringkasan dari server.
function perbaruiRingkasan(d) {
    kpSetNum(document.getElementById('pstat-eligible'), d.eligible_sisa);
    kpSetNum(document.getElementById('pstat-menang'), d.total_menang);
    kpSetNum(document.getElementById('pstat-unit'), d.unit_sisa);
    document.getElementById('sub-eligible').textContent = d.eligible_sisa;
    var tabEl = document.getElementById('elig-jumlah');
    if (tabEl) tabEl.textContent = d.eligible_sisa + ' eligible';
    renderDaftarHadiah(d.items || []);

    var btn = document.getElementById('btn-undi');
    btn.disabled = !hadiahDipilih || d.eligible_sisa <= 0;
    document.getElementById('btn-undi-text').textContent = !hadiahDipilih ? 'Pilih hadiah dulu' : (d.eligible_sisa <= 0 ? 'Tidak ada peserta eligible' : 'Undi Lagi');
}

function renderDaftarHadiah(items) {
    var wadah = document.getElementById('prize-list-wrap');
    document.getElementById('jumlah-barang-sisa').textContent = items.length + ' barang';
    if (!items.length) {
        wadah.innerHTML = '<div class="prize-empty"><i class="ti ti-gift-off"></i><p>Semua hadiah sudah diundi atau belum ada barang.</p><button type="button" onclick="switchTab(\'barang\')" class="btn btn-ghost btn-sm">Ke tab Barang</button></div>';
    } else {
        wadah.innerHTML = '<div class="prize-list">' + items.map(function (it) {
            var kat = it.kat || 'utama';
            return '<button type="button" class="prize-item" data-id="' + it.id + '" data-nama="' + escHtml(it.nama) + '" data-img="' + escHtml(it.img || '') + '" data-kat="' + escHtml(kat) + '" data-sisa="' + it.sisa + '" data-jumlah="' + it.jumlah + '" onclick="pilihHadiah(this)">'
                + '<span class="prize-thumb">' + (it.img ? '<img src="/' + escHtml(it.img) + '" alt="" loading="lazy" onerror="this.remove()">' : '') + '<i class="ti ti-gift"></i></span>'
                + '<span class="prize-info"><span class="prize-name">' + escHtml(it.nama) + '</span><span class="prize-sub"><span class="badge ' + (kat === 'utama' ? 'badge-active' : 'badge-info') + '" style="padding:1px 8px;font-size:10px;">' + escHtml(kat.charAt(0).toUpperCase() + kat.slice(1)) + '</span> <span class="prize-sisa">sisa ' + it.sisa + ' dari ' + it.jumlah + '</span></span></span>'
                + '<i class="ti ti-chevron-right prize-arrow"></i></button>';
        }).join('') + '</div>';
    }
    // Pertahankan pilihan hadiah kalau masih ada sisa, kalau habis kosongkan panggung
    if (hadiahDipilih) {
        var masih = items.filter(function (it) { return String(it.id) === String(hadiahDipilih.id); })[0];
        if (masih) {
            hadiahDipilih.sisa = masih.sisa;
            var el = document.querySelector('.prize-item[data-id="' + masih.id + '"]');
            if (el) el.classList.add('is-active');
            document.getElementById('stage-prize-meta').textContent = 'Kategori ' + hadiahDipilih.kat.charAt(0).toUpperCase() + hadiahDipilih.kat.slice(1) + ' · sisa ' + masih.sisa + ' dari ' + hadiahDipilih.jumlah + ' unit';
        } else {
            hadiahDipilih = null;
            document.getElementById('stage-prize-meta').textContent = 'Unit hadiah ini sudah habis. Pilih hadiah berikutnya.';
        }
    }
}

function tombolAksi(id, nama, hangus) {
    var n = escHtml(nama);
    return (hangus ? '' : '<button type="button" class="btn btn-indigo btn-sm aksi-pemenang" data-aksi="hangus" data-id="' + id + '" data-nama="' + n + '" style="height:26px;font-size:11px;padding:0 8px;" title="Tidak hadir: unit diundi ulang, orang ini tidak ikut lagi">Hangus</button> ')
        + '<button type="button" class="btn btn-danger btn-sm aksi-pemenang" data-aksi="hapus" data-id="' + id + '" data-nama="' + n + '" style="height:26px;font-size:11px;padding:0 8px;" title="Salah undi: hapus pemenang, orang ini bisa diundi lagi"><i class="ti ti-x" style="font-size:12px;"></i></button>';
}

function perbaruiTampilan(d) {
    perbaruiRingkasan(d);
    var kosong = document.getElementById('pemenang-kosong');
    if (kosong) kosong.remove();
    var tb = document.getElementById('tbody-pemenang'), w = d.winner;
    var tr = document.createElement('tr');
    tr.dataset.id = w.id;
    tr.innerHTML = '<td style="color:#9ca3af;">baru</td><td style="font-weight:600;color:#022760;">' + escHtml(d.barang) + '</td>'
        + '<td><span class="badge ' + (d.kategori === 'utama' ? 'badge-active' : 'badge-info') + '">' + escHtml(d.kategori.charAt(0).toUpperCase() + d.kategori.slice(1)) + '</span></td>'
        + '<td style="font-family:var(--font-mono);color:#0960A8;">' + escHtml(w.nip) + '</td><td class="nama-pemenang" style="font-weight:500;">' + escHtml(w.nama) + '</td>'
        + '<td style="font-size:12px;color:#6b7280;">' + escHtml(w.departemen) + '</td><td style="font-size:12px;color:#6b7280;">' + escHtml(w.section) + '</td>'
        + '<td style="font-size:12px;color:#6b7280;">' + escHtml(d.waktu) + '</td>'
        + '<td class="sel-aksi" style="text-align:center;white-space:nowrap;">' + tombolAksi(w.id, w.nama, false) + '</td>';
    tb.insertBefore(tr, tb.firstChild);
    document.getElementById('jumlah-riwayat').textContent = tb.querySelectorAll('tr[data-id]').length;
    tandaiEligible(w.nip, 'menang');
}

function tandaiEligible(nip, status) {
    var row = document.querySelector('.elig-row[data-nip="' + CSS.escape(nip) + '"]');
    if (!row) return;
    row.style.opacity = status ? '0.5' : '';
    var st = row.querySelector('.elig-status');
    if (st) st.innerHTML = status === 'menang' ? '<span class="badge badge-warning">🏆 Menang</span>' : (status === 'hangus' ? '<span class="badge badge-locked">Hangus</span>' : '<span class="badge badge-success">Eligible</span>');
}

// Hangus / hapus pemenang tanpa reload (berlaku untuk baris lama maupun baru)
document.addEventListener('click', function (e) {
    var b = e.target.closest('.aksi-pemenang');
    if (!b) return;
    var hangus = b.dataset.aksi === 'hangus', nama = b.dataset.nama;
    showModal({
        title: hangus ? 'Tandai hangus?' : 'Hapus pemenang?',
        message: hangus ? nama + ' tidak hadir. Unit hadiahnya bisa diundi ulang dan orang ini tidak ikut undian lagi.'
                        : nama + ' dihapus dari daftar pemenang dan bisa diundi lagi. Pakai ini hanya kalau undian salah.',
        confirmText: hangus ? 'Hangus' : 'Hapus', type: hangus ? 'warning' : 'danger',
        onConfirm: function () {
            kpFetch('/doorprize/winner/' + b.dataset.id + '/' + b.dataset.aksi, { method: 'POST', body: new FormData() }).then(function (d) {
                if (d.status !== 'success') { setLabel(d.message, true); return; }
                var tr = b.closest('tr');
                if (d.aksi === 'hangus') {
                    tr.style.opacity = '.55';
                    var nm = tr.querySelector('.nama-pemenang') || tr.children[4];
                    nm.insertAdjacentHTML('beforeend', ' <span class="badge badge-locked" style="font-size:10px;">Hangus</span>');
                    tr.querySelector('.sel-aksi').innerHTML = tombolAksi(d.id, nama, true);
                    tandaiEligible(d.nip, 'hangus');
                } else {
                    tr.remove();
                    tandaiEligible(d.nip, null);
                    document.getElementById('jumlah-riwayat').textContent = document.querySelectorAll('#tbody-pemenang tr[data-id]').length;
                }
                setLabel(d.message);
                perbaruiRingkasan(d);
            }).catch(function (err) { setLabel(err.message, true); });
        }
    });
});

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
    var wadah=document.fullscreenElement||document.body; // tetap terlihat saat layar penuh
    wadah.appendChild(canvas);
    var ctx=canvas.getContext('2d');
    canvas.width=window.innerWidth;canvas.height=window.innerHeight;
    var pieces=[],colors=['#7FFFB4','#FFD97D','#009EE0','#F09595','#AFA9EC','#5DCAA5','#F0997B'];
    for(var i=0;i<120;i++)pieces.push({x:canvas.width/2,y:canvas.height/2,vx:(Math.random()-0.5)*16,vy:Math.random()*-14-4,w:Math.random()*8+4,h:Math.random()*6+3,c:colors[i%colors.length],r:Math.random()*6.28,rv:(Math.random()-0.5)*0.3,g:0.25});
    function draw(){ctx.clearRect(0,0,canvas.width,canvas.height);var alive=false;
    pieces.forEach(function(p){p.x+=p.vx;p.vy+=p.g;p.y+=p.vy;p.r+=p.rv;p.vx*=0.99;
    if(p.y<canvas.height+20){alive=true;ctx.save();ctx.translate(p.x,p.y);ctx.rotate(p.r);
    ctx.fillStyle=p.c;ctx.fillRect(-p.w/2,-p.h/2,p.w,p.h);ctx.restore();}});
    if(alive)requestAnimationFrame(draw);else canvas.remove();}
    requestAnimationFrame(draw);
}

// ESC tutup modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') ['modal-tambah-barang', 'modal-edit-barang'].forEach(function (id) { document.getElementById(id).style.display = 'none'; });
});

// Pindah modal ke body
document.addEventListener('DOMContentLoaded', function() {
    var m = document.getElementById('modal-tambah-barang');
    if (m) document.body.appendChild(m);
    m = document.getElementById('modal-edit-barang');
    if (m) document.body.appendChild(m);
});
