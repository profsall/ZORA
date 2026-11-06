<section class="container standard-page admin-page">
    <div class="page-title">
        <span class="eyebrow">ZORA MANAGEMENT</span>
        <h1>Kelola produk</h1>
        <p>Tambah produk baru atau arsipkan produk yang tidak lagi dijual.</p>
    </div>

    <div class="admin-product-layout">
        <form class="form-card" method="post" action="<?= base_url('admin/products/create') ?>">
            <?= csrf_field() ?>
            <h3>Tambah produk</h3>
            
            <label>Nama produk
                <input name="name" required>
            </label>
            <label>SKU
                <input name="sku" required>
            </label>
            <label>Kategori
                <select name="category_id">
                    <option value="">Tanpa kategori</option>
                    <?php foreach($categories as$c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            
            <div class="form-grid">
                <label>Harga
                    <input type="number" name="price" min="1" required>
                </label>
                <label>Harga pembanding
                    <input type="number" name="compare_at_price" min="0">
                </label>
                <label>Stok
                    <input type="number" name="stock" min="0" value="0">
                </label>
                <label>Warna
                    <input name="color">
                </label>
            </div>

            <label>URL gambar
                <input type="url" name="image_url" placeholder="https://...">
            </label>