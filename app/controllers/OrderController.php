<?php
/**
 * CONTROLLER PESANAN
 * ==================
 * Menampilkan riwayat pesanan dan detail pesanan milik pelanggan.
 */
class OrderController
{
    /**
     * Menampilkan daftar riwayat pesanan milik pelanggan.
     */
    public function index()
    {
        require_login();

        $userId = (int) current_user()['id'];

        // Hanya ambil pesanan milik pengguna yang sedang login
        $daftarPesanan = Order::forUser($userId);

        render('account/orders', [
            'pageTitle' => 'Pesanan Saya',
            'orders'    => $daftarPesanan,
        ]);
    }


    /**
     * Menampilkan detail satu pesanan.
     */
    public function show($id)
    {
        require_login();

        // ID pesanan harus berupa angka
        $orderId = filter_var($id, FILTER_VALIDATE_INT);

        if ($orderId === false || $orderId <= 0) {
            http_response_code(404);
            render('home/not-found', ['pageTitle' => 'Pesanan tidak ditemukan']);
            return;
        }

        $userId = (int) current_user()['id'];

        // Ambil pesanan hanya kalau memang milik pengguna ini.
        // Ini mencegah orang lain membuka pesanan dengan mengganti ID.
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
