<?php
// Mengelola data tabel "categories".
// Model adalah bagian yang berhubungan langsung dengan database.
class Category
{
    // Mengambil semua kategori.
    // $hanyaAktif = true  -> hanya kategori yang aktif saja
    // $hanyaAktif = false -> semua kategori (dipakai di halaman admin)
    public static function all($hanyaAktif = true)
    {
        $pdo = db();

        // Query utama: ambil kategori beserta jumlah produknya
        $sql = "SELECT
                    c.id,
                    c.name,
                    c.slug,
                    c.description,
                    c.is_active,
                    c.sort_order,
                    (
                        SELECT COUNT(*)
                        FROM products p
                        WHERE p.category_id = c.id
                          AND p.status = 'active'
                    ) AS product_count
                FROM categories c";

        if ($hanyaAktif === true) {
            $sql = $sql . " WHERE c.is_active = 1";
        }

        $sql = $sql . " ORDER BY c.sort_order ASC, c.name ASC";

        $data = $pdo->query($sql);
        return $data->fetchAll();
    }


    // Mengambil satu kategori berdasarkan ID. Hasilnya null kalau kategori tidak ditemukan.
    public static function find($id)
    {
        $pdo = db();

        $sql = "SELECT * FROM categories WHERE id = :id LIMIT 1";
        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id]);

        $hasil = $query->fetch();

        if ($hasil === false) {
            return null;
        }

        return $hasil;
    }
}
