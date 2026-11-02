<?php
class Category
{
    public static function all(bool $activeOnly = true): array
    {
        $sql = 'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.status=\'active\') product_count FROM categories c';
        if ($activeOnly) $sql .= ' WHERE c.is_active=1';
        return db()->query($sql . ' ORDER BY c.sort_order,c.name')->fetchAll();
    }
    public static function find(int $id): ?array
    {
        $q=db()->prepare('SELECT * FROM categories WHERE id=?'); $q->execute([$id]); return $q->fetch() ?: null;
    }
}
