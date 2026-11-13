<?php
/**
 * Informasi tambahan untuk halaman detail produk.
 */

// Tampilkan harga coret hanya kalau ada harga pembanding
$hargaPembanding = '';

if ($product['compare_at_price']) {
    $hargaPembanding = rupiah($product['compare_at_price']);
}

// Cek ketersediaan stok
$adaStok = (int) $product['stock'] > 0;

if ($adaStok) {
    $labelStok = 'Tersedia';
} else {
    $labelStok = 'Stok habis';
}

// Warna produk, tampilkan tanda hubung kalau kosong
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

            <span>Ketersediaan</span>
            <strong><?= $labelStok ?></strong>
        </div>

        <?php if ($adaStok): ?>
            <form method="post" action="<?= base_url('cart/add') ?>" class="add-cart-form">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                <label>Jumlah
                    <input type="number" name="quantity" value="1" min="1" max="<?= (int) $product['stock'] ?>">
                </label>

                <button class="btn-zora">Tambah ke keranjang <span>→</span></button>
            </form>
        <?php else: ?>
            <button class="btn-zora" disabled>Stok habis</button>
        <?php endif; ?>

        <small class="stock-note">SKU: <?= e($product['sku']) ?></small>

    </div>
</section>
