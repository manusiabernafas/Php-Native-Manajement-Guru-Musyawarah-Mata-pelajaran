// Konfirmasi aksi penting (hapus, tolak, batalkan)
document.querySelectorAll('form[data-yakin]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.yakin)) e.preventDefault(); });
});
// Submit otomatis saat filter diubah
document.querySelectorAll('form[data-auto] select, form[data-auto] input[type=date]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
});
// Buka/tutup menu samping di layar kecil
var tg = document.querySelector('.menu-toggle');
if (tg) tg.addEventListener('click', function () { document.getElementById('drawer').classList.toggle('open'); });
document.addEventListener('click', function (e) {
    var d = document.getElementById('drawer');
    if (d && d.classList.contains('open') && !d.contains(e.target) && !e.target.closest('.menu-toggle')) d.classList.remove('open');
});
// Salin teks (kode presensi, kode verifikasi)
document.querySelectorAll('[data-salin]').forEach(function (b) {
    b.addEventListener('click', function () {
        var t = b.dataset.salin;
        if (navigator.clipboard) navigator.clipboard.writeText(t).then(function () { b.textContent = 'Tersalin!'; setTimeout(function () { b.textContent = 'Salin'; }, 1800); });
        else prompt('Salin:', t);
    });
});
