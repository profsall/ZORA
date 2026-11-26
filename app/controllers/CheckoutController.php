<?php
// Menangani halaman checkout dan proses pembuatan pesanan.
class CheckoutController
{

    // Menampilkan halaman formulir checkout.
    public function index()
    {
        // Hanya pengguna yang sudah login yang boleh checkout
        require_login();

        $daftarBarang = Cart::items();

        // Keranjang kosong tidak bisa checkout
        if (empty($daftarBarang)) {
            flash('error', 'Keranjang masih kosong.');
            redirect('products');
        }

        $subtotal = Cart::subtotal();
        $ongkir = hitung_ongkir($subtotal);

        render('checkout/index', [
            'pageTitle'    => 'Checkout',
            'items'        => $daftarBarang,
            'subtotal'     => $subtotal,
            'shippingCost' => $ongkir,
        ]);
    }


    // Memproses formulir checkout menjadi pesanan.
    public function store()
    {
        require_login();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $error = [];

        // Kumpulkan data pengiriman dari form
        $pengiriman = [
            'recipient_name'  => post_string('recipient_name'),
            'recipient_phone' => post_string('recipient_phone'),
            'address'         => post_string('address'),
            'city'            => post_string('city'),
            'province'        => post_string('province'),
            'postal_code'     => post_string('postal_code'),
            'note'            => post_string('note'),
        ];


        // Validasi: kolom wajib
        $kolomWajib = ['recipient_name', 'recipient_phone', 'address', 'city'];
        $adaYangKosong = false;

        foreach ($kolomWajib as $kolom) {
            if (!required_value($pengiriman[$kolom])) {
                $adaYangKosong = true;
            }
        }

        if ($adaYangKosong === true) {
            $error[] = 'Nama penerima, nomor telepon, alamat, dan kota wajib diisi.';
        }

        // Validasi: format nomor telepon
        if ($pengiriman['recipient_phone'] !== '') {
            $polaTelepon = '/^[0-9+\-\s]{8,20}$/';

            if (!preg_match($polaTelepon, $pengiriman['recipient_phone'])) {
                $error[] = 'Nomor telepon tidak valid.';
            }
        }

        // Validasi: metode pembayaran
        $metodeBayar = post_string('payment_method', 'bank_transfer');
        $metodeValid = ['bank_transfer', 'qris', 'ewallet', 'cod'];

        if (!in_array($metodeBayar, $metodeValid, true)) {
            $error[] = 'Metode pembayaran tidak valid.';
        }

        // Siapkan data barang dan hitung total
        $daftarBarang = Cart::items();
        $subtotal = Cart::subtotal();
        $ongkir = hitung_ongkir($subtotal);

        // Keranjang kosong
        if (empty($daftarBarang)) {
            flash('error', 'Keranjang masih kosong.');
            redirect('products');
        }

        // Kalau ada kesalahan, tampilkan kembali form beserta pesannya
        if (!empty($error)) {
            render('checkout/index', [
                'pageTitle'    => 'Checkout',
                'items'        => $daftarBarang,
                'subtotal'     => $subtotal,
                'shippingCost' => $ongkir,
                'errors'       => array_unique($error),
            ]);
            return;
        }

        // Semua valid, buat pesanan
        try {
            $userId = (int) current_user()['id'];

            $orderId = Order::createFromCart($userId, $pengiriman, $metodeBayar);

            $pesanan = Order::findForUser($orderId, $userId);

            if ($pesanan === null) {
                throw new RuntimeException('Pesanan tidak ditemukan setelah dibuat.');
            }

            render('checkout/success', [
                'pageTitle' => 'Pesanan Berhasil',
                'order'     => $pesanan,
            ]);

        } catch (RuntimeException $e) {
            // Kesalahan yang memang bisa terjadi, misalnya stok habis.
            // Pesannya aman untuk ditampilkan ke pengguna.
            flash('error', $e->getMessage());
            redirect('cart');

        } catch (Throwable $e) {
            // Kesalahan teknis yang tidak terduga.
            // Detailnya disimpan di log server, pengguna cukup melihat pesan umum.
            error_log($e->getMessage());
            flash('error', 'Terjadi kesalahan ketika memproses pesanan.');
            redirect('cart');
        }
    }
}
