/* Data Anggota: modal tambah/edit/RFID/import. */
function openModal(id) {
    const el = document.getElementById(id);
    if (el && el.parentElement !== document.body) {
        document.body.appendChild(el);
    }
    if (el) el.style.display = 'flex';
    if (id === 'modal-tambah') setTimeout(() => document.getElementById('uid-tambah')?.focus(), 100);
    if (id === 'modal-rfid')   setTimeout(() => document.getElementById('input-rfid-modal')?.focus(), 100);
}

function closeAllModals() {
    ['modal-tambah','modal-import','modal-edit','modal-rfid','modal-hapus-semua'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });
}

// ESC untuk tutup modal
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAllModals(); });
// Reader RFID mengirim Enter setelah UID: jangan langsung kirim form, lanjut ke kolom yang belum diisi
document.getElementById('uid-tambah')?.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const kosong = [...e.target.form.querySelectorAll('input[required]')].find(i => !i.value.trim());
    if (kosong) kosong.focus(); else e.target.form.requestSubmit();
});

function cekNipInput(el) {
    const err = document.getElementById('nip-error');
    if (!err) return;
    const valid = el.value.length === 6 && /^\d{6}$/.test(el.value);
    err.style.display = el.value.length > 0 && !valid ? 'block' : 'none';
    el.style.borderColor = el.value.length > 0 && !valid ? '#b3262b' : '';
}

function validasiNip(form) {
    const nip = form.querySelector('[name="nip"]');
    if (!nip) return true;
    if (nip.value.length !== 6 || !/^\d{6}$/.test(nip.value)) {
        nip.focus();
        nip.style.borderColor = '#b3262b';
        const err = document.getElementById('nip-error');
        if (err) err.style.display = 'block';
        showToast('NIP harus tepat 6 digit angka!', 'error');
        return false;
    }
    return true;
}

function showToast(msg, type='success') {
    const t = document.createElement('div');
    t.className = 'toast toast-' + (type==='error' ? 'error' : 'success');
    t.innerHTML = `<i class="ti ti-${type==='error'?'alert-circle':'circle-check'}" style="font-size:18px;flex-shrink:0;"></i><span>${msg}</span><button class="toast-close" onclick="this.parentElement.remove()">×</button>`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity 0.4s'; setTimeout(()=>t.remove(),400); }, 4000);
}

function openEditModal(anggota) {
    document.getElementById('edit-pr').value   = anggota.nip;
    document.getElementById('edit-nama').value = anggota.nama;
    // Isi dropdown departemen, section, shift
    const dept  = document.getElementById('edit-departemen');
    const sect  = document.getElementById('edit-section');
    const shift = document.getElementById('edit-shift');
    if (dept)  dept.value  = anggota.departemen || '';
    if (sect)  sect.value  = anggota.section    || '';
    if (shift) shift.value = anggota.shift      || '';
    document.getElementById('form-edit').action = '/anggota/' + anggota.id;
    openModal('modal-edit');
}

function openRfidModal(id, nama, uidLama) {
    document.getElementById('rfid-nama').textContent = nama;
    document.getElementById('rfid-modal-title').textContent = uidLama ? 'Ganti Kartu RFID' : 'Daftarkan Kartu RFID';
    document.getElementById('input-rfid-modal').value = '';
    document.getElementById('uid-preview').textContent = uidLama ? 'Kartu sekarang: ' + uidLama : '';
    document.getElementById('form-rfid').action = '/anggota/' + id + '/rfid';
    var lepas = document.getElementById('btn-lepas-kartu');
    lepas.style.display = uidLama ? '' : 'none';
    lepas.onclick = function () {
        closeAllModals();
        showModal({ title: 'Lepas kartu ' + nama + '?', message: 'Kartu ' + uidLama + ' dilepas dari anggota ini (misalnya hilang atau rusak) dan bisa dipakai orang lain.', confirmText: 'Lepas Kartu', type: 'warning',
            onConfirm: function () { var f = document.getElementById('form-lepas-kartu'); f.action = '/anggota/' + id + '/rfid/lepas'; f.submit(); } });
    };
    openModal('modal-rfid');
}

// Preview UID saat user tap kartu di modal RFID
const rfidInput = document.getElementById('input-rfid-modal');
if (rfidInput) {
    rfidInput.addEventListener('input', function() {
        const preview = document.getElementById('uid-preview');
        if (this.value) {
            preview.textContent = 'UID terdeteksi: ' + this.value;
            preview.style.color = '#16a34a';
        }
    });
}

function cekKonfirmasi() {
    const val = document.getElementById('input-konfirmasi').value;
    const btn = document.getElementById('btn-hapus-semua');
    const match = val === 'HAPUS SEMUA DATA ANGGOTA';
    btn.disabled = !match;
    btn.style.opacity = match ? '1' : '0.4';
    btn.style.cursor  = match ? 'pointer' : 'not-allowed';
}
