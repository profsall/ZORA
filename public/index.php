<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/helpers/validation.php';

spl_autoload_register(function(string $class): void {
    foreach ([__DIR__.'/../app/models/'.$class.'.php', __DIR__.'/../app/controllers/'.$class.'.php'] as $file) {
        if (is_file($file)) { require_once $file; return; }
    }
});

try { db(); } catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>Koneksi database ZORA gagal</h1><p>Pastikan MySQL aktif, database <strong>zora</strong> sudah diimpor, dan konfigurasi pada <code>config/database.php</code> benar.</p>';
    error_log($e->getMessage()); exit;
}

$path=trim((string)($_GET['url']??''),'/');
$parts=$path===''?[]:explode('/',$path);
$first=$parts[0]??'home'; $second=$parts[1]??''; $method=$_SERVER['REQUEST_METHOD']??'GET';
try {
    switch ($first) {
        case '': case 'home': (new HomeController())->index(); break;
        case 'products': $controller=new ProductController(); if($second!=='') $controller->show($second); else $controller->index(); break;
        case 'login': (new AuthController())->login(); break;
        case 'register': (new AuthController())->register(); break;
        case 'logout': (new AuthController())->logout(); break;
        case 'cart': $c=new CartController(); if($second==='add')$c->add();elseif($second==='update')$c->update();elseif($second==='remove')$c->remove();else $c->index(); break;
        case 'checkout': $c=new CheckoutController(); if(is_post())$c->store();else $c->index(); break;
        case 'account': require_login(); render('account/index',['pageTitle'=>'Akun Saya']); break;
        case 'orders': $c=new OrderController(); if($second!=='')$c->show($second);else $c->index(); break;
        case 'admin': $c=new AdminController(); if($second==='products') { if(($parts[2]??'')==='create')$c->createProduct();elseif(($parts[2]??'')==='delete')$c->deleteProduct();else $c->products(); } elseif($second==='orders' && ($parts[2]??'')==='update')$c->updateOrderStatus();else $c->index(); break;
        default: http_response_code(404); render('home/not-found',['pageTitle'=>'Halaman tidak ditemukan']);
    }
} catch (Throwable $e) {
    error_log($e->getMessage()); http_response_code(500);
    echo '<main style="font-family:Arial;padding:40px"><h1>Terjadi kesalahan</h1><p>Permintaan belum dapat diproses. Periksa konfigurasi dan log PHP.</p></main>';
}
