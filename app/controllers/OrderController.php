<?php
// Riwayat pesanan dan detail pesanan pelanggan.
class OrderController
{
    // Daftar riwayat pesanan
    public function index()
    {
        require_login();

        $userId = (int) current_user()['id'];

        $daftarPesanan = Order::forUser($userId);

        render('account/orders', [
            'pageTitle' => 'Pesanan Saya',
            'orders'    => $daftarPesanan,
        ]);
    }


    // Detail satu pesanan
    public function show($id)
    {
        require_login();

        $orderId = filter_var($id, FILTER_VALIDATE_INT);

        if ($orderId === false || $orderId <= 0) {
            http_response_code(404);
            render('home/not-found', ['pageTitle' => 'Pesanan tidak ditemukan']);
            return;
        }

        $userId = (int) current_user()['id'];

        // Filternya by user_id, biar pesanan orang lain nggak bisa dibuka
        $pesanan = Order::findForUser($orderId, $userId);

        if ($pesanan === null) {
            http_response_code(404);
            render('home/not-found', ['pageTitle' => 'Pesanan tidak ditemukan']);
            return;
        }

        $daftarBarang = Order::items($orderId);
        $pembayaran = Payment::forOrder($orderId);

        render('account/order', [
            'pageTitle' => 'Detail Pesanan',
            'order'     => $pesanan,
            'items'     => $daftarBarang,
            'payment'   => $pembayaran,
        ]);
    }
}