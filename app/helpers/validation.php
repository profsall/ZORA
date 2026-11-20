<?php
// Fungsi untuk memeriksa apakah data yang dikirim pengguna sudah sesuai aturan sebelum disimpan ke database.
/**
 * Memeriksa apakah format email sudah benar.
 *
 * Contoh benar : budi@gmail.com
 * Contoh salah : budi@gmail
 */
function valid_email($email)
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    return true;
}

// Memeriksa panjang password minimal 8 karakter.
function valid_password($password)
{
    return strlen($password) >= 8;
}

// Memeriksa apakah input wajib sudah diisi.
// Spasi saja dianggap kosong.
function required_value($nilai)
{
    return trim((string) $nilai) !== '';
}

// Memeriksa apakah URL gambar menggunakan http atau https.
function valid_image_url($url)
{
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    $skema = parse_url($url, PHP_URL_SCHEME);

    if ($skema === false || $skema === null) {
        return false;
    }

    $skema = strtolower($skema);

    return $skema === 'http' || $skema === 'https';
}
