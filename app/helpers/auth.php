<?php
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool { return !empty($_SESSION['user']['id']); }
function is_admin(): bool { return is_logged_in() && (($_SESSION['user']['role'] ?? '') === 'admin'); }
function require_login(): void
{
    if (!is_logged_in()) { flash('error', 'Silakan login terlebih dahulu.'); redirect('login'); }
}
function require_admin(): void
{
    require_login();
    if (!is_admin()) { http_response_code(403); render('home/not-found', ['pageTitle' => 'Akses ditolak']); exit; }
}
