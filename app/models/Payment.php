<?php
/**
 * MODEL PEMBAYARAN
 * ================
 * Mengelola data tabel "payments".
 */
class Payment
{
    /**
     * Mencatat pembayaran baru untuk sebuah pesanan.
     * Status awalnya selalu "pending" (menunggu pembayaran).
     */
    public static function create($orderId, $metode, $jumlah)
    {
        // Metode pembayaran yang diperbolehkan
        $metodeValid = ['bank_transfer', 'qris', 'ewallet', 'cod'];

        // Kalau metode tidak dikenal, pakai transfer bank
        if (!in_array($metode, $metodeValid, true)) {
            $metode = 'bank_transfer';
        }

        $pdo = db();

        $sql = "INSERT INTO payments
                    (order_id, payment_method, payment_status, amount)
                VALUES
                    (:order_id, :metode, 'pending', :jumlah)";

        $query = $pdo->prepare($sql);
        $query->execute([
            'order_id' => $orderId,
            'metode'   => $metode,
            'jumlah'   => $jumlah,
        ]);

        return (int) $pdo->lastInsertId();
    }


    /**
     * Mengambil data pembayaran terbaru dari sebuah pesanan.
     * Hasilnya null kalau belum ada data pembayaran.
     */
    public static function forOrder($orderId)
    {
        $pdo = db();

        $sql = "SELECT * FROM payments
                WHERE order_id = :order_id
                ORDER BY id DESC
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(['order_id' => $orderId]);

        $hasil = $query->fetch();

        if ($hasil === false) {
            return null;
        }

        return $hasil;
    }
}