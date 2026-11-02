<?php
class Payment
{
    public static function create(int $orderId,string $method,float $amount): int
    {
        $allowed=['bank_transfer','qris','ewallet','cod'];
        if(!in_array($method,$allowed,true)) $method='bank_transfer';
        $q=db()->prepare("INSERT INTO payments (order_id,payment_method,payment_status,amount) VALUES (?,?,'pending',?)");
        $q->execute([$orderId,$method,$amount]); return (int)db()->lastInsertId();
    }
    public static function forOrder(int $orderId): ?array
    { $q=db()->prepare('SELECT * FROM payments WHERE order_id=? ORDER BY id DESC LIMIT 1'); $q->execute([$orderId]); return $q->fetch() ?: null; }
}
