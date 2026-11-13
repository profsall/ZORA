<?php
/**
 * Halaman edit produk admin.
 * Formulir untuk mengubah data produk yang sudah ada.
 */

// Status produk yang bisa dipilih
$pilihanStatus = [
    'active'   => 'Aktif',
    'draft'    => 'Draft',
    'archived' => 'Diarsipkan',
];

// Tandai produk sebagai unggulan atau bukan
$centangUnggulan = '';

if (!empty($product['is_featured'])) {
    $centangUnggulan = 'checked';
}
?>
<section class="container standard-page admin-page">

    <div class="page-title">
        <span class="eyebrow">ZORA MANAGEMENT</span>
        <h1>Edit produk</h1>
        <p>Perbarui informasi produk <strong><?= e($product['name']) ?></strong>.</p>
    </div>

    <div class="admin-product-layout" style="max-width:680px">

        <form class="form-card" method="post" action="<?= base_url('admin/products/update') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

            <label>Nama produk
                <input name="name" required value="<?= e($product['name']) ?>">
            </label>

            <label>SKU
                <input name="sku" required value="<?= e($product['sku']) ?>">
            </label>

            <label>Kategori
                <select name="category_id">
                    <option value="">Tanpa kategori</option>

                    <?php foreach ($categories as $c): ?>
                        <?php
                        // Tandai kategori yang sedang dipakai produk ini
                        $terpilih = '';

                        if ((int) $c['id'] === (int) ($product['category_id'] ?? 0)) {
                            $terpilih = 'selected';
                        }
                        ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $terpilih ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="form-grid">
                <label>Harga (Rp)
                    <input type="number" name="price" min="1" required
                           value="<?= (int) $product['price'] ?>">
                </label>

                <label>Harga pembanding (Rp)
                    <input type="number" name="compare_at_price" min="0"
                           value="<?= e($product['compare_at_price'] ?? '') ?>">
                </label>

                <label>Stok
                    <input type="number" name="stock" min="0"
                           value="<?= (int) $product['stock'] ?>">
                </label>

                <label>Warna
                    <input name="color" value="<?= e($product['color'] ?? '') ?>">
                </label>
            </div>

            <label>Tag produk
                <?php
                    $currentTag = strtoupper((string) ($product['tag'] ?? ''));
                ?>

                <select name="tag">
                    <option value="" <?= $currentTag === '' ? 'selected' : '' ?>>
                        Tanpa tag
                    </option>

                    <option value="NEW" <?= $currentTag === 'NEW' ? 'selected' : '' ?>>
                        NEW
                    </option>

                    <option value="POPULAR" <?= $currentTag === 'POPULAR' ? 'selected' : '' ?>>
                        POPULAR
                    </option>

                    <option value="SALE" <?= $currentTag === 'SALE' ? 'selected' : '' ?>>
                        SALE
                    </option>

                    <option value="LIMITED" <?= $currentTag === 'LIMITED' ? 'selected' : '' ?>>
                        LIMITED
                    </option>
                </select>
            </label>

            <label>URL gambar
                <input type="url" name="image_url"
                       value="<?= e($product['image_url'] ?? '') ?>"
                       placeholder="https://...">
            </label>

            <?php if (!empty($product['image_url'])): ?>
                <div style="margin-bottom:12px">
                    <small style="color:var(--muted)">Preview gambar saat ini:</small><br>
                    <img src="<?= e($product['image_url']) ?>" alt=""
                         style="max-width:200px;max-height:200px;object-fit:cover;margin-top:6px;border-radius:3px">
                </div>
            <?php endif; ?>

            <label>Deskripsi produk
                <textarea name="description" rows="4"><?= e($product['description'] ?? '') ?></textarea>
            </label>

            <label>Status produk
                <select name="status">
                    <?php foreach ($pilihanStatus as $nilai => $label): ?>
                        <?php
                        $terpilih = '';

                        if ($nilai === $product['status']) {
                            $terpilih = 'selected';
                        }
                        ?>
                        <option value="<?= e($nilai) ?>" <?= $terpilih ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="checkbox-label">
                <input type="checkbox" name="is_featured" value="1" <?= $centangUnggulan ?>>
                <span>Tampilkan sebagai produk unggulan di homepage</span>
            </label>

            <div style="display:flex;gap:12px;margin-top:8px">
                <button class="btn-zora" type="submit" style="flex:1">Simpan perubahan →</button>
                <a href="<?= base_url('admin/products') ?>" class="subtle-button" style="align-self:center">Batal</a>
            </div>
        </form>

    </div>
</section>
