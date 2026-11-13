<?php
/**
 * NAVBAR (MENU ATAS)
 * ==================
 * Menu navigasi yang muncul di bagian atas semua halaman.
 */

// Ambil nama depan pengguna yang sedang login
$namaDepan = '';

if (is_logged_in()) {
    $namaLengkap = current_user()['name'];
    $potonganNama = explode(' ', $namaLengkap);
    $namaDepan = $potonganNama[0];
}

// Halaman tujuan tombol akun
if (is_admin()) {
    $linkAkun = base_url('admin');
} else {
    $linkAkun = base_url('account');
}
?>
<header class="site-header">
  <div class="container header-inner">

    <!-- Logo -->
    <a class="brand" href="<?= base_url('home') ?>" aria-label="ZORA Home">ZORA<span>.</span></a>

    <!-- Tombol menu untuk layar HP -->
    <button class="mobile-menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false">
      <span></span>
      <span></span>
    </button>

    <!-- Menu utama -->
    <nav class="main-nav">
      <a href="<?= base_url('products') ?>">Semua Produk</a>
      <a href="<?= base_url('products?category=smartphones') ?>">Smartphones</a>
      <a href="<?= base_url('products?category=computers') ?>">Computers</a>
      <a href="<?= base_url('products?category=audio') ?>">Audio</a>
      <a href="<?= base_url('products?category=wearables') ?>">Wearables</a>
      <a href="<?= base_url('products?category=cameras') ?>">Cameras</a>
      <a href="<?= base_url('products?category=accessories') ?>">Accessories</a>
    </nav>

    <!-- Tombol akun dan keranjang -->
    <div class="header-actions">

      <?php if (is_logged_in()): ?>
        <a class="header-account" href="<?= $linkAkun ?>">
          <?= e($namaDepan) ?>
        </a>
      <?php else: ?>
        <a class="header-account" href="<?= base_url('login') ?>">Masuk</a>
      <?php endif; ?>

      <a class="header-cart" href="<?= base_url('cart') ?>" aria-label="Keranjang">
        Keranjang <span><?= (int) $cartCount ?></span>
      </a>

    </div>
  </div>
</header>
