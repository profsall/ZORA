<?php
// Query ke tabel users: daftar akun, login, dan profil.
class User
{
    // Cari pengguna lewat email, null kalau nggak terdaftar
    public static function findByEmail($email)
    {
        $pdo = db();

        $email = strtolower(trim($email));

        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $query = $pdo->prepare($sql);
        $query->execute(['email' => $email]);

        $hasil = $query->fetch();

        if ($hasil === false) {
            return null;
        }

        return $hasil;
    }

    // Data pengguna berdasarkan ID, kolom password sengaja nggak diambil
    public static function find($id)
    {
        $pdo = db();

        $sql = "SELECT id, name, email, phone, role, status, created_at
                FROM users
                WHERE id = :id
                LIMIT 1";

        $query = $pdo->prepare($sql);
        $query->execute(['id' => $id]);

        $hasil = $query->fetch();

        if ($hasil === false) {
            return null;
        }

        return $hasil;
    }


    // Daftar akun baru, role selalu customer dan password di-hash
    public static function create($nama, $email, $telepon, $password)
    {
        $pdo = db();

        $sql = "INSERT INTO users (name, email, phone, password, role, status)
                VALUES (:nama, :email, :telepon, :password, 'customer', 'active')";

        $query = $pdo->prepare($sql);
        $query->execute([
            'nama'     => $nama,
            'email'    => strtolower($email),
            'telepon'  => $telepon,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return (int) $pdo->lastInsertId();
    }

    // Cek email dan password saat login, balikin data pengguna kalau cocok
    public static function authenticate($email, $password)
    {
        $pengguna = self::findByEmail($email);

        if ($pengguna === null) {
            return null;
        }

        // Akun non-aktif nggak boleh login
        if ($pengguna['status'] !== 'active') {
            return null;
        }

        if (!password_verify($password, $pengguna['password'])) {
            return null;
        }

        // Catat waktu login terakhir
        $pdo = db();
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = :id";
        $pdo->prepare($sql)->execute(['id' => $pengguna['id']]);

        // Hash password nggak perlu ikut ke session
        unset($pengguna['password']);

        return $pengguna;
    }
}