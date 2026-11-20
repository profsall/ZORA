<?php
// Kumpulan fungsi kecil yang dipakai di banyak halaman: membuat link, menampilkan teks, format uang, dan lain-lain.


/**
 * Menghitung alamat folder public.
 *
 * Contoh: kalau aplikasi dibuka lewat
 *     /ujicoba/public/index.php
 * maka hasilnya
 *     /ujicoba/public
 */
function public_base_path()
{
    $lokasiIndex = '/index.php';

    if (isset($_SERVER['SCRIPT_NAME'])) {
        $lokasiIndex = $_SERVER['SCRIPT_NAME'];
    }

    // Ubah tanda \ menjadi / supaya sama di Windows maupun Linux
    $lokasiIndex = str_replace('\\', '/', $lokasiIndex);

    // Ambil nama foldernya saja
    $folder = dirname($lokasiIndex);

    // dirname() bisa mengembalikan "\" di Windows.
    // Samakan dulu supaya pemeriksaan di bawah tetap benar.
    $folder = str_replace('\\', '/', $folder);

    // Kalau aplikasi ada di alamat paling depan
    if ($folder === '.' || $folder === '/' || $folder === '') {
        return '';
    }

    // Buang garis miring di akhir
    return rtrim($folder, '/');
}


/**
 * Membuat link ke halaman di dalam aplikasi.
 *
 * Contoh:
 *     base_url('cart')          ->  .../index.php?url=cart
 *     base_url('products/nova-phone-x')
 *     base_url('products?category=audio')
 */
function base_url($tujuan = '')
{
    // Pisahkan antara halaman dan parameter tambahan
    $pisah = explode('?', $tujuan, 2);
    $halaman = trim($pisah[0], '/');

    // Kalau tidak diisi, tampilkan halaman home
    if ($halaman === '') {
        $halaman = 'home';
    }

    // Amankan setiap bagian alamat halaman
    $bagian = explode('/', $halaman);
    $bagianAman = [];

    foreach ($bagian as $satu) {
        $bagianAman[] = rawurlencode($satu);
    }

    $link = public_base_path() . '/index.php?url=' . implode('/', $bagianAman);

    // Tambahkan parameter tambahan kalau ada
    if (isset($pisah[1]) && $pisah[1] !== '') {
        $link = $link . '&' . ltrim($pisah[1], '&');
    }

    return $link;
}


// Membuat link ke file gambar, CSS, atau JavaScript. Contoh: asset('css/style.css')
function asset($namaFile)
{
    $namaFile = str_replace('\\', '/', $namaFile);
    $namaFile = ltrim($namaFile, '/');

    return public_base_path() . '/assets/' . $namaFile;
}

// Pindah ke halaman lain.
function redirect($tujuan)
{
    header('Location: ' . base_url($tujuan));
    exit;
}

// Mengamankan teks sebelum ditampilkan ke browser
// Dipakai supaya kode HTML seperti <script> tidak ikut dijalankan
// ketika ditampilkan sebagai teks biasa. Ini mencegah serangan XSS.
function e($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}


// Mengubah angka menjadi format rupiah.
// Contoh: 1500000  ->  "Rp 1.500.000"
function rupiah($angka)
{
    $angka = (float) $angka;
    return 'Rp ' . number_format($angka, 0, ',', '.');
}


// Mengubah nama produk menjadi alamat yang rapi (slug).
// Contoh: "AirBook 14 Pro"  ->  "airbook-14-pro"
function slugify($teks)
{
    $teks = strtolower(trim($teks));

    // Ganti semua karakter selain huruf dan angka dengan tanda -
    $teks = preg_replace('/[^a-z0-9]+/', '-', $teks);

    // Buang tanda - di awal dan akhir
    $teks = trim($teks, '-');

    // Kalau hasilnya kosong, pakai nama cadangan
    if ($teks === '') {
        return 'produk';
    }

    return $teks;
}

// Menyimpan pesan singkat untuk ditampilkan di halaman berikutnya.
// Pesan hanya muncul satu kali, setelah dibaca langsung dihapus.
function flash($nama, $isi = null)
{
    // Kalau ada dua argumen, berarti sedang menyimpan pesan
    if (func_num_args() > 1) {
        $_SESSION['_flash'][$nama] = $isi;
        return null;
    }

    // Kalau satu argumen, berarti sedang mengambil pesan
    if (isset($_SESSION['_flash'][$nama])) {
        $pesan = $_SESSION['_flash'][$nama];
        unset($_SESSION['_flash'][$nama]);
        return $pesan;
    }

    return null;
}


/**
 * Menampilkan halaman lengkap (header + isi halaman + footer).
 *
 * $namaHalaman : nama file view tanpa .php
 *                contoh: 'home/index' atau 'cart/index'
 * $data        : data yang dikirim dari controller
 *                contoh: ['pageTitle' => 'Keranjang', 'items' => $items]
 */
function render($namaHalaman, $data = [])
{
    // Ubah data menjadi variabel yang bisa dipakai di dalam view.
    // Contoh: $data['pageTitle'] menjadi $pageTitle
    if (is_array($data)) {
        foreach ($data as $nama => $isi) {
            ${$nama} = $isi;
        }
    }

    // Lokasi file view yang akan ditampilkan
    $fileView = __DIR__ . '/../views/' . $namaHalaman . '.php';

    // Kalau file tidak ada, hentikan program
    if (!is_file($fileView)) {
        http_response_code(500);
        exit('View tidak ditemukan: ' . e($namaHalaman));
    }

    // Tampilkan layout halaman satu per satu
    require __DIR__ . '/../views/layouts/header.php';
    require $fileView;
    require __DIR__ . '/../views/layouts/footer.php';
}


/**
 * Membuat token keamanan untuk formulir (CSRF token).
 *
 * Token ini memastikan formulir yang dikirim benar-benar berasal
 * dari halaman aplikasi sendiri, bukan dari situs lain.
 */
function csrf_token()
{
    // Buat token baru kalau belum ada
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}


/**
 * Membuat kotak input tersembunyi berisi token CSRF.
 * Tempelkan fungsi ini di dalam setiap form POST.
 *
 * Contoh: <?= csrf_field() ?>
 */
function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}


/**
 * Memeriksa apakah token CSRF pada form cocok dengan yang ada di session.
 * Kalau tidak cocok, halaman langsung dihentikan.
 */
function verify_csrf()
{
    $tokenDikirim = '';

    if (isset($_POST['_token'])) {
        $tokenDikirim = (string) $_POST['_token'];
    }

    // Bandingkan dua token dengan cara yang aman
    $cocok = false;
    if ($tokenDikirim !== '') {
        $cocok = hash_equals(csrf_token(), $tokenDikirim);
    }

    if (!$cocok) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
}


/**
 * Menampilkan kembali isi form yang sudah diketik sebelumnya.
 *
 * Dipakai saat validasi gagal, supaya pengguna tidak perlu mengetik ulang.
 */
function old($namaField, $nilaiDefault = '')
{
    $nilai = $nilaiDefault;

    if (isset($_POST[$namaField])) {
        $nilai = $_POST[$namaField];
    }

    // Kalau isinya bukan teks sederhana, pakai nilai default
    if (!is_scalar($nilai)) {
        $nilai = $nilaiDefault;
    }

    return e((string) $nilai);
}


/**
 * Menentukan gambar produk yang dipakai.
 * Kalau produk tidak punya gambar, pakai gambar cadangan.
 */
function product_image($gambar)
{
    if (empty($gambar)) {
        return 'https://placehold.co/800x900/eaf1f9/174a82?text=ZORA';
    }

    return $gambar;
}


/**
 * Memeriksa apakah permintaan saat ini menggunakan metode POST.
 * Dipakai untuk membedakan "membuka halaman" dan "mengirim form".
 */
function is_post()
{
    if (!isset($_SERVER['REQUEST_METHOD'])) {
        return false;
    }

    return $_SERVER['REQUEST_METHOD'] === 'POST';
}


/**
 * Mengambil parameter teks dari URL (GET).
 *
 * Contoh URL: index.php?url=products&q=laptop
 *     get_string('q')  ->  "laptop"
 */
function get_string($nama, $nilaiDefault = '')
{
    if (!isset($_GET[$nama])) {
        return $nilaiDefault;
    }

    if (!is_string($_GET[$nama])) {
        return $nilaiDefault;
    }

    return trim($_GET[$nama]);
}


/**
 * Mengambil isi input teks dari form (POST).
 *
 * Contoh form: <input name="email">
 *     post_string('email')  ->  isi input tersebut
 */
function post_string($nama, $nilaiDefault = '')
{
    if (!isset($_POST[$nama])) {
        return $nilaiDefault;
    }

    if (!is_string($_POST[$nama])) {
        return $nilaiDefault;
    }

    return trim($_POST[$nama]);
}


/**
 * Daftar label status pesanan dalam Bahasa Indonesia.
 */
function label_status_pesanan($status)
{
    $daftar = [
        'pending'    => 'Menunggu',
        'confirmed'  => 'Dikonfirmasi',
        'processing' => 'Diproses',
        'shipped'    => 'Dikirim',
        'completed'  => 'Selesai',
        'cancelled'  => 'Dibatalkan',
    ];

    if (isset($daftar[$status])) {
        return $daftar[$status];
    }

    // Kalau status tidak dikenal, rapikan sendiri teksnya
    return ucfirst(str_replace('_', ' ', $status));
}


/**
 * Daftar label status produk dalam Bahasa Indonesia.
 */
function label_status_produk($status)
{
    $daftar = [
        'active'   => 'Aktif',
        'draft'    => 'Draft',
        'archived' => 'Diarsipkan',
    ];

    if (isset($daftar[$status])) {
        return $daftar[$status];
    }

    return ucfirst($status);
}


/**
 * Daftar label metode pembayaran.
 */
function label_metode_pembayaran($metode)
{
    $daftar = [
        'bank_transfer' => 'Transfer bank',
        'qris'          => 'QRIS',
        'ewallet'       => 'E-wallet',
        'cod'           => 'Bayar di tempat',
    ];

    if (isset($daftar[$metode])) {
        return $daftar[$metode];
    }

    return ucfirst(str_replace('_', ' ', $metode));
}


/**
 * Menghitung biaya pengiriman.
 *
 * Aturan toko ZORA:
 * - Belanja minimal Rp 500.000  ->  gratis ongkir
 * - Selain itu                  ->  Rp 15.000
 */
function hitung_ongkir($subtotal)
{
    if ($subtotal >= 500000) {
        return 0;
    }

    return 15000;
}
