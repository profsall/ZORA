<?php
// Mengelola tabel "orders" dan "order_items".
// Berisi proses paling penting: mengubah isi keranjang menjadi pesanan.
class Order
{
    // Membuat pesanan baru dari isi keranjang.
    // Mengembalikan ID pesanan yang baru dibuat. Langkah harus komplit kalau salah satu gagal itu gagal total
    public static function createFromCart($userId, $pengiriman, $metodeBayar)
    {
        $pdo = db();
        $daftarBarang = Cart::items();

        // Keranjang kosong tidak bisa di-checkout
        if (empty($daftarBarang)) {
            throw new RuntimeException('Keranjang masih kosong.');
        }

        // Hitung total belanja
        $subtotal = Cart::subtotal();
        $ongkir = hitung_ongkir($subtotal);
        $total = $subtotal + $ongkir;

        // Mulai transaksi. Semua perubahan belum permanen sampai commit().
        $pdo->beginTransaction();

        try {
            // LANGKAH 1: Periksa stok setiap barang
            // FOR UPDATE mengunci baris produk supaya tidak ada
            // pengguna lain yang membeli stok yang sama di saat bersamaan.
            $sql = "SELECT stock, status
                    FROM products
                    WHERE id = :id
                    FOR UPDATE";

            $queryStok = $pdo->prepare($sql);

            foreach ($daftarBarang as $barang) {
                $queryStok->execute(['id' => $barang['product_id']]);
                $stokProduk = $queryStok->fetch();

                // Produk sudah tidak ada
                if ($stokProduk === false) {
                    throw new RuntimeException('Stok ' . $barang['name'] . ' tidak mencukupi.');
                }

                // Produk sudah tidak dijual
                if ($stokProduk['status'] !== 'active') {
                    throw new RuntimeException('Stok ' . $barang['name'] . ' tidak mencukupi.');
                }

                // Stok kurang dari yang dipesan
                if ((int) $stokProduk['stock'] < (int) $barang['quantity']) {
                    throw new RuntimeException('Stok ' . $barang['name'] . ' tidak mencukupi.');
                }
            }

            // LANGKAH 2: Buat nomor pesanan yang unik
            // Contoh hasil: ZOR260311A1B2C3
            $nomorPesanan = 'ZOR' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));

            // LANGKAH 3: Simpan data pesanan
            $sql = "INSERT INTO orders (
                        order_number, user_id,
                        recipient_name, recipient_phone,
                        shipping_address, shipping_city,
                        shipping_province, shipping_postal_code,
                        subtotal, shipping_cost, discount_amount,
                        total_amount, order_status, customer_note
                    ) VALUES (
                        :nomor_pesanan, :user_id,
                        :nama_penerima, :telepon_penerima,
                        :alamat, :kota,
                        :provinsi, :kode_pos,
                        :subtotal, :ongkir, 0,
                        :total, 'pending', :catatan
                    )";

            $queryOrder = $pdo->prepare($sql);
            $queryOrder->execute([
                'nomor_pesanan'   => $nomorPesanan,
                'user_id'         => $userId,
                'nama_penerima'   => $pengiriman['recipient_name'],
                'telepon_penerima' => $pengiriman['recipient_phone'],
                'alamat'          => $pengiriman['address'],
                'kota'            => $pengiriman['city'],
                'provinsi'        => $pengiriman['province'],
                'kode_pos'        => $pengiriman['postal_code'],
                'subtotal'        => $subtotal,
                'ongkir'          => $ongkir,
                'total'           => $total,
                'catatan'         => $pengiriman['note'],
            ]);

            $orderId = (int) $pdo->lastInsertId();
          
            // LANGKAH 4: Simpan rincian barang dan kurangi stok
            // Nama, SKU, dan gambar disimpan sebagai salinan (snapshot).
            // Tujuannya: kalau nanti produk diubah admin, riwayat pesanan
            // lama tetap menampilkan data saat dibeli.
            $sqlItem = "INSERT INTO order_items
                            (order_id, product_id, product_name, product_sku,
                             product_image, unit_price, quantity, line_total)
                        VALUES
                            (:order_id, :product_id, :nama_produk, :sku_produk,
                             :gambar_produk, :harga_satuan, :jumlah, :total_baris)";

            $queryItem = $pdo->prepare($sqlItem);

            $sqlStok = "UPDATE products SET stock = stock - :jumlah WHERE id = :product_id";
            $queryStokKurang = $pdo->prepare($sqlStok);

            foreach ($daftarBarang as $barang) {
                $totalBaris = (float) $barang['unit_price'] * (int) $barang['quantity'];

                $queryItem->execute([
                    'order_id'      => $orderId,
                    'product_id'    => $barang['product_id'],
                    'nama_produk'   => $barang['name'],
                    'sku_produk'    => $barang['sku'],
                    'gambar_produk' => $barang['image_url'],
                    'harga_satuan'  => $barang['unit_price'],
                    'jumlah'        => $barang['quantity'],
                    'total_baris'   => $totalBaris,
                ]);

                $queryStokKurang->execute([
                    'jumlah'     => $barang['quantity'],
                    'product_id' => $barang['product_id'],
                ]);
            }

            // LANGKAH 5: Catat data pembayaran
            Payment::create($orderId, $metodeBayar, $total);

            // LANGKAH 6: Kosongkan keranjang
            // Dilakukan di dalam transaksi yang sama, jadi kalau gagal seluruh pesanan ikut dibatalkan.
            Cart::clear(true, $pdo);
          
            // LANGKAH 7: Simpan semua perubahan secara permanen
            $pdo->commit();

            return $orderId;

        } catch (Throwable $error) {
            // Ada yang gagal, batalkan semua perubahan
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Lempar ulang errornya supaya controller bisa menampilkan pesan
            throw $error;
        }
    }

    // Mengambil semua pesanan milik satu pengguna.
    public static function forUser($userId)
    {
        $pdo = db();

        $sql = "SELECT * FROM orders
                WHERE user_id = :user_id
                ORDER BY ordered_at DESC";

        $query = $pdo->prepare($sql);
        $query->execute(['user_id' => $userId]);

        return $query->fetchAll();
    }


    // Mengambil semua pesanan untuk dashboard admin. Nama pelanggan ikut diambil dari tabel users.
    public static function all()
    {
        $pdo = db();

        $sql = "SELECT
                    o.*,
                    u.name AS customer_name
                FROM orders o
                LEFT JOIN users u ON u.id = o.user_id
                ORDER BY o.ordered_at DESC";

        $query = $pdo->query($sql);
        return $query->fetchAll();
    }


    // Mengambil satu pesanan milik pengguna tertentu. user_id ikut dipakai di WHERE supaya pengguna tidak bisa membuka pesanan milik orang lain hanya dengan mengganti ID.
    public static function findForUser($id, $userId)
    {
        $pdo = db();

        $sql = "SELECT * FROM orders
                WHERE id = :id
                  AND user_id = :user_id
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute([
            'id'      => $id,
            'user_id' => $userId,
        ]);

        $hasil = $query->fetch();

        if ($hasil === false) {
            return null;
        }

        return $hasil;
    }

    // Mengambil semua barang dari satu pesanan.
    public static function items($orderId)
    {
        $pdo = db();

        $sql = "SELECT * FROM order_items WHERE order_id = :order_id";
        $query = $pdo->prepare($sql);
        $query->execute(['order_id' => $orderId]);

        return $query->fetchAll();
    }

    // Mengubah status pesanan. Kalau status diubah menjadi "cancelled", stok produk balik seperti sebelum pesanan dibuat.
    public static function updateStatus($id, $status)
    {
        // Status yang diperbolehkan
        $statusValid = ['confirmed', 'processing', 'shipped', 'completed', 'cancelled'];

        if (!in_array($status, $statusValid, true)) {
            throw new InvalidArgumentException('Status pesanan tidak valid.');
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            // 1. Ambil status pesanan saat ini (sekaligus dikunci)
            $sql = "SELECT order_status FROM orders WHERE id = :id FOR UPDATE";
            $query = $pdo->prepare($sql);
            $query->execute(['id' => $id]);
            $statusSekarang = $query->fetchColumn();

            // Pesanan tidak ditemukan
            if ($statusSekarang === false) {
                throw new RuntimeException('Pesanan tidak ditemukan.');
            }

            // Pesanan yang sudah dibatalkan tidak bisa diaktifkan lagi
            if ($statusSekarang === 'cancelled' && $status !== 'cancelled') {
                throw new RuntimeException(
                    'Pesanan yang sudah dibatalkan tidak dapat diaktifkan kembali.'
                );
            }

            // 2. Kalau dibatalkan, kembalikan stok produk
            $baruDibatalkan = ($status === 'cancelled' && $statusSekarang !== 'cancelled');

            if ($baruDibatalkan === true) {
                $sql = "SELECT product_id, quantity
                        FROM order_items
                        WHERE order_id = :order_id
                          AND product_id IS NOT NULL";

                $query = $pdo->prepare($sql);
                $query->execute(['order_id' => $id]);
                $daftarBarang = $query->fetchAll();

                $sql = "UPDATE products SET stock = stock + :jumlah WHERE id = :product_id";
                $queryStok = $pdo->prepare($sql);

                foreach ($daftarBarang as $barang) {
                    $queryStok->execute([
                        'jumlah'     => $barang['quantity'],
                        'product_id' => $barang['product_id'],
                    ]);
                }
            }

            // 3. Simpan status baru
            $sql = "UPDATE orders SET order_status = :status WHERE id = :id";
            $pdo->prepare($sql)->execute([
                'status' => $status,
                'id'     => $id,
            ]);

            $pdo->commit();

        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }
    }
}
