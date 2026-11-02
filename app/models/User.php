<?php
class User
{
    public static function findByEmail(string $email): ?array
    {
        $q = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1'); $q->execute([$email]);
        return $q->fetch() ?: null;
    }
    public static function find(int $id): ?array
    {
        $q = db()->prepare('SELECT id,name,email,phone,role,status,created_at FROM users WHERE id=?'); $q->execute([$id]);
        return $q->fetch() ?: null;
    }
    public static function create(array $data): int
    {
        $q = db()->prepare('INSERT INTO users (name,email,phone,password,role,status) VALUES (?,?,?,?,\'customer\',\'active\')');
        $q->execute([$data['name'], strtolower($data['email']), $data['phone'] ?: null, password_hash($data['password'], PASSWORD_DEFAULT)]);
        return (int)db()->lastInsertId();
    }
    public static function authenticate(string $email, string $password): ?array
    {
        $user = self::findByEmail(strtolower(trim($email)));
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password'])) return null;
        db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$user['id']]);
        unset($user['password']);
        return $user;
    }
    public static function updateProfile(int $id, string $name, string $phone): void
    {
        db()->prepare('UPDATE users SET name=?,phone=? WHERE id=?')->execute([$name, $phone ?: null, $id]);
    }
}
