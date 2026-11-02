<?php
class Order
{
    public static function createFromCart(int $userId, array $shipping, string $paymentMethod): int
    {
        $pdo=db(); $items=Cart::items();
        if (!$items) throw new RuntimeException('Keranjang masih kosong.');
        $subtotal=Cart::subtotal(); $shippingCost=$subtotal>=500000?0:15000; $total=$subtotal+$shippingCost;
        $pdo->beginTransaction();
        try {
            foreach($items as $item) {
                $check=$pdo->prepare("SELECT stock,status FROM products WHERE id=? FOR UPDATE"); $check->execute([$item['product_id']]); $stock=$check->fetch();
                if (!$stock || $stock['status']!=='active' || (int)$stock['stock'] < (int)$item['quantity']) throw new RuntimeException('Stok '.$item['name'].' tidak mencukupi.');
            }
            $number='ZOR'.date('ymd').strtoupper(bin2hex(random_bytes(3)));
            $q=$pdo->prepare("INSERT INTO orders (order_number,user_id,recipient_name,recipient_phone,shipping_address,shipping_city,shipping_province,shipping_postal_code,subtotal,shipping_cost,discount_amount,total_amount,order_status,customer_note) VALUES (?,?,?,?,?,?,?,?,?,?,0,?,'pending',?)");
            $q->execute([$number,$userId,$shipping['recipient_name'],$shipping['recipient_phone'],$shipping['address'],$shipping['city'],$shipping['province'],$shipping['postal_code'],$subtotal,$shippingCost,$total,$shipping['note']]);
            $orderId=(int)$pdo->lastInsertId();
            $ins=$pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,product_sku,product_image,unit_price,quantity,line_total) VALUES (?,?,?,?,?,?,?,?)');
            $upd=$pdo->prepare('UPDATE products SET stock=stock-? WHERE id=?');
            foreach($items as $item) {
                $line=(float)$item['unit_price']*(int)$item['quantity'];
                $ins->execute([$orderId,$item['product_id'],$item['name'],$item['sku'] ?? null,$item['image_url'],$item['unit_price'],$item['quantity'],$line]);
                $upd->execute([$item['quantity'],$item['product_id']]);
            }
            Payment::create($orderId,$paymentMethod,$total);
            // Item keranjang dibersihkan dan cart ditandai converted setelah transaksi tersimpan.
            $pdo->commit();
            Cart::clear(true);
            return $orderId;
        } catch(Throwable $e) { if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    public static function forUser(int $userId): array
    { $q=db()->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY ordered_at DESC'); $q->execute([$userId]); return $q->fetchAll(); }
    public static function all(): array
    { return db()->query('SELECT o.*,u.name customer_name FROM orders o LEFT JOIN users u ON u.id=o.user_id ORDER BY o.ordered_at DESC')->fetchAll(); }
    public static function findForUser(int $id,int $userId): ?array
    { $q=db()->prepare('SELECT * FROM orders WHERE id=? AND user_id=?'); $q->execute([$id,$userId]); return $q->fetch() ?: null; }
    public static function items(int $orderId): array
    { $q=db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $q->execute([$orderId]); return $q->fetchAll(); }
    public static function updateStatus(int $id,string $status): void
    {
        $allowed=['confirmed','processing','shipped','completed','cancelled'];
        if(!in_array($status,$allowed,true)) throw new InvalidArgumentException('Status tidak valid.');
        $pdo=db();
        $pdo->beginTransaction();
        try {
            $q=$pdo->prepare('SELECT order_status FROM orders WHERE id=? FOR UPDATE');
            $q->execute([$id]); $current=$q->fetchColumn();
            if (!$current) throw new RuntimeException('Pesanan tidak ditemukan.');
            if ($current === 'cancelled' && $status !== 'cancelled') throw new RuntimeException('Pesanan yang sudah dibatalkan tidak dapat diaktifkan kembali.');
            if ($status === 'cancelled' && $current !== 'cancelled') {
                $items=$pdo->prepare('SELECT product_id,quantity FROM order_items WHERE order_id=? AND product_id IS NOT NULL');
                $items->execute([$id]);
                $restore=$pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?');
                foreach ($items->fetchAll() as $item) $restore->execute([$item['quantity'],$item['product_id']]);
            }
            $pdo->prepare('UPDATE orders SET order_status=? WHERE id=?')->execute([$status,$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
