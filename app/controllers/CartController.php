<?php

class CartController
{
    /**
     * Menampilkan halaman keranjang.
     */
    public function index(): void
    {
        $items = Cart::items();

        render('cart/index', [
            'pageTitle' => 'Keranjang',
            'items'     => $items,
            'subtotal'  => Cart::subtotal(),
        ]);
    }

    /**
     * Menambahkan produk ke keranjang.
     *
     * UX FIX (H10): Sebelumnya selalu redirect ke halaman daftar produk,
     * sehingga user yang menambah produk dari halaman detail kehilangan konteks.
     *
     * FIX: Redirect kembali ke HTTP_REFERER jika referrer berasal dari
     * domain yang sama dan path valid. Fallback ke halaman produk.
     */
    public function add(): void
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $productId = (int) ($_POST['product_id'] ?? 0);
        $quantity  = max(1, (int) ($_POST['quantity'] ?? 1));

        try {
            if ($productId <= 0) {
                throw new InvalidArgumentException('Produk tidak valid.');
            }

            Cart::add($productId, $quantity);

            flash('success', 'Produk berhasil ditambahkan ke keranjang.');

        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        // Redirect kembali ke halaman asal jika referrer valid.
        $redirectTo = $this->safeReferer('products');
        redirect($redirectTo);
    }

    /**
     * Memperbarui kuantitas item di keranjang.
     */
    public function update(): void
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $itemId   = (int) ($_POST['item_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        try {
            if ($itemId <= 0) {
                throw new InvalidArgumentException('Item keranjang tidak valid.');
            }

            Cart::update($itemId, $quantity);

            flash('success', 'Keranjang berhasil diperbarui.');

        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('cart');
    }

    /**
     * Menghapus item dari keranjang.
     */
    public function remove(): void
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $itemId = (int) ($_POST['item_id'] ?? 0);

        try {
            if ($itemId <= 0) {
                throw new InvalidArgumentException('Item keranjang tidak valid.');
            }

            Cart::remove($itemId);

            flash('success', 'Produk berhasil dihapus dari keranjang.');

        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('cart');
    }

    /**
     * Mengambil route dari HTTP_REFERER untuk mengembalikan pelanggan
     * ke halaman asal setelah menambahkan produk.
     *
     * Bekerja dengan URL tanpa .htaccess:
     * /ZORA/public/index.php?url=products/nova-phone-x
     */
    private function safeReferer(string $fallback): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if ($referer === '') {
            return $fallback;
        }

        $refererHost = strtolower(
            (string) parse_url($referer, PHP_URL_HOST)
        );

        $currentHost = strtolower(
            (string) parse_url(
                'http://' . ($_SERVER['HTTP_HOST'] ?? ''),
                PHP_URL_HOST
            )
        );

        if ($refererHost === '' || $refererHost !== $currentHost) {
            return $fallback;
        }

        $refererPath = parse_url($referer, PHP_URL_PATH) ?? '';
        $expectedPath = public_base_path() . '/index.php';

        if ($refererPath !== $expectedPath) {
            return $fallback;
        }

        $queryString = parse_url($referer, PHP_URL_QUERY) ?? '';
        parse_str($queryString, $params);

        $route = $params['url'] ?? '';

        if (!is_string($route) || $route === '') {
            return $fallback;
        }

        $route = trim($route, '/');

        // Hanya izinkan karakter route sederhana; hindari path traversal.
        if (!preg_match('~^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$~', $route)) {
            return $fallback;
        }

        unset($params['url']);

        $extraQuery = http_build_query($params);

        return $route . ($extraQuery !== '' ? '?' . $extraQuery : '');
    }
}