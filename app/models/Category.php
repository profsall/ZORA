<?php
// Query ke tabel categories.
class Category
{
    // Semua kategori.
    // $hanyaAktif kalau true cuma kategori aktif, false semua (dipakai admin).
    public static function all($hanyaAktif = true)
    {
        $pdo = db();

        // Jumlah produk aktif ikut dihitung
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


    // Satu kategori berdasarkan ID, null kalau nggak ada
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