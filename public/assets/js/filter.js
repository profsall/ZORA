
/* ==========================================================
   ZORA — filter katalog produk
   ----------------------------------------------------------
   Isinya:
   1. Kirim form filter otomatis ketika kategori / urutan diganti
   2. Kirim form filter ketika tombol Enter ditekan di kotak cari
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {

    var form = document.querySelector('.filter-bar');

    // Kalau tidak ada form filter di halaman ini, berhenti.
    if (!form) {
        return;
    }

    // Fungsi untuk mengirim form filter.
    function kirimFilter() {

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    // Setiap kali dropdown kategori atau urutan diganti,
    // langsung kirim formnya.
    var daftarSelect = form.querySelectorAll('select');
    var i;

    for (i = 0; i < daftarSelect.length; i++) {
        daftarSelect[i].addEventListener('change', kirimFilter);
    }

    // Kalau tombol Enter ditekan di kotak pencarian,
    // kirim formnya juga.
    var kotakCari = form.querySelector('input[type="search"], input[name="q"], .search-field input');

    if (kotakCari) {

        kotakCari.addEventListener('keydown', function (kejadian) {

            if (kejadian.key === 'Enter') {

                kejadian.preventDefault();
                kirimFilter();
            }
        });
    }
});
