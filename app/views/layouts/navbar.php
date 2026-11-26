<?php
// Menu yang muncul di atas semua halaman.

// Nama depan pengguna yang sedang login
$namaDepan = '';

if (is_logged_in()) {
    $namaLengkap = current_user()['name'];
    $potonganNama = explode(' ', $namaLengkap);
    $namaDepan = $potonganNama[0];
}

// Tujuan tombol akun
if (is_admin()) {
    $linkAkun = base_url('admin');
} else {
    $linkAkun = base_url('account');
}
?>
<header class="site-header">
  <div class="container header-inner">

    <a class="brand" href="<?= base_url('home') ?>" aria-label="ZORA Home">ZORA<span>.</span></a>

    <button class="mobile-menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false">
      <span></span>
      <span></span>
    </button>

    <nav class="main-nav">
      <a href="<?= base_url('products') ?>">Semua Produk</a>
      <a href="<?= base_url('products?category=smartphones') ?>">Smartphones</a>
      <a href="<?= base_url('products?category=computers') ?>">Computers</a>
      <a href="<?= base_url('products?category=audio') ?>">Audio</a>
      <a href="<?= base_url('products?category=wearables') ?>">Wearables</a>
      <a href="<?= base_url('products?category=cameras') ?>">Cameras</a>
      <a href="<?= base_url('products?category=accessories') ?>">Accessories</a>
    </nav>

    <div class="header-actions">

      <?php if (is_logged_in()): ?>
        <a class="header-account" href="<?= $linkAkun ?>">
          <?= e($namaDepan) ?>
        </a>
      <?php else: ?>
        <a class="header-account" href="<?= base_url('login') ?>">Masuk</a>
      <?php endif; ?>

      <a class="header-cart" href="<?= base_url('cart') ?>" aria-label="Keranjang">
        <svg class="icon-cart" viewBox="0 0 24 24" width="19" height="19" fill="none"
             stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
             stroke-linejoin="round" aria-hidden="true">
          <circle cx="9" cy="20" r="1.4"></circle>
          <circle cx="18" cy="20" r="1.4"></circle>
          <path d="M2 3h2.2l2.4 11.2h11.6l2-8H6"></path>
        </svg>
        Keranjang <span><?= (int) $cartCount ?></span>
      </a>

    </div>
  </div>
</header>