<?php
// Keranjang bisa milik tamu (session_id) atau member yang login (user_id).
class Cart
{
    // Ambil keranjang aktif, buat baru kalau belum ada
    private static function cartId()
    {
        $pdo = db();
        $userId = null;

        if (isset($_SESSION['user']['id'])) {
            $userId = $_SESSION['user']['id'];
        }

        $sessionId = session_id();

        if ($userId !== null) {
            $sql = "SELECT id FROM carts
                    WHERE user_id = :user_id
                      AND status = 'active'
                    ORDER BY id DESC
                    LIMIT 1";
            $query = $pdo->prepare($sql);
            $query->execute(['user_id' => $userId]);
        } else {
            $sql = "SELECT id FROM carts
                    WHERE session_id = :session_id
                      AND status = 'active'
                    ORDER BY id DESC
                    LIMIT 1";
            $query = $pdo->prepare($sql);
            $query->execute(['session_id' => $sessionId]);
        }

        $cartId = $query->fetchColumn();

        if ($cartId !== false) {
            return (int) $cartId;
        }

        // Belum ada keranjang, bikin yang baru
        $sql = "INSERT INTO carts (user_id, session_id, status)
                VALUES (:user_id, :session_id, 'active')";

        $query = $pdo->prepare($sql);
        $query->execute([
            'user_id'    => $userId,
            'session_id' => $sessionId,
        ]);

        return (int) $pdo->lastInsertId();
    }


    // Semua barang yang ada di keranjang
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


    // Produk yang sudah ada di keranjang cuma ditambah jumlahnya
    public static function add($productId, $quantity = 1)
    {
        $pdo = db();

        $produk = Product::find($productId);

        if ($produk === null) {
            throw new RuntimeException('Produk tidak tersedia.');
        }

        if ($produk['status'] !== 'active') {
            throw new RuntimeException('Produk tidak tersedia.');
        }

        if ((int) $produk['stock'] < $quantity) {
            throw new RuntimeException('Stok produk tidak mencukupi.');
        }

        $cartId = self::cartId();

        // LEAST() biar jumlahnya nggak lewat dari stok
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


    // Ubah jumlah barang di keranjang
    public static function update($itemId, $quantity)
    {
        $pdo = db();
        $cartId = self::cartId();

        // Filter cart_id penting, biar nggak bisa ubah keranjang orang lain
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

        if ($barang === false) {
            throw new RuntimeException('Item keranjang tidak ditemukan.');
        }

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


    // Hapus satu barang dari keranjang
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


    // Total harga semua barang
    public static function subtotal()
    {
        $daftarBarang = self::items();
        $total = 0;

        foreach ($daftarBarang as $barang) {
            $total = $total + ((float) $barang['unit_price'] * (int) $barang['quantity']);
        }

        return $total;
    }


    // Jumlah barang buat angka kecil di ikon keranjang
    public static function count()
    {
        $daftarBarang = self::items();
        $total = 0;

        foreach ($daftarBarang as $barang) {
            $total = $total + (int) $barang['quantity'];
        }

        return $total;
    }


    // $ubahStatus = true kalau keranjang sudah jadi pesanan
    public static function clear($ubahStatus = false, $pdo = null)
    {
        if ($pdo === null) {
            $pdo = db();
        }

        $cartId = self::cartId();

        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";
        $pdo->prepare($sql)->execute(['cart_id' => $cartId]);

        if ($ubahStatus === true) {
            $sql = "UPDATE carts SET status = 'converted' WHERE id = :cart_id";
            $pdo->prepare($sql)->execute(['cart_id' => $cartId]);
        }
    }


    // Pindahkan keranjang tamu ke akun, dipanggil setelah login
    public static function mergeGuestCart($userId, $sessionIdLama)
    {
        $pdo = db();

        // Cari keranjang tamu dari session lama
        $sql = "SELECT id FROM carts
                WHERE session_id = :session_id
                  AND status = 'active'
                  AND user_id IS NULL
                ORDER BY id DESC
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(['session_id' => $sessionIdLama]);
        $guestCartId = $query->fetchColumn();

        if ($guestCartId === false) {
            return;
        }

        // Cari keranjang milik pengguna
        $sql = "SELECT id FROM carts
                WHERE user_id = :user_id
                  AND status = 'active'
                ORDER BY id DESC
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(['user_id' => $userId]);
        $userCartId = $query->fetchColumn();

        // Belum punya keranjang, keranjang tamu diambil alih
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

        // Sudah punya keranjang, barang tamu dipindah ke situ
        $sql = "SELECT product_id, quantity, unit_price
                FROM cart_items
                WHERE cart_id = :cart_id";

        $query = $pdo->prepare($sql);
        $query->execute(['cart_id' => $guestCartId]);
        $barangTamu = $query->fetchAll();

        foreach ($barangTamu as $barang) {
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

        $sql = "UPDATE carts SET status = 'abandoned' WHERE id = :cart_id";
        $pdo->prepare($sql)->execute(['cart_id' => $guestCartId]);
    }
}