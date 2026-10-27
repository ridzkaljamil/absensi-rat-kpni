/* Halaman Peserta RAT: modal import/tambah/RFID, isi otomatis dari data induk. */
var MODAL_IDS = ['modal-import', 'modal-tambah', 'modal-rfid'];
function bukaModal(id) { document.getElementById(id).style.display = 'flex'; }
function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
function bukaRfid(pid, nama) {
    document.getElementById('rfid-nama').textContent = 'Peserta: ' + nama;
    document.getElementById('form-rfid').action = '/peserta-rat/' + pid + '/rfid';
    document.getElementById('input-rfid').value = '';
    bukaModal('modal-rfid');
    setTimeout(function () { document.getElementById('input-rfid').focus(); }, 100);
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') MODAL_IDS.forEach(tutupModal); });
// Modal dipindah ke <body> supaya position:fixed tidak terganggu backdrop-filter parent
document.addEventListener('DOMContentLoaded', function () {
    MODAL_IDS.forEach(function (id) { var el = document.getElementById(id); if (el) document.body.appendChild(el); });
    if ((window.KP_PAGE || {}).bukaTambah) bukaModal('modal-tambah'); // gagal simpan: buka lagi form beserta isiannya
});
// Isi otomatis dari data induk saat NIP diketik
(function () {
    var ni = document.querySelector('#modal-tambah input[name="nip"]');
    if (!ni) return;
    var rf = document.getElementById('tambah-rfid');
    var info = document.getElementById('tambah-rfid-info');
    var infoAwal = info ? info.textContent : '';
    var t = null;
    // RFID reader mengirim Enter setelah UID: jangan langsung submit, cukup pilih isi kolom
    rf.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); rf.select(); info.textContent = 'Kartu terbaca. Klik Tambahkan untuk menyimpan.'; }
    });
    rf.addEventListener('input', function () { rfOtomatis = false; if (!rf.value) info.textContent = infoAwal; });
    var rfOtomatis = false; // true kalau kolom RFID diisi dari data tersimpan, bukan di-tap
    ni.addEventListener('input', function () {
        clearTimeout(t);
        if (rfOtomatis) { rf.value = ''; info.textContent = infoAwal; rfOtomatis = false; }
        var nip = this.value.trim();
        if (nip.length < 4) return;
        t = setTimeout(function () {
            kpFetch('/peserta-rat/lookup-nip?nip=' + encodeURIComponent(nip))
                .then(function (d) {
                    if (ni.value.trim() !== nip) return; // jawaban untuk NIP lama (sudah diketik ulang)
                    if (d.uid_rfid && rf && !rf.value) {
                        rf.value = d.uid_rfid; rfOtomatis = true;
                        info.textContent = 'Kartu sudah terdaftar untuk NIP ini, otomatis diisi. Ganti dengan tap kartu baru kalau perlu.';
                    }
                    if (!d.found) return;
                    var n = document.querySelector('#modal-tambah input[name="nama"]');
                    if (n && !n.value) n.value = d.nama;
                    ['departemen', 'section', 'shift'].forEach(function (f) {
                        var el = document.querySelector('#modal-tambah [name="' + f + '"]');
                        if (!el || !d[f]) return;
                        if (el.tagName === 'SELECT') {
                            for (var i = 0; i < el.options.length; i++) { if (el.options[i].value === d[f]) { el.selectedIndex = i; break; } }
                        } else if (!el.value) { el.value = d[f]; }
                    });
                }).catch(function () {});
        }, 300);
    });
})();
