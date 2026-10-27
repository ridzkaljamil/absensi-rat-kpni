/* Sistem Absensi RAT KPNI — fungsi inti, dimuat di <head> semua halaman.
   kpSetNum (animasi angka), kpFetch (fetch JSON + CSRF + jenis kegagalan), latar kontinu. */
// Background "bersambung": semua animasi latar dihitung dari satu titik waktu yang
// disimpan di browser, jadi saat pindah halaman animasinya melanjutkan, tidak mulai dari awal.
(function () {
    var d = document.documentElement, epoch = Date.now();
    try {
        epoch = +localStorage.getItem('kpni_bg_epoch') || 0;
        if (!epoch) { epoch = Date.now(); localStorage.setItem('kpni_bg_epoch', String(epoch)); }
        var pertama = !sessionStorage.getItem('kpni_splash_done');
        var habisLogin = (Date.now() - (+localStorage.getItem('kpni_last_login') || 0)) < 15000;
        if (!pertama && !habisLogin) d.classList.add('tanpa-splash');
    } catch (e) {}
    window.KPNI_BG_EPOCH = epoch;
    d.style.setProperty('--bg-t', ((Date.now() - epoch) / 1000).toFixed(3) + 's');
})();
// Angka berubah dengan animasi hitung (dipakai dashboard & absensi)
function kpSetNum(el, nilai, akhiran) {
    if (!el) return;
    akhiran = akhiran || '';
    var dari = parseFloat(el.textContent) || 0, ke = +nilai;
    if (isNaN(ke)) { el.textContent = nilai; return; }
    if (dari === ke) { el.textContent = ke + akhiran; return; }
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { el.textContent = ke + akhiran; return; }
    var mulai = performance.now(), durasi = 600, token = (el._kpAnim || 0) + 1;
    el._kpAnim = token; // animasi lama berhenti kalau angka berubah lagi sebelum selesai
    (function langkah(now) {
        if (el._kpAnim !== token) return;
        var p = Math.min((now - mulai) / durasi, 1), e = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(dari + (ke - dari) * e) + akhiran;
        if (p < 1) requestAnimationFrame(langkah);
    })(mulai);
    el.classList.remove('kp-pop'); void el.offsetWidth; el.classList.add('kp-pop');
}

// Token CSRF untuk semua request AJAX (dikirim lewat header)
var metaCsrf = function (n) { var m = document.querySelector('meta[name="' + n + '"]'); return m ? m.content : ''; };
window.KP_CSRF = { header: metaCsrf('csrf-header'), hash: metaCsrf('csrf-hash') };

/**
 * fetch JSON yang membedakan jenis kegagalan:
 *  - err.jenis = 'jaringan' → server tidak terjangkau (boleh disimpan offline)
 *  - err.jenis = 'sesi'     → login habis / token kedaluwarsa (perlu muat ulang)
 *  - err.jenis = 'server'   → server error (jangan dianggap berhasil)
 */
function kpFetch(url, opsi) {
    opsi = opsi || {};
    var h = Object.assign({ 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, opsi.headers || {});
    if ((opsi.method || 'GET').toUpperCase() !== 'GET') h[KP_CSRF.header] = KP_CSRF.hash;
    var gagal = function (jenis, pesan) { var e = new Error(pesan); e.jenis = jenis; return e; };
    // Batas tunggu 10 detik: server yang macet diperlakukan seperti koneksi putus
    var batas = (window.AbortSignal && AbortSignal.timeout) ? { signal: AbortSignal.timeout(opsi.timeout || 10000) } : {};
    return fetch(url, Object.assign(batas, opsi, { headers: h, credentials: 'same-origin' })).then(function (r) {
        var json = (r.headers.get('content-type') || '').indexOf('application/json') !== -1;
        if (!json) {
            if (r.redirected || r.status === 401 || r.status === 403) throw gagal('sesi', 'Sesi login habis atau halaman terlalu lama terbuka. Muat ulang halaman lalu login lagi.');
            throw gagal('server', 'Server mengalami gangguan (kode ' + r.status + '). Data belum tersimpan, coba lagi.');
        }
        return r.json().then(function (d) {
            if (r.status === 401 || r.status === 403) {
                // 403 tanpa kode = token keamanan (CSRF) kedaluwarsa karena sesi login habis
                throw gagal('sesi', d.kode === 'akses_ditolak' ? d.message : 'Sesi login habis atau halaman terlalu lama terbuka. Muat ulang halaman lalu login lagi.');
            }
            if (r.status >= 500) throw gagal('server', d.message || 'Server mengalami gangguan.');
            return d;
        });
    }, function () { throw gagal('jaringan', 'Tidak terhubung ke server.'); });
}
