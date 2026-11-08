
<?php

class OrderController
{
    /**
     * Menampilkan semua pesanan milik pengguna.
     */
    public function index(): void
    {
        require_login();

        $userId = (int) current_user()['id'];

        $orders = Order::forUser($userId);

        render('account/orders', [
            'pageTitle' => 'Pesanan Saya',
            'orders' => $orders
        ]);
    }

    /**
     * Menampilkan detail pesanan.
     */
    public function show(string $id): void
    {
        require_login();

        $orderId = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        if ($orderId === false || $orderId <= 0) {
            http_response_code(404);

            render('home/not-found', [
                'pageTitle' => 'Pesanan tidak ditemukan'
            ]);

            return;
        }

        $userId = (int) current_user()['id'];

        // Periksa kepemilikan pesanan.
        $order = Order::findForUser(
            $orderId,
            $userId
        );

        if (!$order) {
            http_response_code(404);

            render('home/not-found', [
                'pageTitle' => 'Pesanan tidak ditemukan'
            ]);

            return;
        }

        $items = Order::items($orderId);

        $payment = Payment::forOrder($orderId);

        render('account/order', [
            'pageTitle' => 'Detail Pesanan',
            'order' => $order,
            'items' => $items,
            'payment' => $payment
        ]);
    }
}
