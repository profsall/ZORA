<?php
// Mengelola data tabel "users". Dipakai untuk daftar akun, login, dan profil.
class User
{
    // Mencari pengguna berdasarkan email. Hasilnya null kalau email tidak terdaftar.
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

    // Mengambil data pengguna berdasarkan ID. Password (hash) sengaja tidak diambil demi keamanan.
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


    // Mendaftarkan pengguna baru. Role selalu "customer" dan status "active".
    // Password diubah dulu menjadi hash memakai password_hash().
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

    // Memeriksa email dan password saat login.
    // Hasilnya data pengguna kalau berhasil, atau null kalau gagal.
    public static function authenticate($email, $password)
    {
        $pengguna = self::findByEmail($email);

        // Kalau email tidak terdaftar, login gagal
        if ($pengguna === null) {
            return null;
        }

        // Kalau akun tidak aktif, login gagal
        if ($pengguna['status'] !== 'active') {
            return null;
        }

        // Cocokkan password yang diketik dengan hash di database
        if (!password_verify($password, $pengguna['password'])) {
            return null;
        }

        // Catat waktu login terakhir
        $pdo = db();
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = :id";
        $pdo->prepare($sql)->execute(['id' => $pengguna['id']]);

        // Hapus hash password sebelum disimpan ke session
        unset($pengguna['password']);

        return $pengguna;
    }
}
