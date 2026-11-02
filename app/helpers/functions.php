<?php
function base_url(string $path = ''): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($base === '.' || $base === '/') $base = '';
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string { return base_url('assets/' . ltrim($path, '/')); }
function redirect(string $path): void { header('Location: ' . base_url($path)); exit; }
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function rupiah(mixed $amount): string { return 'Rp ' . number_format((float)$amount, 0, ',', '.'); }
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'produk';
}
function flash(string $key, mixed $value = null): mixed
{
    if (func_num_args() > 1) { $_SESSION['_flash'][$key] = $value; return null; }
    $result = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $result;
}
function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . '/../views/' . $view . '.php';
    if (!is_file($viewFile)) { http_response_code(500); exit('View tidak ditemukan.'); }
    require __DIR__ . '/../views/layouts/header.php';
    require $viewFile;
    require __DIR__ . '/../views/layouts/footer.php';
}
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void
{
    $sent = (string)($_POST['_token'] ?? '');
    if (!$sent || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
}
function old(string $key, string $default = ''): string { return e($_POST[$key] ?? $default); }
function product_image(?string $image): string
{
    return $image ?: 'https://placehold.co/800x900/eaf1f9/174a82?text=ZORA';
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
