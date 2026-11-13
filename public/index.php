<?php
/**
 * HALAMAN UTAMA APLIKASI (FRONT CONTROLLER)
 * =========================================
 * Semua permintaan masuk lewat file ini.
 * Tugasnya: memuat file pendukung, membaca alamat URL,
 * lalu memanggil controller yang sesuai.
 */

// Mulai session untuk menyimpan data login dan keranjang
session_start();


// ==============================================================
// BAGIAN 1: MEMUAT FILE PENDUKUNG
// ==============================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/validation.php';


// ==============================================================
// BAGIAN 2: MEMUAT CLASS SECARA OTOMATIS
// ==============================================================
// Jadi kita tidak perlu menulis require untuk setiap model/controller.
// Cukup sebut nama class-nya, file akan dicari otomatis.

spl_autoload_register(function ($namaClass) {
    $folder = [
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/controllers/',
    ];

    foreach ($folder as $satuFolder) {
        $lokasiFile = $satuFolder . $namaClass . '.php';

        if (is_file($lokasiFile)) {
            require_once $lokasiFile;
            return;
        }
    }
});


// ==============================================================
// BAGIAN 3: MEMERIKSA KONEKSI DATABASE
// ==============================================================

try {
    db();

} catch (Throwable $e) {
    error_log('[ZORA database] ' . $e->getMessage());
    http_response_code(500);

    echo '<!doctype html>
    <html lang="id">
    <head><meta charset="utf-8"><title>Koneksi Database Gagal</title></head>
    <body style="font-family:Arial,sans-serif;padding:40px">
        <h1>Koneksi database ZORA gagal</h1>
        <p>Pastikan MySQL aktif dan database <strong>zora</strong>
        sudah dibuat serta dikonfigurasi dengan benar di
        <code>config/database.php</code>.</p>
    </body>
    </html>';

    exit;
}


// ==============================================================
// BAGIAN 4: MEMBACA ALAMAT URL
// ==============================================================
// Contoh URL: index.php?url=admin/products/edit/5
//
// Maka bagian-bagiannya:
//   bagian1 = admin
//   bagian2 = products
//   bagian3 = edit
//   bagian4 = 5

$isiUrl = '';

if (isset($_GET['url'])) {
    $isiUrl = $_GET['url'];
}

// Kalau yang dikirim bukan teks, anggap kosong
if (!is_string($isiUrl)) {
    $isiUrl = '';
}

// Buang garis miring di awal dan akhir
$alamat = trim($isiUrl, '/');

// Pecah alamat menjadi beberapa bagian
if ($alamat === '') {
    $bagian = [];
} else {
    $bagian = explode('/', $alamat);
}

$bagian1 = '';
$bagian2 = '';
$bagian3 = '';
$bagian4 = '';

if (isset($bagian[0])) {
    $bagian1 = $bagian[0];
}

if (isset($bagian[1])) {
    $bagian2 = $bagian[1];
}

if (isset($bagian[2])) {
    $bagian3 = $bagian[2];
}

if (isset($bagian[3])) {
    $bagian4 = $bagian[3];
}

// Kalau alamat kosong, tampilkan halaman beranda
if ($bagian1 === '') {
    $bagian1 = 'home';
}


// ==============================================================
// BAGIAN 5: MEMERIKSA KEAMANAN ALAMAT
// ==============================================================
// Hanya izinkan huruf, angka, tanda hubung, dan garis miring.
// Ini mencegah alamat berisi karakter aneh atau percobaan path traversal.

if ($alamat !== '') {
    $polaAman = '~^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$~';

    if (!preg_match($polaAman, $alamat)) {
        http_response_code(404);
        render('home/not-found', ['pageTitle' => 'Halaman Tidak Ditemukan']);
        exit;
    }
}


// ==============================================================
// BAGIAN 6: MENENTUKAN CONTROLLER YANG DIPANGGIL
// ==============================================================

try {

    // ----------------------------------------------------------
    // HALAMAN BERANDA
    // ----------------------------------------------------------
    if ($bagian1 === 'home') {
        $controller = new HomeController();
        $controller->index();
    }

    // ----------------------------------------------------------
    // HALAMAN KATALOG PRODUK
    // ----------------------------------------------------------
    elseif ($bagian1 === 'products') {
        $controller = new ProductController();

        if ($bagian2 !== '') {
            // products/nova-phone-pro  -> halaman detail
            $controller->show($bagian2);
        } else {
            // products                 -> halaman katalog
            $controller->index();
        }
    }

    // ----------------------------------------------------------
    // LOGIN, DAFTAR, LOGOUT
    // ----------------------------------------------------------
    elseif ($bagian1 === 'login') {
        $controller = new AuthController();
        $controller->login();
    }

    elseif ($bagian1 === 'register') {
        $controller = new AuthController();
        $controller->register();
    }

    elseif ($bagian1 === 'logout') {
        $controller = new AuthController();
        $controller->logout();
    }

    // ----------------------------------------------------------
    // KERANJANG
    // ----------------------------------------------------------
    elseif ($bagian1 === 'cart') {
        $controller = new CartController();

        if ($bagian2 === 'add') {
            $controller->add();
        } elseif ($bagian2 === 'update') {
            $controller->update();
        } elseif ($bagian2 === 'remove') {
            $controller->remove();
        } else {
            $controller->index();
        }
    }

    // ----------------------------------------------------------
    // CHECKOUT
    // ----------------------------------------------------------
    elseif ($bagian1 === 'checkout') {
        $controller = new CheckoutController();

        if (is_post()) {
            $controller->store();
        } else {
            $controller->index();
        }
    }

    // ----------------------------------------------------------
    // HALAMAN AKUN
    // ----------------------------------------------------------
    elseif ($bagian1 === 'account') {
        require_login();

        render('account/index', ['pageTitle' => 'Akun Saya']);
    }

    // ----------------------------------------------------------
    // RIWAYAT PESANAN
    // ----------------------------------------------------------
    elseif ($bagian1 === 'orders') {
        $controller = new OrderController();

        if ($bagian2 !== '') {
            // orders/5  -> detail pesanan
            $controller->show($bagian2);
        } else {
            // orders    -> daftar pesanan
            $controller->index();
        }
    }

    // ----------------------------------------------------------
    // HALAMAN ADMIN
    // ----------------------------------------------------------
    elseif ($bagian1 === 'admin') {
        $controller = new AdminController();

        // ----- Kelola produk -----
        if ($bagian2 === 'products') {
            if ($bagian3 === 'edit') {
                $controller->editProduct((int) $bagian4);
            } elseif ($bagian3 === 'create') {
                $controller->createProduct();
            } elseif ($bagian3 === 'update') {
                $controller->updateProduct();
            } elseif ($bagian3 === 'delete') {
                $controller->deleteProduct();
            } elseif ($bagian3 === 'restore') {
                $controller->restoreProduct();
            } else {
                $controller->products();
            }
        }

        // ----- Ubah status pesanan -----
        elseif ($bagian2 === 'orders' && $bagian3 === 'update') {
            $controller->updateOrderStatus();
        }

        // ----- Dashboard -----
        else {
            $controller->index();
        }
    }

    // ----------------------------------------------------------
    // ALAMAT TIDAK DIKENAL
    // ----------------------------------------------------------
    else {
        http_response_code(404);
        render('home/not-found', ['pageTitle' => 'Halaman Tidak Ditemukan']);
    }

} catch (Throwable $e) {
    // Ada kesalahan tak terduga di aplikasi.
    // Detailnya disimpan di log server, pengguna melihat pesan umum.
    error_log('[ZORA application] ' . $e->getMessage());

    http_response_code(500);

    echo '<!doctype html>
    <html lang="id">
    <head><meta charset="utf-8"><title>Terjadi Kesalahan</title></head>
    <body style="font-family:Arial,sans-serif;padding:40px">
        <h1>Terjadi kesalahan</h1>
        <p>Permintaan belum dapat diproses. Silakan coba kembali.</p>
        <a href="' . e(base_url('home')) . '">Kembali ke beranda</a>
    </body>
    </html>';
}
