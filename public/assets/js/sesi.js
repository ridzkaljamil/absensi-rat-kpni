/* Kelola Sesi: input jam 24 jam. */
function bukaTambahSesi() { document.getElementById('modal-tambah-sesi').style.display = 'flex'; }
document.addEventListener('DOMContentLoaded', function () {
    var m = document.getElementById('modal-tambah-sesi');
    if (m) document.body.appendChild(m);
});

// Input jam 24 jam (HH:MM) — tidak bergantung pada format AM/PM bawaan browser
document.querySelectorAll('.jam-24').forEach(function (el) {
    el.setAttribute('inputmode', 'numeric');
    el.setAttribute('maxlength', '5');
    el.setAttribute('placeholder', 'HH:MM');
    el.setAttribute('pattern', '([01][0-9]|2[0-3]):[0-5][0-9]');
    el.setAttribute('title', 'Format 24 jam, contoh 08:00 atau 13:30');
    el.setAttribute('autocomplete', 'off');
    if (!el.disabled) el.required = true;

    el.addEventListener('input', function () {
        var d = el.value.replace(/\D/g, '').slice(0, 4);
        el.value = d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d;
    });
    el.addEventListener('blur', function () {
        var d = el.value.replace(/\D/g, '');
        if (d.length === 1 || d.length === 2) d = ('0' + d).slice(-2) + '00';   // "8" → 08:00
        else if (d.length === 3) d = '0' + d;                                    // "830" → 08:30
        if (d.length === 4) el.value = d.slice(0, 2) + ':' + d.slice(2);
    });
});
