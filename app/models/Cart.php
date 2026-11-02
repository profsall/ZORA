<?php
class Cart
{
    private static function cartId(): int
    {
        $pdo=db(); $userId=current_user()['id'] ?? null; $sessionId=session_id();
        if ($userId) {
            $q=$pdo->prepare("SELECT id FROM carts WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1"); $q->execute([$userId]);
        } else {
            $q=$pdo->prepare("SELECT id FROM carts WHERE session_id=? AND status='active' ORDER BY id DESC LIMIT 1"); $q->execute([$sessionId]);
        }
        $id=$q->fetchColumn();
        if ($id) return (int)$id;
        $q=$pdo->prepare('INSERT INTO carts (user_id,session_id,status) VALUES (?,?,\'active\')'); $q->execute([$userId,$sessionId]);
        return (int)$pdo->lastInsertId();
    }
    public static function items(): array
    {
        $id=self::cartId();
        $q=db()->prepare('SELECT ci.id item_id,ci.quantity,ci.unit_price,p.id product_id,p.name,p.slug,p.sku,p.image_url,p.stock,p.status,p.price FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.cart_id=? ORDER BY ci.created_at DESC');
        $q->execute([$id]); return $q->fetchAll();
    }
    public static function add(int $productId, int $quantity = 1): void
    {
        $p=Product::find($productId);
        if (!$p || $p['status']!=='active') throw new RuntimeException('Produk tidak tersedia.');
        if ((int)$p['stock'] < $quantity) throw new RuntimeException('Stok produk tidak mencukupi.');
        $id=self::cartId();
        $q=db()->prepare('INSERT INTO cart_items (cart_id,product_id,quantity,unit_price) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=LEAST(quantity+VALUES(quantity),?)');
        $q->execute([$id,$productId,$quantity,$p['price'],$p['stock']]);
    }
    public static function update(int $itemId, int $quantity): void
    {
        $items=self::items(); foreach($items as $item) if ((int)$item['item_id']===$itemId) {
            if ($quantity > (int)$item['stock']) throw new RuntimeException('Jumlah melebihi stok yang tersedia.');
            db()->prepare('UPDATE cart_items SET quantity=? WHERE id=?')->execute([$quantity,$itemId]); return;
        }
        throw new RuntimeException('Item keranjang tidak ditemukan.');
    }
    public static function remove(int $itemId): void
    {
        $id=self::cartId(); db()->prepare('DELETE FROM cart_items WHERE id=? AND cart_id=?')->execute([$itemId,$id]);
    }
    public static function subtotal(): float
    { $sum=0; foreach(self::items() as $item) $sum += (float)$item['unit_price']*(int)$item['quantity']; return $sum; }
    public static function count(): int
    { $sum=0; foreach(self::items() as $item) $sum += (int)$item['quantity']; return $sum; }
    public static function clear(bool $convert = false): void
    {
        $id=self::cartId(); db()->prepare('DELETE FROM cart_items WHERE cart_id=?')->execute([$id]);
        if ($convert) db()->prepare("UPDATE carts SET status='converted' WHERE id=?")->execute([$id]);
    }
    public static function mergeGuestCart(int $userId, string $oldSessionId): void
    {
        $pdo=db();
        $q=$pdo->prepare("SELECT id FROM carts WHERE session_id=? AND status='active' AND user_id IS NULL ORDER BY id DESC LIMIT 1"); $q->execute([$oldSessionId]); $guestId=$q->fetchColumn();
        if (!$guestId) return;
        $q=$pdo->prepare("SELECT id FROM carts WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1"); $q->execute([$userId]); $userCart=$q->fetchColumn();
        if (!$userCart) { $pdo->prepare('UPDATE carts SET user_id=?,session_id=? WHERE id=?')->execute([$userId,session_id(),$guestId]); return; }
        $items=$pdo->prepare('SELECT product_id,quantity,unit_price FROM cart_items WHERE cart_id=?'); $items->execute([$guestId]);
        foreach($items->fetchAll() as $item) {
            $ins=$pdo->prepare('INSERT INTO cart_items (cart_id,product_id,quantity,unit_price) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity)');
            $ins->execute([$userCart,$item['product_id'],$item['quantity'],$item['unit_price']]);
        }
        $pdo->prepare("UPDATE carts SET status='abandoned' WHERE id=?")->execute([$guestId]);
    }
}
