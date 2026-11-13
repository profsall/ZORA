<?php
/**
 * HEADER HALAMAN
 * ==============
 * Bagian atas setiap halaman: judul, CSS, dan navbar.
 * File ini otomatis dipakai oleh fungsi render().
 */

// Hitung jumlah barang di keranjang untuk angka di navbar
$cartCount = 0;

if (class_exists('Cart')) {
    $cartCount = Cart::count();
}

// Ambil pesan yang tersimpan dari halaman sebelumnya
$success = flash('success');
$error = flash('error');

// Deskripsi halaman untuk mesin pencari
// Controller bisa mengirim $metaDescription sendiri kalau perlu
if (!isset($metaDescription)) {
    $metaDescription = 'ZORA — pilihan elektronik untuk keseharian, dengan pengalaman belanja yang sederhana.';
}

// Judul tab browser
$judulHalaman = 'ZORA Electronics';

if (isset($pageTitle)) {
    $judulHalaman = $pageTitle;
}

// Waktu perubahan file CSS, dipakai supaya browser selalu ambil versi terbaru
$lokasiStyle = __DIR__ . '/../../../public/assets/css/style.css';
$lokasiResponsive = __DIR__ . '/../../../public/assets/css/responsive.css';
$lokasiAdmin = __DIR__ . '/../../../public/assets/css/admin.css';

$versiStyle = is_file($lokasiStyle) ? filemtime($lokasiStyle) : time();
$versiResponsive = is_file($lokasiResponsive) ? filemtime($lokasiResponsive) : time();
$versiAdmin = is_file($lokasiAdmin) ? filemtime($lokasiAdmin) : time();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($judulHalaman) ?> — ZORA</title>

    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="index, follow">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- CSS ZORA -->
    <link href="<?= asset('css/style.css') ?>?v=<?= $versiStyle ?>" rel="stylesheet">
    <link href="<?= asset('css/responsive.css') ?>?v=<?= $versiResponsive ?>" rel="stylesheet">

    <?php if (is_admin()): ?>
        <link href="<?= asset('css/admin.css') ?>?v=<?= $versiAdmin ?>" rel="stylesheet">
    <?php endif; ?>
</head>
<body>

<?php require __DIR__ . '/navbar.php'; ?>

<main class="site-main">
    <div class="container">

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                <?= e($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <?= e($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        <?php endif; ?>

    </div>