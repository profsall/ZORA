<section class="container standard-page admin-page">
    <div class="page-title">
        <span class="eyebrow">ZORA MANAGEMENT</span>
        <h1>Dashboard admin</h1>
        <p>Ringkasan aktivitas toko ZORA.</p>
    </div>

    <div class="admin-stats">
        <div>
            <small>Produk aktif</small>
            <strong><?= number_format($stats['products']) ?></strong>
        </div>
        <div>
            <small>Total pesanan</small>
            <strong><?= number_format($stats['orders']) ?></strong>
        </div>
        <div>
            <small>Pelanggan</small>
            <strong><?= number_format($stats['customers']) ?></strong>
        </div>
        <div>
            <small>Omzet pesanan terkonfirmasi</small>
            <strong><?= rupiah($stats['revenue']) ?></strong>
        </div>
    </div>

    <div class="admin-toolbar">
        <h3>Pesanan terbaru</h3>
        <a class="btn-zora btn-small" href="<?= base_url('admin/products') ?>">Kelola produk →</a>
    </div>

    <div class="table-responsive">
        <table class="table zora-table">
            <thead>
                <tr>
                    <th>Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Ubah status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $o): ?>
                    <tr>
                        <td><?= e($o['order_number']) ?></td>
                        <td><?= e($o['customer_name']??'Pelanggan') ?></td>
                        <td><?= rupiah($o['total_amount']) ?></td>
                        <td><?= e($o['order_status']) ?></td>
                        <td>
                            <form class="status-form" method="post" action="<?= base_url('admin/orders/update') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                <select name="status">
                                    <option value="confirmed">Confirmed</option>
                                    <option value="processing">Processing</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <button class="btn-zora btn-small">Simpan</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>