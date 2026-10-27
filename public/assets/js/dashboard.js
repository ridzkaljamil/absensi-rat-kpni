/* Dashboard: pembaruan otomatis kuorum per sesi setiap 5 detik. */
(function () {
    var JUMLAH_SESI = (window.KP_PAGE || {}).jumlahSesi || 0;

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
        kpFetch('/dashboard/polling')
            .then(function (d) {
                // RAT berganti/dihapus, atau jumlah sesi berubah → muat ulang supaya angka tidak tercampur
                if (d.error || d.id_rat !== (window.KP_PAGE || {}).idRat || (d.sesi || []).length !== JUMLAH_SESI) { location.reload(); return; }
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
