<?php
// Data tambahan untuk halaman detail pesanan.
// Status pesanan dalam Bahasa Indonesia
$labelStatus = label_status_pesanan($order['order_status']);

// Data pembayaran mungkin belum ada
if ($payment) {
    $namaMetode = label_metode_pembayaran($payment['payment_method']);
    $statusBayar = $payment['payment_status'];
} else {
    $namaMetode = '-';
    $statusBayar = '-';
}
?>
<section class="container standard-page">

    <div class="page-title">
        <span class="eyebrow">DETAIL PESANAN</span>
        <h1><?= e($order['order_number']) ?></h1>
        <p>Status: <span class="status-pill"><?= e($labelStatus) ?></span></p>
    </div>

    <div class="order-detail-grid">

        <!-- Barang & Alamat -->
        <div class="form-card">

            <h3>Produk dipesan</h3>

            <?php foreach ($items as $item): ?>
                <div class="cart-item">
                    <img src="<?= e(product_image($item['product_image'])) ?>" alt="">

                    <div class="cart-item-info">
                        <strong><?= e($item['product_name']) ?></strong>
                        <small><?= (int) $item['quantity'] ?> × <?= rupiah($item['unit_price']) ?></small>
                    </div>

                    <strong><?= rupiah($item['line_total']) ?></strong>
                </div>
            <?php endforeach; ?>

            <h3 class="form-heading-spaced">Alamat pengiriman</h3>
            <p>
                <?= e($order['recipient_name']) ?> · <?= e($order['recipient_phone']) ?><br>
                <?= nl2br(e($order['shipping_address'])) ?><br>
                <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?>
            </p>
        </div>

        <!-- Ringkasan -->
        <aside class="summary-card">
            <h3>Ringkasan</h3>

            <div>
                <span>Subtotal</span>
                <strong><?= rupiah($order['subtotal']) ?></strong>
            </div>

            <div>
                <span>Pengiriman</span>
                <strong><?= rupiah($order['shipping_cost']) ?></strong>
            </div>

            <div class="summary-total">
                <span>Total</span>
                <strong><?= rupiah($order['total_amount']) ?></strong>
            </div>

            <p class="summary-hint">
                Pembayaran: <?= e($namaMetode) ?><br>
                Status pembayaran: <?= e($statusBayar) ?>
            </p>
        </aside>

    </div>

    <a href="<?= base_url('orders') ?>" class="text-link">← Kembali ke pesanan</a>

</section>
