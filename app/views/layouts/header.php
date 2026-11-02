<?php $cartCount = class_exists('Cart') ? Cart::count() : 0; $success=flash('success'); $error=flash('error'); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'ZORA Electronics') ?></title>
  <meta name="description" content="ZORA — pilihan elektronik untuk keseharian, dengan pengalaman belanja yang sederhana.">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= asset('css/style.css') ?>" rel="stylesheet"><link href="<?= asset('css/responsive.css') ?>" rel="stylesheet">
  <?php if(is_admin()): ?><link href="<?= asset('css/admin.css') ?>" rel="stylesheet"><?php endif; ?>
</head>
<body>
<?php require __DIR__.'/navbar.php'; ?>
<main class="site-main">
  <div class="container"> <?php if($success): ?><div class="alert alert-success alert-dismissible fade show mt-3" role="alert"><?= e($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
  <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show mt-3" role="alert"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?></div>
