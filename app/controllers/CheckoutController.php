
<?php

class CheckoutController
{
    /**
     * Menampilkan halaman checkout.
     */
    public function index(): void
    {
        require_login();

        $items = Cart::items();

        if (!$items) {
            flash('error', 'Keranjang masih kosong.');
            redirect('products');
        }

        $subtotal = Cart::subtotal();

        $shippingCost = $subtotal >= 500000
            ? 0
            : 15000;

        render('checkout/index', [
            'pageTitle' => 'Checkout',
            'items' => $items,
            'subtotal' => $subtotal,
            'shippingCost' => $shippingCost
        ]);
    }

    /**
     * Memproses pesanan pelanggan.
     */
    public function store(): void
    {
        require_login();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $errors = [];

        $shipping = [
            'recipient_name' => $this->postString('recipient_name'),
            'recipient_phone' => $this->postString('recipient_phone'),
            'address' => $this->postString('address'),
            'city' => $this->postString('city'),
            'province' => $this->postString('province'),
            'postal_code' => $this->postString('postal_code'),
            'note' => $this->postString('note')
        ];

        // Validasi data penerima
        $requiredFields = [
            'recipient_name',
            'recipient_phone',
            'address',
            'city'
        ];

        foreach ($requiredFields as $field) {
            if (!required_value($shipping[$field])) {
                $errors[] = 'Nama penerima, nomor telepon, alamat, dan kota wajib diisi.';
                break;
            }
        }

        // Validasi nomor telepon
        if (
            $shipping['recipient_phone'] !== '' &&
            !preg_match('/^[0-9+\-\s]{8,20}$/', $shipping['recipient_phone'])
        ) {
            $errors[] = 'Nomor telepon tidak valid.';
        }

        // Validasi metode pembayaran
        $paymentMethod = $this->postString(
            'payment_method',
            'bank_transfer'
        );

        $allowedPaymentMethods = [
            'bank_transfer',
            'qris',
            'ewallet',
            'cod'
        ];

        if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
            $errors[] = 'Metode pembayaran tidak valid.';
        }

        $items = Cart::items();
        $subtotal = Cart::subtotal();

        $shippingCost = $subtotal >= 500000
            ? 0
            : 15000;

        if (!$items) {
            flash('error', 'Keranjang masih kosong.');
            redirect('products');
        }

        // Jika validasi gagal, kembali ke halaman checkout.
        if ($errors) {
            render('checkout/index', [
                'pageTitle' => 'Checkout',
                'items' => $items,
                'subtotal' => $subtotal,
                'shippingCost' => $shippingCost,
                'errors' => array_unique($errors)
            ]);

            return;
        }

        try {
            $userId = (int) current_user()['id'];

            $orderId = Order::createFromCart(
                $userId,
                $shipping,
                $paymentMethod
            );

            $order = Order::findForUser(
                $orderId,
                $userId
            );

            if (!$order) {
                throw new RuntimeException(
                    'Pesanan tidak ditemukan setelah dibuat.'
                );
            }

            render('checkout/success', [
                'pageTitle' => 'Pesanan Berhasil',
                'order' => $order
            ]);

        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('cart');

        } catch (Throwable $e) {
            error_log($e->getMessage());

            flash(
                'error',
                'Terjadi kesalahan ketika memproses pesanan.'
            );

            redirect('cart');
        }
    }

    /**
     * Mengambil input POST berupa string.
     */
    private function postString(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_string($value)
            ? trim($value)
            : $default;
    }
}
