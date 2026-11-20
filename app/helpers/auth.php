<?php
 // Kumpulan fungsi untuk memeriksa siapa yang sedang login dan membatasi halaman tertentu.


// Mengambil data pengguna yang sedang login. Hasilnya berupa array data user, atau null kalau belum login.
function current_user()
{
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    return null;
}


// Memeriksa apakah pengunjung sudah login
function is_logged_in()
{
    if (!isset($_SESSION['user']['id'])) {
        return false;
    }

    return !empty($_SESSION['user']['id']);
}


// Memeriksa apakah pengguna yang login adalah admin.
function is_admin()
{
    if (!is_logged_in()) {
        return false;
    }

    if (!isset($_SESSION['user']['role'])) {
        return false;
    }

    return $_SESSION['user']['role'] === 'admin';
}


// Memaksa pengguna untuk login dulu. Kalau belum login, otomatis dipindahkan ke halaman login.
function require_login()
{
    if (!is_logged_in()) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect('login');
    }
}

// Memaksa pengguna untuk login sebagai admin. Kalau bukan admin, muncul halaman "akses ditolak".
function require_admin()
{
    // Pastikan sudah login dulu
    require_login();

    // Kalau bukan admin, tolak aksesnya
    if (!is_admin()) {
        http_response_code(403);
        render('home/not-found', ['pageTitle' => 'Akses ditolak']);
        exit;
    }
}
