
<?php

class CartController
{
    /**
     * Menampilkan keranjang.
     */
    public function index(): void
    {
        $items = Cart::items();

        render('cart/index', [
            'pageTitle' => 'Keranjang',
            'items' => $items,
            'subtotal' => Cart::subtotal()
        ]);
    }

    /**
     * Menambahkan produk ke keranjang.
     */
    public function add(): void
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $productId = (int) ($_POST['product_id'] ?? 0);

        $quantity = max(
            1,
            (int) ($_POST['quantity'] ?? 1)
        );

        try {
            if ($productId <= 0) {
                throw new InvalidArgumentException(
                    'Produk tidak valid.'
                );
            }

            Cart::add($productId, $quantity);

            flash('success', 'Produk berhasil ditambahkan ke keranjang.');

        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('products');
    }

    /**
     * Memperbarui jumlah produk.
     */
    public function update(): void
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $itemId = (int) ($_POST['item_id'] ?? 0);

        $quantity = max(
            1,
            (int) ($_POST['quantity'] ?? 1)
        );

        try {
            if ($itemId <= 0) {
                throw new InvalidArgumentException(
                    'Item keranjang tidak valid.'
                );
            }

            Cart::update($itemId, $quantity);

            flash('success', 'Keranjang berhasil diperbarui.');

        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('cart');
    }

    /**
     * Menghapus produk dari keranjang.
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
                throw new InvalidArgumentException(
                    'Item keranjang tidak valid.'
                );
            }

            Cart::remove($itemId);

            flash('success', 'Produk berhasil dihapus dari keranjang.');

        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('cart');
    }
}
