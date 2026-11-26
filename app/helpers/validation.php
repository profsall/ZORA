<?php
// Cek data dari pengguna sebelum masuk database.

// Format email, contoh benar budi@gmail.com
function valid_email($email)
{
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return false;
    }

    return true;
}

// Password minimal 8 karakter
function valid_password($password)
{
    return strlen($password) >= 8;
}

// Input wajib diisi, spasi doang dianggap kosong
function required_value($nilai)
{
    return trim((string) $nilai) !== '';
}

// URL gambar harus http atau https
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