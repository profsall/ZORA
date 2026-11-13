/* ZORA — validasi dan umpan balik keranjang */
(function () {
    'use strict';

    function siapkanValidasiJumlah() {
        var daftarInput = document.querySelectorAll('.quantity-form input[type="number"]');

        daftarInput.forEach(function (input) {
            input.addEventListener('blur', function () {
                var minimal = Number(this.min || 1);
                var maksimal = Number(this.max || 999);
                var angka = Number.parseInt(this.value, 10);

                if (!Number.isFinite(angka)) angka = minimal;
                if (angka < minimal) angka = minimal;
                if (angka > maksimal) angka = maksimal;
                this.value = String(angka);
            });
        });
    }

    function siapkanUmpanBalikForm(pemilih, teks) {
        document.querySelectorAll(pemilih).forEach(function (form) {
            form.addEventListener('submit', function () {
                var tombol = this.querySelector('button[type="submit"], button:not([type])');
                if (!tombol) return;

                tombol.disabled = true;
                tombol.dataset.originalText = tombol.textContent.trim();
                tombol.textContent = teks;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        siapkanValidasiJumlah();
        siapkanUmpanBalikForm('.quantity-form', 'Memperbarui…');
        siapkanUmpanBalikForm('.add-cart-form', 'Menambahkan…');
    });
})();
