// Filter katalog: form dikirim otomatis saat kategori atau urutan diganti.

document.addEventListener('DOMContentLoaded', function () {

    var form = document.querySelector('.filter-bar');

    // Nggak ada form filter di halaman ini
    if (!form) {
        return;
    }

    function kirimFilter() {

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    // Ganti kategori atau urutan langsung kirim
    var daftarSelect = form.querySelectorAll('select');
    var i;

    for (i = 0; i < daftarSelect.length; i++) {
        daftarSelect[i].addEventListener('change', kirimFilter);
    }

    // Enter di kotak cari juga kirim
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