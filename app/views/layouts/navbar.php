<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="<?= base_url('home') ?>" aria-label="ZORA Home">ZORA<span>.</span></a>
    <button class="mobile-menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false"><span></span><span></span></button>
    <nav class="main-nav">
      <a href="<?= base_url('products') ?>">Semua Produk</a><a href="<?= base_url('products?category=smartphones') ?>">Smartphone</a><a href="<?= base_url('products?category=computers') ?>">Komputer</a><a href="<?= base_url('products?category=audio') ?>">Audio</a>
    </nav>
    <div class="header-actions">
      <a class="header-icon" href="<?= base_url('products') ?>" aria-label="Cari"><i class="bi bi-search"></i><span class="icon-fallback">⌕</span></a>
      <?php if(is_logged_in()): ?><a class="header-account" href="<?= base_url(is_admin()?'admin':'account') ?>"><?= e(explode(' ',current_user()['name'])[0]) ?></a><?php else: ?><a class="header-account" href="<?= base_url('login') ?>">Masuk</a><?php endif; ?>
      <a class="header-cart" href="<?= base_url('cart') ?>" aria-label="Keranjang">Tas <span><?= (int)$cartCount ?></span></a>
    </div>
  </div>
</header>
