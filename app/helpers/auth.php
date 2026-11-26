<?php
// Cek siapa yang sedang login dan siapa yang boleh buka halaman tertentu.


// Data pengguna yang login, null kalau belum login
function current_user()
{
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    return null;
}


// Cek pengunjung sudah login atau belum
function is_logged_in()
{
    if (!isset($_SESSION['user']['id'])) {
        return false;
    }

    return !empty($_SESSION['user']['id']);
}


// Cek pengguna yang login adalah admin
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


// Wajib login dulu, kalau belum langsung dilempar ke halaman login
function require_login()
{
    if (!is_logged_in()) {
        flash('error', 'Silakan login terlebih dahulu.');
        redirect('login');
    }
}

// Wajib login sebagai admin, kalau bukan dapat halaman akses ditolak
function require_admin()
{
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        render('home/not-found', ['pageTitle' => 'Akses ditolak']);
        exit;
    }
}