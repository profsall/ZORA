<?php
/**
 * KONEKSI DATABASE
 * ================
 * File ini menghubungkan aplikasi PHP dengan database MySQL.
 * Semua file lain yang butuh database cukup memanggil fungsi db().
 */

// 1. Pengaturan database (sesuaikan dengan komputer kamu)
$dbHost = '127.0.0.1';   // lokasi server MySQL
$dbName = 'zora';        // nama database
$dbUser = 'root';        // username MySQL (bawaan XAMPP)
$dbPass = '';            // password MySQL (bawaan XAMPP kosong)


/*
 * db()
 * Membuka koneksi ke database.
 * Cara pakai:
 *     $pdo = db();
 *     $hasil = $pdo->query("SELECT * FROM products");
 */
function db()
{
    // Variabel global agar nilai dari luar fungsi bisa dipakai di sini
    global $dbHost, $dbName, $dbUser, $dbPass;
    // Menyimpan koneksi supaya tidak dibuka berulang-ulang
    static $koneksi = null;
    // Kalau koneksi sudah pernah dibuat, pakai yang itu saja
    if ($koneksi !== null) {
        return $koneksi;
    }

    // Alamat lengkap server database
    $alamat = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';
    // Membuat koneksi baru dengan PDO
    $koneksi = new PDO($alamat, $dbUser, $dbPass);

    // Pengaturan PDO:
    // - Kalau ada error, langsung dilempar sebagai exception
    //$koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // - Hasil query berbentuk array dengan nama kolom sebagai kunci
    //$koneksi->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // - Matikan emulasi supaya prepared statement benar-benar asli dari MySQL
    //   (ini yang membuat query aman dari SQL Injection)
    //$koneksi->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $koneksi;
}
