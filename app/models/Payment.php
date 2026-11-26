<?php
// Query ke tabel payments.
class Payment
{
    // Catat pembayaran baru, statusnya selalu "pending" (menunggu)
    public static function create($orderId, $metode, $jumlah)
    {
        $metodeValid = ['bank_transfer', 'qris', 'ewallet', 'cod'];

        // Metode aneh dianggap transfer bank
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

    // Pembayaran terbaru dari satu pesanan, null kalau belum ada
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