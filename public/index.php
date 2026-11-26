<?php
// Titik masuk aplikasi: muat file pendukung, baca URL, panggil controller.

// Session dipakai untuk login dan keranjang
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/validation.php';

// Class model dan controller dicari otomatis, jadi nggak perlu require manual
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

// Cek koneksi database sebelum lanjut
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

// Baca URL, contohnya index.php?url=admin/products/edit/5
$isiUrl = '';

if (isset($_GET['url'])) {
    $isiUrl = $_GET['url'];
}

// Kalau yang dikirim bukan teks, anggap kosong
if (!is_string($isiUrl)) {
    $isiUrl = '';
}

$alamat = trim($isiUrl, '/');

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

if ($bagian1 === '') {
    $bagian1 = 'home';
}

// URL cuma boleh huruf, angka, tanda hubung, dan garis miring
if ($alamat !== '') {
    $polaAman = '~^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$~';

    if (!preg_match($polaAman, $alamat)) {
        http_response_code(404);
        render('home/not-found', ['pageTitle' => 'Halaman Tidak Ditemukan']);
        exit;
    }
}

try {
    // Beranda
    if ($bagian1 === 'home') {
        $controller = new HomeController();
        $controller->index();
    }

    // Katalog dan detail produk
    elseif ($bagian1 === 'products') {
        $controller = new ProductController();

        if ($bagian2 !== '') {
            $controller->show($bagian2);
        } else {
            $controller->index();
        }
    }

    // Login, daftar, logout
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

    // Keranjang
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

    // Checkout
    elseif ($bagian1 === 'checkout') {
        $controller = new CheckoutController();

        if (is_post()) {
            $controller->store();
        } else {
            $controller->index();
        }
    }

    // Halaman akun
    elseif ($bagian1 === 'account') {
        require_login();

        render('account/index', ['pageTitle' => 'Akun Saya']);
    }

    // Riwayat pesanan
    elseif ($bagian1 === 'orders') {
        $controller = new OrderController();

        if ($bagian2 !== '') {
            $controller->show($bagian2);
        } else {
            $controller->index();
        }
    }

    // Halaman admin
    elseif ($bagian1 === 'admin') {
        $controller = new AdminController();

        // Kelola produk
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

        // Ubah status pesanan
        elseif ($bagian2 === 'orders' && $bagian3 === 'update') {
            $controller->updateOrderStatus();
        }

        // Dashboard
        else {
            $controller->index();
        }
    }

    // Alamat nggak dikenal
    else {
        http_response_code(404);
        render('home/not-found', ['pageTitle' => 'Halaman Tidak Ditemukan']);
    }

} catch (Throwable $e) {
    // Detail error masuk log, pengunjung cuma lihat pesan umum
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