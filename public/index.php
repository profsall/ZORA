<?php
declare(strict_types=1);

session_start();

// 1. Konfigurasi database dan helper.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/validation.php';

// 2. Autoload Model dan Controller berdasarkan nama class.
spl_autoload_register(static function (string $class): void {
    $directories = [
        __DIR__ . '/../app/models/',
        __DIR__ . '/../app/controllers/',
    ];

    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';

        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// 3. Periksa koneksi database.
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
        <p>Pastikan MySQL aktif dan database <strong>pemweb-teori</strong>
        sudah dibuat serta dikonfigurasi dengan benar di
        <code>config/database.php</code>.</p>
    </body>
    </html>';
    exit;
}

// 4. Ambil route dari Query String.
// Contoh: index.php?url=admin/products/edit/5
$urlValue = $_GET['url'] ?? '';

$path = is_string($urlValue)
    ? trim($urlValue, '/')
    : '';

$parts = ($path === '')
    ? []
    : explode('/', $path);

$first  = $parts[0] ?? 'home';
$second = $parts[1] ?? '';
$third  = $parts[2] ?? '';
$fourth = $parts[3] ?? '';

// Tolak route dengan segmen kosong atau karakter yang tidak diizinkan.
if (
    $path !== '' &&
    !preg_match('~^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$~', $path)
) {
    http_response_code(404);
    render('home/not-found', [
        'pageTitle' => 'Halaman Tidak Ditemukan',
    ]);
    exit;
}

// 5. Routing aplikasi.
try {
    switch ($first) {
        // Homepage.
        case 'home':
            (new HomeController())->index();
            break;

        // Katalog dan detail produk.
        case 'products':
            $productController = new ProductController();

            if ($second !== '') {
                $productController->show($second);
            } else {
                $productController->index();
            }
            break;

        // Authentication.
        case 'login':
            (new AuthController())->login();
            break;

        case 'register':
            (new AuthController())->register();
            break;

        case 'logout':
            (new AuthController())->logout();
            break;

        // Cart.
        case 'cart':
            $cartController = new CartController();

            switch ($second) {
                case 'add':
                    $cartController->add();
                    break;

                case 'update':
                    $cartController->update();
                    break;

                case 'remove':
                    $cartController->remove();
                    break;

                default:
                    $cartController->index();
                    break;
            }
            break;

        // Checkout.
        case 'checkout':
            $checkoutController = new CheckoutController();

            if (is_post()) {
                $checkoutController->store();
            } else {
                $checkoutController->index();
            }
            break;

        // Account.
        case 'account':
            require_login();

            render('account/index', [
                'pageTitle' => 'Akun Saya',
            ]);
            break;

        // Order history and detail.
        case 'orders':
            $orderController = new OrderController();

            if ($second !== '') {
                $orderController->show($second);
            } else {
                $orderController->index();
            }
            break;

        // Admin.
        case 'admin':
            $adminController = new AdminController();

            if ($second === 'products') {
                if ($third === 'edit') {
                    $adminController->editProduct((int) $fourth);
                } elseif ($third === 'create') {
                    $adminController->createProduct();
                } elseif ($third === 'update') {
                    $adminController->updateProduct();
                } elseif ($third === 'delete') {
                    $adminController->deleteProduct();
                } elseif ($third === 'restore') {
                    $adminController->restoreProduct();
                } else {
                    $adminController->products();
                }
            } elseif ($second === 'orders' && $third === 'update') {
                $adminController->updateOrderStatus();
            } else {
                $adminController->index();
            }
            break;

        // Route tidak dikenal.
        default:
            http_response_code(404);

            render('home/not-found', [
                'pageTitle' => 'Halaman Tidak Ditemukan',
            ]);
            break;
    }
} catch (Throwable $e) {
    error_log('[ZORA application] ' . $e->getMessage());

    http_response_code(500);

    // Tidak memanggil view errors/500 karena file tersebut
    // tidak tersedia pada struktur proyek saat ini.
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