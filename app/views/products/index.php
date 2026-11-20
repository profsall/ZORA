<section class="page-banner">
    <div class="container">
        <span class="eyebrow">KATALOG ZORA</span>
        <h1>Temukan perangkat yang tepat.</h1>
        <p>Jelajahi koleksi elektronik untuk kebutuhanmu.</p>
    </div>
</section>


<section class="container catalog-section">

    <!-- FORM FILTER -->
    <form class="filter-bar" method="get" action="<?= base_url('products') ?>">

        <input type="hidden" name="url" value="products">

        <label class="search-field">
            <span>⌕</span>
            <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari produk...">
        </label>

        <select name="category" aria-label="Kategori">
            <option value="">Semua kategori</option>

            <?php foreach ($categories as $c): ?>
                <?php
                // Tandai kategori yang sedang dipilih
                $terpilih = '';

                if ($filters['category'] === $c['slug']) {
                    $terpilih = 'selected';
                }
                ?>
                <option value="<?= e($c['slug']) ?>" <?= $terpilih ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="sort" aria-label="Urutkan">
            <option value="">Rekomendasi</option>

            <?php
            // Tandai urutan yang sedang dipakai
            $urutHargaNaik = '';
            $urutHargaTurun = '';
            $urutNama = '';

            if ($filters['sort'] === 'price_asc') {
                $urutHargaNaik = 'selected';
            } elseif ($filters['sort'] === 'price_desc') {
                $urutHargaTurun = 'selected';
            } elseif ($filters['sort'] === 'name') {
                $urutNama = 'selected';
            }
            ?>

            <option value="price_asc" <?= $urutHargaNaik ?>>Harga terendah</option>
            <option value="price_desc" <?= $urutHargaTurun ?>>Harga tertinggi</option>
            <option value="name" <?= $urutNama ?>>Nama A–Z</option>
        </select>

        <button class="btn-zora btn-small">Terapkan</button>
    </form>


    <!-- INFO HASIL -->
    <div class="catalog-meta">
        <span><?= count($products) ?> produk ditemukan</span>
        <a href="<?= base_url('products') ?>">Reset filter</a>
    </div>


    <!-- DAFTAR PRODUK -->
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <article class="product-card">

                <a class="product-image" href="<?= base_url('products/' . $product['slug']) ?>">
                    <img src="<?= e(product_image($product['image_url'])) ?>" alt="<?= e($product['name']) ?>">

                    <?php if ($product['tag']): ?>
                        <span class="product-tag"><?= e($product['tag']) ?></span>
                    <?php endif; ?>
                </a>

                <div class="product-info">
                    <small><?= e($product['category_name'] ?? 'Electronics') ?></small>
                    <a class="product-name" href="<?= base_url('products/' . $product['slug']) ?>">
                        <?= e($product['name']) ?>
                    </a>
                    <strong><?= rupiah($product['price']) ?></strong>
                </div>

            </article>
        <?php endforeach; ?>
    </div>


    <!-- JIKA TIDAK ADA HASIL -->
    <?php if (!$products): ?>
        <div class="empty-state compact">
            <h3>Produk belum ditemukan</h3>
            <p>Coba kata kunci atau kategori yang berbeda.</p>
            <a href="<?= base_url('products') ?>" class="text-link"> Lihat semua produk </a>
        </div>
    <?php endif; ?>

</section>
