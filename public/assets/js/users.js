/* Kelola User: modal tambah/edit akun. */
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeAllModals() { ['modal-tambah','modal-edit'].forEach(id => { const el = document.getElementById(id); if (el) el.style.display = 'none'; }); }
function openEditModal(u) {
    document.getElementById('edit-nama').value = u.nama_lengkap;
    document.getElementById('edit-username').value = u.username;
    document.getElementById('edit-role').value = u.role;
    document.getElementById('pwd-edit').value = '';
    document.getElementById('form-edit-user').action = '/users/' + u.id;
    openModal('modal-edit');
}
document.addEventListener('keydown', e => { if (e.key==='Escape') closeAllModals(); });
