# ZORA — Electronics Store (PHP Native MVC)

Aplikasi toko elektronik **ZORA** dengan tampilan minimalis biru-putih yang terinspirasi pendekatan retail sederhana: navigasi ringkas, ruang putih yang cukup, hierarki informasi jelas, dan fokus pada produk.

## Teknologi

- PHP Native 8.0+ dengan pola MVC sederhana
- MySQL / MariaDB melalui PDO dan prepared statements
- Bootstrap 5.3 untuk komponen dasar, CSS khusus untuk identitas ZORA
- JavaScript untuk navigasi mobile, filter, dan validasi interaksi

## Struktur proyek

Struktur folder mengikuti permintaan: `app/controllers`, `app/models`, `app/views`, `app/helpers`, `config`, `public/assets`, dan `storage/uploads`. File SQL ada di root proyek sebagai `zora.sql`.

## Instalasi XAMPP

1. Ekstrak folder `ZORA` ke `C:\xampp\htdocs\ZORA`.
2. Jalankan **Apache** dan **MySQL** di XAMPP.
3. Buka `http://localhost/phpmyadmin`.
4. Pilih **Import**, pilih file `ZORA/zora.sql`, lalu klik **Import/Go**. File SQL membuat database `zora` beserta tabel dan data produk contoh. **Catatan:** skrip SQL ini menghapus tabel ZORA yang sudah ada sebelum membuat ulang; backup data terlebih dahulu bila database lama berisi data penting.
5. Periksa `config/database.php`. Konfigurasi default XAMPP: host `127.0.0.1`, database `zora`, user `root`, password kosong.
6. Buka `http://localhost/ZORA/public/`.

Jika Apache tidak mengaktifkan `mod_rewrite`, aktifkan modul tersebut di `httpd.conf` dan pastikan `AllowOverride All` untuk direktori htdocs. Alternatif URL sementara dapat memakai `http://localhost/ZORA/public/index.php?url=products`.

## Akun admin awal

- Email: `admin@zora.test`
- Password: `AdminZora123!`

Akun ini hanya untuk pengembangan lokal. Ganti password dan hapus/ubah akun demo sebelum aplikasi dipublikasikan.

## Fitur yang sudah terhubung ke database

- Homepage, kategori, katalog, pencarian, dan pengurutan produk.
- Detail produk berdasarkan slug.
- Registrasi, login, logout, dan pembatasan akses admin.
- Keranjang belanja disimpan di tabel `carts` dan `cart_items`, termasuk keranjang tamu berbasis session.
- Checkout membuat `orders`, `order_items`, dan `payments` dalam transaksi database; stok produk dikurangi saat pesanan dibuat.
- Riwayat dan detail pesanan pelanggan.
- Dashboard admin, penambahan produk, arsip produk, dan pembaruan status pesanan.

## Catatan pengembangan

- Pembayaran QRIS, transfer, e-wallet, dan COD saat ini hanya dicatat sebagai pilihan/metode dan status awal `pending`. Belum terhubung ke payment gateway atau proses verifikasi bukti pembayaran.
- Biaya pengiriman disimulasikan Rp15.000 dan gratis untuk subtotal minimal Rp500.000. Sesuaikan aturan di `app/models/Order.php` dan tampilan checkout.
- Produk contoh memakai gambar eksternal Unsplash. Gambar memerlukan koneksi internet. Gambar produk yang diunggah admin dapat dikembangkan lebih lanjut; folder `storage/uploads/products` dan `storage/uploads/payments` sudah disiapkan.
- Untuk keamanan produksi: gunakan HTTPS, password admin unik, validasi upload, rate limiting login, konfigurasi error/logging, backup database, dan set document root web server ke folder `public`.

## Tabel database

`users`, `categories`, `products`, `product_images`, `user_addresses`, `carts`, `cart_items`, `orders`, `order_items`, `payments`, `wishlist_items`.
