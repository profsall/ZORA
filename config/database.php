<?php
// Koneksi database pakai PDO.
// Sesuaikan host, nama database, dan user di bawah kalau beda.
$dbHost = '127.0.0.1';   // lokasi server MySQL
$dbName = 'zora';        // nama database
$dbUser = 'root';        // username MySQL (bawaan XAMPP)
$dbPass = '';            // password MySQL (bawaan XAMPP kosong)


// Fungsi koneksi dipakai semua model, contohnya: $pdo = db();
function db()
{
    global $dbHost, $dbName, $dbUser, $dbPass;

    // Koneksi disimpan supaya nggak dibuka ulang tiap query
    static $koneksi = null;

    if ($koneksi !== null) {
        return $koneksi;
    }

    $alamat = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';
    $koneksi = new PDO($alamat, $dbUser, $dbPass);

    // Error langsung jadi exception
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Hasil query berupa array dengan nama kolom sebagai kunci
    $koneksi->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Emulasi dimatikan biar prepared statement asli dari MySQL
    $koneksi->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $koneksi;
}