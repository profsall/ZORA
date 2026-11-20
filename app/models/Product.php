<?php
// Mengelola data tabel "products". Dipakai untuk katalog, pencarian, detail produk, dan halaman admin.
class Product
{
    /**
     * Mengambil produk yang aktif untuk ditampilkan di katalog.
     *
     * $filter bisa berisi:
     *     'q'        -> kata pencarian
     *     'category' -> slug kategori
     *     'sort'     -> urutan tampil
     */
    public static function all($filter = [])
    {
        $pdo = db();

        // Kumpulkan kondisi WHERE dan nilainya
        $kondisi = ["p.status = 'active'"];
        $nilai = [];

        // Filter 1: kata pencarian (mencari di nama, deskripsi, dan SKU)
        if (isset($filter['q']) && $filter['q'] !== '') {
            $kondisi[] = "(p.name LIKE :cari_nama
                        OR p.description LIKE :cari_deskripsi
                        OR p.sku LIKE :cari_sku)";

            $kataKunci = '%' . $filter['q'] . '%';

            $nilai['cari_nama'] = $kataKunci;
            $nilai['cari_deskripsi'] = $kataKunci;
            $nilai['cari_sku'] = $kataKunci;
        }

        // Filter 2: kategori
        if (isset($filter['category']) && $filter['category'] !== '') {
            $kondisi[] = "c.slug = :kategori";
            $nilai['kategori'] = $filter['category'];
        }

        // Urutan tampil produk
        // Nilai sort sudah disaring di controller, jadi aman dipakai langsung.
        $urutan = 'p.is_featured DESC, p.created_at DESC';

        if (isset($filter['sort'])) {
            if ($filter['sort'] === 'price_asc') {
                $urutan = 'p.price ASC';
            } elseif ($filter['sort'] === 'price_desc') {
                $urutan = 'p.price DESC';
            } elseif ($filter['sort'] === 'name') {
                $urutan = 'p.name ASC';
            }
        }

        // Susun query lengkap
        $sql = "SELECT
                    p.*,
                    c.name AS category_name,
                    c.slug AS category_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE " . implode(' AND ', $kondisi) . "
                ORDER BY " . $urutan;

        $query = $pdo->prepare($sql);
        $query->execute($nilai);

        return $query->fetchAll();
    }

    // Mengambil produk unggulan untuk ditampilkan di halaman beranda.
    public static function featured($jumlah = 4)
    {
        $pdo = db();

        $sql = "SELECT
                    p.*,
                    c.name AS category_name,
                    c.slug AS category_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.status = 'active'
                  AND p.is_featured = 1
                ORDER BY p.created_at DESC
                LIMIT :jumlah";

        $query = $pdo->prepare($sql);

        // LIMIT harus dikirim sebagai angka, bukan teks
        $query->bindValue('jumlah', $jumlah, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll();
    }


    // Mencari satu produk aktif berdasarkan slug. Dipakai di halaman detail produk.
    public static function findBySlug($slug)
    {
        $pdo = db();

        $sql = "SELECT
                    p.*,
                    c.name AS category_name,
                    c.slug AS category_slug
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.slug = :slug
                  AND p.status = 'active'
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(['slug' => $slug]);

        $hasil = $query->fetch();

        if ($hasil === false) {
            return null;
        }

        return $hasil;
    }


    // Mencari satu produk berdasarkan ID (semua status). Dipakai di halaman admin supaya produk arsip tetap bisa dibuka.
    public static function find($id)
    {
        $pdo = db();

        $sql = "SELECT
                    p.*,
                    c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.id = :id
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id]);

        $hasil = $query->fetch();

        if ($hasil === false) {
            return null;
        }

        return $hasil;
    }


    // Mengambil semua produk untuk halaman admin. Termasuk produk yang masih draft dan sudah diarsipkan.
    public static function adminAll()
    {
        $pdo = db();

        $sql = "SELECT
                    p.*,
                    c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                ORDER BY p.created_at DESC";

        $query = $pdo->query($sql);
        return $query->fetchAll();
    }
    // Crete produk baru. Mengembalikan ID produk yang baru dibuat.
    public static function create($data)
    {
        $pdo = db();

        // Buat alamat (slug) dari nama produk, pastikan belum dipakai
        $slug = self::buatSlugUnik($data['name']);

        $sql = "INSERT INTO products
                    (category_id, name, slug, sku, description, price,
                     compare_at_price, stock, color, tag, image_url,
                     status, is_featured)
                VALUES
                    (:category_id, :name, :slug, :sku, :description, :price,
                     :compare_at_price, :stock, :color, :tag, :image_url,
                     'active', :is_featured)";

        $query = $pdo->prepare($sql);
        $query->execute([
            'category_id'      => $data['category_id'],
            'name'             => $data['name'],
            'slug'             => $slug,
            'sku'              => $data['sku'],
            'description'      => $data['description'],
            'price'            => $data['price'],
            'compare_at_price' => $data['compare_at_price'],
            'stock'            => $data['stock'],
            'color'            => $data['color'],
            'tag'              => $data['tag'],
            'image_url'        => $data['image_url'],
            'is_featured'      => $data['is_featured'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    // Update data produk.
    public static function update($id, $data)
    {
        $pdo = db();

        // Buat slug baru dari nama produk, pastikan bentroknya dengan produk LAIN.
        // Produk ini sendiri tidak dihitung, jadi simpan nama lama untuk perbandingan.
        $slug = self::buatSlugUnikUntukEdit($id, $data['name']);

        $sql = "UPDATE products SET
                    category_id      = :category_id,
                    name             = :name,
                    slug             = :slug,
                    sku              = :sku,
                    description      = :description,
                    price            = :price,
                    compare_at_price = :compare_at_price,
                    stock            = :stock,
                    color            = :color,
                    tag              = :tag,
                    image_url        = :image_url,
                    status           = :status,
                    is_featured      = :is_featured
                WHERE id = :id";

        $query = $pdo->prepare($sql);
        $query->execute([
            'category_id'      => $data['category_id'],
            'name'             => $data['name'],
            'slug'             => $slug,
            'sku'              => $data['sku'],
            'description'      => $data['description'],
            'price'            => $data['price'],
            'compare_at_price' => $data['compare_at_price'],
            'stock'            => $data['stock'],
            'color'            => $data['color'],
            'tag'              => $data['tag'],
            'image_url'        => $data['image_url'],
            'status'           => $data['status'],
            'is_featured'      => $data['is_featured'],
            'id'               => $id,
        ]);
    }


    // Mengarsipkan produk (bukan dihapus permanen). Produk arsip tidak muncul lagi di katalog.
    public static function delete($id)
    {
        $pdo = db();

        $sql = "UPDATE products SET status = 'archived' WHERE id = :id";
        $pdo->prepare($sql)->execute(['id' => $id]);
    }

    // Mengaktifkan kembali produk yang sudah diarsipkan.
    public static function restore($id)
    {
        $pdo = db();

        $sql = "UPDATE products SET status = 'active' WHERE id = :id";
        $pdo->prepare($sql)->execute(['id' => $id]);
    }

    // Membuat slug yang belum dipakai produk lain.
    // Contoh: "Nova Phone" sudah ada, maka produk baru
    // akan mendapat slug "nova-phone-2", "nova-phone-3", dan seterusnya.
    private static function buatSlugUnik($namaProduk)
    {
        $slugDasar = slugify($namaProduk);
        $slug = $slugDasar;
        $angka = 2;

        // Ulangi selama slug masih dipakai produk lain
        while (self::slugSudahDipakai($slug)) {
            $slug = $slugDasar . '-' . $angka;
            $angka = $angka + 1;
        }

        return $slug;
    }

    // Membuat slug untuk proses edit produk.
    // Beda dengan buatSlugUnik(), di sini produk yang sedang diedit
    // tidak dihitung sebagai pemakai slug. Jadi kalau nama produk
    // tidak berubah, slug lamanya tetap dipertahankan.
    private static function buatSlugUnikUntukEdit($idProduk, $namaProduk)
    {
        $slugDasar = slugify($namaProduk);
        $slug = $slugDasar;
        $angka = 2;

        // Ulangi selama slug masih dipakai produk LAIN
        while (self::slugDipakaiProdukLain($slug, $idProduk)) {
            $slug = $slugDasar . '-' . $angka;
            $angka = $angka + 1;
        }

        return $slug;
    }

    // Memeriksa apakah slug sudah dipakai produk lain.
    // Produk dengan ID $idProduk sendiri tidak dihitung.
    private static function slugDipakaiProdukLain($slug, $idProduk)
    {
        $pdo = db();

        $sql = "SELECT COUNT(*) FROM products
                WHERE slug = :slug
                  AND id <> :id";

        $query = $pdo->prepare($sql);
        $query->execute([
            'slug' => $slug,
            'id'   => $idProduk,
        ]);

        $jumlah = (int) $query->fetchColumn();

        return $jumlah > 0;
    }

    // Memeriksa apakah slug sudah dipakai produk lain.
    private static function slugSudahDipakai($slug)
    {
        $pdo = db();

        $sql = "SELECT COUNT(*) FROM products WHERE slug = :slug";
        $query = $pdo->prepare($sql);
        $query->execute(['slug' => $slug]);

        $jumlah = (int) $query->fetchColumn();

        return $jumlah > 0;
    }
}
