// Interaksi halaman checkout: penanda metode bayar dan cek form sebelum kirim.

document.addEventListener('DOMContentLoaded', function () {

    var form = document.querySelector('.checkout-layout form');
    var pilihanBayar = document.querySelectorAll('.payment-options input[type="radio"]');

    // Tandai label yang radio-nya sedang dipilih
    var i;

    for (i = 0; i < pilihanBayar.length; i++) {

        pilihanBayar[i].addEventListener('change', function () {

            var daftarLabel = document.querySelectorAll('.payment-options label');
            var j;

            for (j = 0; j < daftarLabel.length; j++) {

                var iniLabel = daftarLabel[j];

                if (iniLabel.contains(this) && this.checked) {
                    iniLabel.classList.add('is-selected');
                } else {
                    iniLabel.classList.remove('is-selected');
                }
            }
        });
    }

    // Bukan halaman checkout, berhenti di sini
    if (!form) {
        return;
    }

    // Cek form sebelum dikirim
    form.addEventListener('submit', function (kejadian) {

        // Ada isian yang belum benar, tahan dulu
        if (!form.reportValidity()) {

            kejadian.preventDefault();

            var isianSalah = form.querySelector(':invalid');

            if (isianSalah) {
                isianSalah.focus();
            }

            return;
        }

        // Sudah benar semua, tombol dimatikan biar nggak dobel
        var tombol = form.querySelector('button[type="submit"], button:not([type])');

        if (tombol) {

            tombol.disabled = true;
            tombol.setAttribute('aria-busy', 'true');
            tombol.textContent = 'Memproses pesanan…';
        }
    });
});