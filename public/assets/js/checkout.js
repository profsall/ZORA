
/* ==========================================================
   ZORA — interaksi halaman checkout
   ----------------------------------------------------------
   Isinya:
   1. Menandai metode pembayaran yang sedang dipilih
   2. Memeriksa form sebelum dikirim
   ========================================================== */

document.addEventListener('DOMContentLoaded', function () {

    var form = document.querySelector('.checkout-layout form');
    var pilihanBayar = document.querySelectorAll('.payment-options input[type="radio"]');

    /* ------------------------------------------------------
       1. Metode pembayaran yang sedang dipilih
       ------------------------------------------------------ */
    var i;

    for (i = 0; i < pilihanBayar.length; i++) {

        pilihanBayar[i].addEventListener('change', function () {

            var daftarLabel = document.querySelectorAll('.payment-options label');
            var j;

            for (j = 0; j < daftarLabel.length; j++) {

                var iniLabel = daftarLabel[j];

                // Label ini dipilih kalau label memuat radio yang aktif.
                if (iniLabel.contains(this) && this.checked) {
                    iniLabel.classList.add('is-selected');
                } else {
                    iniLabel.classList.remove('is-selected');
                }
            }
        });
    }

    // Kalau tidak ada form checkout, tidak perlu lanjut.
    if (!form) {
        return;
    }

    /* ------------------------------------------------------
       2. Pemeriksaan form sebelum dikirim
       ------------------------------------------------------ */
    form.addEventListener('submit', function (kejadian) {

        // Kalau ada isian yang belum benar, tahan pengirimannya.
        if (!form.reportValidity()) {

            kejadian.preventDefault();

            var isianSalah = form.querySelector(':invalid');

            if (isianSalah) {
                isianSalah.focus();
            }

            return;
        }

        // Semua sudah benar: matikan tombol supaya tidak dobel.
        var tombol = form.querySelector('button[type="submit"], button:not([type])');

        if (tombol) {

            tombol.disabled = true;
            tombol.setAttribute('aria-busy', 'true');
            tombol.textContent = 'Memproses pesanan…';
        }
    });
});
