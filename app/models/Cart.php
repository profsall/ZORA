<?php
/**
 * Mengelola tabel "carts" dan "cart_items".
 *
 * Keranjang bisa dimiliki oleh dua jenis pengunjung:
 * - Pengunjung yang sudah login  -> dikenali dari user_id
 * - Pengunjung yang belum login  -> dikenali dari session_id
 */
class Cart
{
    // Mencari ID keranjang yang sedang dipakai. Kalau belum ada, keranjang baru langsung dibuat.
    private static function cartId()
    {
        $pdo = db();
        $userId = null;

        // Cek apakah penggunanya sedang login
        if (isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
        }

        $sessionId = session_id();

        // Cari keranjang yang sudah ada
        if ($userId !== null) {
            // Keranjang milik pengguna yang sudah login
            $sql = "SELECT id FROM carts
                    WHERE user_id = :user_id
                      AND status = 'active'
                    ORDER BY id DESC
                    LIMIT 1";
            $query = $pdo->prepare($sql);
            $query->execute(['user_id' => $userId]);
        } else {
            // Keranjang milik pengunjung yang belum login
            $sql = "SELECT id FROM carts
                    WHERE session_id = :session_id
                      AND status = 'active'
                    ORDER BY id DESC
                    LIMIT 1";
            $query = $pdo->prepare($sql);
            $query->execute(['session_id' => $sessionId]);
        }

        $cartId = $query->fetchColumn();

        // Kalau keranjang sudah ada, langsung pakai itu
        if ($cartId !== false) {
            return (int) $cartId;
        }


        // Keranjang belum ada, buat yang baru
        $sql = "INSERT INTO carts (user_id, session_id, status)
                VALUES (:user_id, :session_id, 'active')";

        $query = $pdo->prepare($sql);
        $query->execute([
            'user_id'    => $userId,
            'session_id' => $sessionId,
        ]);

        return (int) $pdo->lastInsertId();
    }


    /**
     * Mengambil semua barang di dalam keranjang.
     */
    public static function items()
    {
        $pdo = db();
        $cartId = self::cartId();

        $sql = "SELECT
                    ci.id        AS item_id,
                    ci.quantity,
                    ci.unit_price,
                    p.id         AS product_id,
                    p.name,
                    p.slug,
                    p.sku,
                    p.image_url,
                    p.stock,
                    p.status,
                    p.price
                FROM cart_items ci
                JOIN products p ON p.id = ci.product_id
                WHERE ci.cart_id = :cart_id
                ORDER BY ci.created_at DESC";

        $query = $pdo->prepare($sql);
        $query->execute(['cart_id' => $cartId]);

        return $query->fetchAll();
    }


    /**
     * Menambahkan produk ke keranjang.
     *
     * Kalau produk tersebut sudah ada di keranjang,
     * jumlahnya ditambah (bukan dibuat baris baru).
     */
    public static function add($productId, $quantity = 1)
    {
        $pdo = db();

        // Pastikan produknya ada dan masih dijual
        $produk = Product::find($productId);

        if ($produk === null) {
            throw new RuntimeException('Produk tidak tersedia.');
        }

        if ($produk['status'] !== 'active') {
            throw new RuntimeException('Produk tidak tersedia.');
        }

        // Jangan melebihi stok yang tersedia
        if ((int) $produk['stock'] < $quantity) {
            throw new RuntimeException('Stok produk tidak mencukupi.');
        }

        $cartId = self::cartId();

        // ON DUPLICATE KEY UPDATE membuat jumlah otomatis bertambah
        // kalau produk sudah ada di keranjang.
        // LEAST() memastikan jumlahnya tidak melebihi stok.
        $sql = "INSERT INTO cart_items (cart_id, product_id, quantity, unit_price)
                VALUES (:cart_id, :product_id, :quantity, :unit_price)
                ON DUPLICATE KEY UPDATE
                    quantity = LEAST(quantity + VALUES(quantity), :stock)";

        $query = $pdo->prepare($sql);
        $query->execute([
            'cart_id'    => $cartId,
            'product_id' => $productId,
            'quantity'   => $quantity,
            'unit_price' => $produk['price'],
            'stock'      => $produk['stock'],
        ]);
    }


    /**
     * Mengubah jumlah barang di keranjang.
     */
    public static function update($itemId, $quantity)
    {
        $pdo = db();
        $cartId = self::cartId();

        // Ambil data barang sekaligus memastikan barang ini
        // memang milik keranjang yang sedang dipakai.
        // Ini mencegah pengguna mengubah keranjang milik orang lain.
        $sql = "SELECT ci.quantity, p.stock
                FROM cart_items ci
                JOIN products p ON p.id = ci.product_id
                WHERE ci.id = :item_id
                  AND ci.cart_id = :cart_id";

        $query = $pdo->prepare($sql);
        $query->execute([
            'item_id' => $itemId,
            'cart_id' => $cartId,
        ]);

        $barang = $query->fetch();

        // Barang tidak ditemukan di keranjang ini
        if ($barang === false) {
            throw new RuntimeException('Item keranjang tidak ditemukan.');
        }

        // Jumlah tidak boleh melebihi stok
        if ($quantity > (int) $barang['stock']) {
            throw new RuntimeException('Jumlah melebihi stok yang tersedia.');
        }

        $sql = "UPDATE cart_items
                SET quantity = :quantity
                WHERE id = :item_id
                  AND cart_id = :cart_id";

        $query = $pdo->prepare($sql);
        $query->execute([
            'quantity' => $quantity,
            'item_id'  => $itemId,
            'cart_id'  => $cartId,
        ]);
    }


    /**
     * Menghapus satu barang dari keranjang.
     */
    public static function remove($itemId)
    {
        $pdo = db();
        $cartId = self::cartId();

        $sql = "DELETE FROM cart_items
                WHERE id = :item_id
                  AND cart_id = :cart_id";

        $query = $pdo->prepare($sql);
        $query->execute([
            'item_id' => $itemId,
            'cart_id' => $cartId,
        ]);
    }


    /**
     * Menghitung total harga semua barang di keranjang.
     */
    public static function subtotal()
    {
        $daftarBarang = self::items();
        $total = 0;

        foreach ($daftarBarang as $barang) {
            $total = $total + ((float) $barang['unit_price'] * (int) $barang['quantity']);
        }

        return $total;
    }


    /**
     * Menghitung jumlah seluruh barang di keranjang.
     * Dipakai untuk angka kecil di ikon keranjang pada navbar.
     */
    public static function count()
    {
        $daftarBarang = self::items();
        $total = 0;

        foreach ($daftarBarang as $barang) {
            $total = $total + (int) $barang['quantity'];
        }

        return $total;
    }


    /**
     * Mengosongkan keranjang.
     *
     * $ubahStatus = true -> keranjang ditandai "converted" (sudah jadi pesanan)
     * $pdo                -> koneksi yang dipakai saat transaksi checkout
     */
    public static function clear($ubahStatus = false, $pdo = null)
    {
        if ($pdo === null) {
            $pdo = db();
        }

        $cartId = self::cartId();

        // Hapus semua barang di keranjang
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
        $pdo->prepare($sql)->execute(['cart_id' => $cartId]);

        // Tandai keranjang sudah selesai dipakai
        if ($ubahStatus === true) {
            $sql = "UPDATE carts SET status = 'converted' WHERE id = :cart_id";
            $pdo->prepare($sql)->execute(['cart_id' => $cartId]);
        }
    }


    /**
     * Menggabungkan keranjang tamu ke keranjang pengguna setelah login.
     *
     * Jadi barang yang sudah dimasukkan sebelum login tidak hilang.
     */
    public static function mergeGuestCart($userId, $sessionIdLama)
    {
        $pdo = db();

        // ------------------------------------------------------------
        // 1. Cari keranjang tamu berdasarkan session ID yang lama
        // ------------------------------------------------------------
        $sql = "SELECT id FROM carts
                WHERE session_id = :session_id
                  AND status = 'active'
                  AND user_id IS NULL
                ORDER BY id DESC
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(['session_id' => $sessionIdLama]);
        $guestCartId = $query->fetchColumn();

        // Tidak ada keranjang tamu, tidak perlu apa-apa
        if ($guestCartId === false) {
            return;
        }

        // ------------------------------------------------------------
        // 2. Cari keranjang milik pengguna
        // ------------------------------------------------------------
        $sql = "SELECT id FROM carts
                WHERE user_id = :user_id
                  AND status = 'active'
                ORDER BY id DESC
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(['user_id' => $userId]);
        $userCartId = $query->fetchColumn();

        // ------------------------------------------------------------
        // 3a. Pengguna belum punya keranjang -> ambil alih keranjang tamu
        // ------------------------------------------------------------
        if ($userCartId === false) {
            $sql = "UPDATE carts
                    SET user_id = :user_id, session_id = :session_id
                    WHERE id = :cart_id";

            $query = $pdo->prepare($sql);
            $query->execute([
                'user_id'    => $userId,
                'session_id' => session_id(),
                'cart_id'    => $guestCartId,
            ]);

            return;
        }

        // ------------------------------------------------------------
        // 3b. Pengguna sudah punya keranjang -> pindahkan semua barangnya
        // ------------------------------------------------------------
        $sql = "SELECT product_id, quantity, unit_price
                FROM cart_items
                WHERE cart_id = :cart_id";

        $query = $pdo->prepare($sql);
        $query->execute(['cart_id' => $guestCartId]);
        $barangTamu = $query->fetchAll();

        foreach ($barangTamu as $barang) {
            // Kalau produknya sudah ada, jumlahnya ditambah
            $sql = "INSERT INTO cart_items (cart_id, product_id, quantity, unit_price)
                    VALUES (:cart_id, :product_id, :quantity, :unit_price)
                    ON DUPLICATE KEY UPDATE
                        quantity = quantity + VALUES(quantity)";

            $pdo->prepare($sql)->execute([
                'cart_id'    => $userCartId,
                'product_id' => $barang['product_id'],
                'quantity'   => $barang['quantity'],
                'unit_price' => $barang['unit_price'],
            ]);
        }

        // ------------------------------------------------------------
        // 4. Tandai keranjang tamu sudah tidak dipakai
        // ------------------------------------------------------------
        $sql = "UPDATE carts SET status = 'abandoned' WHERE id = :cart_id";
        $pdo->prepare($sql)->execute(['cart_id' => $guestCartId]);
    }
}