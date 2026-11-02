<?php
class Product
{
    public static function all(array $filters = []): array
    {
        $where = ["p.status='active'"]; $params=[];
        if (!empty($filters['q'])) { $where[]='(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)'; $like='%'.$filters['q'].'%'; array_push($params,$like,$like,$like); }
        if (!empty($filters['category'])) { $where[]='c.slug=?'; $params[]=$filters['category']; }
        if (isset($filters['min_price']) && $filters['min_price'] !== '') { $where[]='p.price>=?'; $params[]=(float)$filters['min_price']; }
        if (isset($filters['max_price']) && $filters['max_price'] !== '') { $where[]='p.price<=?'; $params[]=(float)$filters['max_price']; }
        $order = match($filters['sort'] ?? '') { 'price_asc'=>'p.price ASC', 'price_desc'=>'p.price DESC', 'name'=>'p.name ASC', default=>'p.is_featured DESC,p.created_at DESC' };
        $sql='SELECT p.*,c.name category_name,c.slug category_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE '.implode(' AND ',$where).' ORDER BY '.$order;
        $q=db()->prepare($sql); $q->execute($params); return $q->fetchAll();
    }
    public static function featured(int $limit = 4): array
    {
        $q=db()->prepare("SELECT p.*,c.name category_name,c.slug category_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.status='active' AND p.is_featured=1 ORDER BY p.created_at DESC LIMIT ?");
        $q->bindValue(1,$limit,PDO::PARAM_INT); $q->execute(); return $q->fetchAll();
    }
    public static function findBySlug(string $slug): ?array
    {
        $q=db()->prepare("SELECT p.*,c.name category_name,c.slug category_slug FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.slug=? AND p.status='active' LIMIT 1");
        $q->execute([$slug]); return $q->fetch() ?: null;
    }
    public static function find(int $id): ?array
    {
        $q=db()->prepare('SELECT p.*,c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?');
        $q->execute([$id]); return $q->fetch() ?: null;
    }
    public static function adminAll(): array
    { return db()->query('SELECT p.*,c.name category_name FROM products p LEFT JOIN categories c ON c.id=p.category_id ORDER BY p.created_at DESC')->fetchAll(); }
    public static function create(array $d): int
    {
        $slug=slugify($d['name']);
        $q=db()->prepare('INSERT INTO products (category_id,name,slug,sku,description,price,compare_at_price,stock,color,tag,image_url,status,is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$d['category_id'] ?: null,$d['name'],$slug.'-'.strtolower(bin2hex(random_bytes(2))),$d['sku'],$d['description'],$d['price'],$d['compare_at_price'] ?: null,$d['stock'],$d['color'],$d['tag'] ?: null,$d['image_url'],'active',!empty($d['is_featured'])?1:0]);
        return (int)db()->lastInsertId();
    }
    public static function delete(int $id): void
    { db()->prepare("UPDATE products SET status='archived' WHERE id=?")->execute([$id]); }
}
