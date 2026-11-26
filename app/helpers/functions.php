<?php
// Fungsi bantu yang dipakai di banyak halaman: link, escape, format uang, dan sejenisnya.


// Alamat folder public, hasilnya misal "/ujicoba/public"
function public_base_path()
{
    $lokasiIndex = '/index.php';

    if (isset($_SERVER['SCRIPT_NAME'])) {
        $lokasiIndex = $_SERVER['SCRIPT_NAME'];
    }

    // Disamakan dulu biar aman di Windows
    $lokasiIndex = str_replace('\\', '/', $lokasiIndex);

    $folder = dirname($lokasiIndex);
    $folder = str_replace('\\', '/', $folder);

    if ($folder === '.' || $folder === '/' || $folder === '') {
        return '';
    }

    return rtrim($folder, '/');
}


// Bikin link ke halaman dalam aplikasi, contohnya base_url('cart')
function base_url($tujuan = '')
{
    // Pisahkan halaman dan parameternya
    $pisah = explode('?', $tujuan, 2);
    $halaman = trim($pisah[0], '/');

    if ($halaman === '') {
        $halaman = 'home';
    }

    // Tiap bagian alamat diamankan
    $bagian = explode('/', $halaman);
    $bagianAman = [];

    foreach ($bagian as $satu) {
        $bagianAman[] = rawurlencode($satu);
    }

    $link = public_base_path() . '/index.php?url=' . implode('/', $bagianAman);

    if (isset($pisah[1]) && $pisah[1] !== '') {
        $link = $link . '&' . ltrim($pisah[1], '&');
    }

    return $link;
}


// Link ke file gambar, CSS, atau JavaScript
function asset($namaFile)
{
    $namaFile = str_replace('\\', '/', $namaFile);
    $namaFile = ltrim($namaFile, '/');

    return public_base_path() . '/assets/' . $namaFile;
}

// Pindah ke halaman lain
function redirect($tujuan)
{
    header('Location: ' . base_url($tujuan));
    exit;
}

// Escape teks sebelum ditampilkan, biar HTML dari user nggak jalan
function e($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}


// Format angka jadi rupiah, contohnya 1500000 jadi "Rp 1.500.000"
function rupiah($angka)
{
    $angka = (float) $angka;
    return 'Rp ' . number_format($angka, 0, ',', '.');
}


// Ubah nama produk jadi slug, "AirBook 14 Pro" jadi "airbook-14-pro"
function slugify($teks)
{
    $teks = strtolower(trim($teks));

    // Selain huruf dan angka diganti tanda hubung
    $teks = preg_replace('/[^a-z0-9]+/', '-', $teks);
    $teks = trim($teks, '-');

    if ($teks === '') {
        return 'produk';
    }

    return $teks;
}

// Pesan singkat yang cuma muncul sekali di halaman berikutnya
function flash($nama, $isi = null)
{
    // Dua argumen berarti menyimpan pesan
    if (func_num_args() > 1) {
        $_SESSION['_flash'][$nama] = $isi;
        return null;
    }

    if (isset($_SESSION['_flash'][$nama])) {
        $pesan = $_SESSION['_flash'][$nama];
        unset($_SESSION['_flash'][$nama]);
        return $pesan;
    }

    return null;
}


// Tampilkan halaman lengkap: header, isi, footer.
// Datanya disebar jadi variabel di dalam view.
function render($namaHalaman, $data = [])
{
    if (is_array($data)) {
        foreach ($data as $nama => $isi) {
            ${$nama} = $isi;
        }
    }

    $fileView = __DIR__ . '/../views/' . $namaHalaman . '.php';

    if (!is_file($fileView)) {
        http_response_code(500);
        exit('View tidak ditemukan: ' . e($namaHalaman));
    }

    require __DIR__ . '/../views/layouts/header.php';
    require $fileView;
    require __DIR__ . '/../views/layouts/footer.php';
}


// Token CSRF, dipakai biar form cuma bisa dikirim dari halaman sendiri
function csrf_token()
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}


// Input hidden berisi token CSRF, taruh di dalam setiap form POST
function csrf_field()
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}


// Cek token dari form, kalau nggak cocok halaman langsung dihentikan
function verify_csrf()
{
    $tokenDikirim = '';

    if (isset($_POST['_token'])) {
        $tokenDikirim = (string) $_POST['_token'];
    }

    $cocok = false;
    if ($tokenDikirim !== '') {
        $cocok = hash_equals(csrf_token(), $tokenDikirim);
    }

    if (!$cocok) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
}


// Kembalikan isi form yang sudah diketik, dipakai saat validasi gagal
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


// Gambar produk, pakai gambar cadangan kalau kosong
function product_image($gambar)
{
    if (empty($gambar)) {
        return 'https://placehold.co/800x900/eaf1f9/174a82?text=ZORA';
    }

    return $gambar;
}


// Cek apakah request sekarang pakai metode POST
function is_post()
{
    if (!isset($_SERVER['REQUEST_METHOD'])) {
        return false;
    }

    return $_SERVER['REQUEST_METHOD'] === 'POST';
}


// Ambil teks dari URL, misal get_string('q') untuk ?q=laptop
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


// Ambil isi input teks dari form
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


// Label status pesanan dalam Bahasa Indonesia
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

    // Status yang nggak dikenal dirapikan sendiri
    return ucfirst(str_replace('_', ' ', $status));
}


// Label status produk dalam Bahasa Indonesia
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


// Label metode pembayaran
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


// Ongkir: gratis kalau belanja minimal Rp 500.000, selain itu Rp 15.000
function hitung_ongkir($subtotal)
{
    if ($subtotal >= 500000) {
        return 0;
    }

    return 15000;
}