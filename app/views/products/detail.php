<?php
// Harga pembanding dipakai buat harga coret
$hargaPembanding = '';

if ($product['compare_at_price']) {
    $hargaPembanding = rupiah($product['compare_at_price']);
}

// Stok 0 artinya barang habis
$stok = (int) $product['stock'];
$adaStok = $stok > 0;
$labelStok = $adaStok ? (string) $stok : 'Sold Out';

// Warna kosong ditampilkan sebagai tanda hubung
$warna = $product['color'];

if (!$warna) {
    $warna = '—';
}
?>
<section class="container product-detail">

    <div class="detail-image">
        <img src="<?= e(product_image($product['image_url'])) ?>" alt="<?= e($product['name']) ?>">
    </div>

    <div class="detail-copy">

        <div class="breadcrumbs">
            <a href="<?= base_url('home') ?>">Beranda</a> /
            <a href="<?= base_url('products') ?>">Produk</a> /
            <?= e($product['name']) ?>
        </div>

        <span class="eyebrow"><?= e($product['category_name'] ?? 'ELECTRONICS') ?></span>
        <h1><?= e($product['name']) ?></h1>

        <div class="detail-price">
            <?= rupiah($product['price']) ?>

            <?php if ($hargaPembanding): ?>
                <del><?= $hargaPembanding ?></del>
            <?php endif; ?>
        </div>

        <p><?= nl2br(e($product['description'])) ?></p>

        <div class="detail-spec">
            <span>Warna</span>
            <strong><?= e($warna) ?></strong>

            <span>Stok</span>
            <strong><?= e($labelStok) ?></strong>
        </div>

        <?php if ($adaStok): ?>
            <form method="post" action="<?= base_url('cart/add') ?>" class="add-cart-form">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                <label>Jumlah
                    <input type="number" name="quantity" value="1" min="1" max="<?= $stok ?>">
                </label>

                <button class="btn-zora">
                    <svg class="icon-cart" viewBox="0 0 24 24" width="17" height="17" fill="none"
                         stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <circle cx="9" cy="20" r="1.4"></circle>
                        <circle cx="18" cy="20" r="1.4"></circle>
                        <path d="M2 3h2.2l2.4 11.2h11.6l2-8H6"></path>
                    </svg>
                    Tambah ke keranjang
                </button>
            </form>
        <?php else: ?>
            <button class="btn-zora" disabled>Sold Out</button>
        <?php endif; ?>

        <small class="stock-note">SKU: <?= e($product['sku']) ?></small>

    </div>
</section>