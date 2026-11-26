<?php
// Halaman keranjang dan semua perubahan isinya.
class CartController
{

    // Tampilkan keranjang
    public function index()
    {
        $daftarBarang = Cart::items();

        render('cart/index', [
            'pageTitle' => 'Keranjang',
            'items'     => $daftarBarang,
            'subtotal'  => Cart::subtotal(),
        ]);
    }


    // Tambah produk ke keranjang
    public function add()
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $productId = (int) post_string('product_id');
        $quantity = (int) post_string('quantity');

        if ($quantity < 1) {
            $quantity = 1;
        }

        try {
            if ($productId <= 0) {
                throw new InvalidArgumentException('Produk tidak valid.');
            }

            Cart::add($productId, $quantity);

            flash('success', 'Produk berhasil ditambahkan ke keranjang.');

        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        // Balik ke halaman asal pengguna
        redirect($this->halamanAsal('products'));
    }


    // Ubah jumlah barang
    public function update()
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $itemId = (int) post_string('item_id');
        $quantity = (int) post_string('quantity');

        if ($quantity < 1) {
            $quantity = 1;
        }

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


    // Hapus barang dari keranjang
    public function remove()
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $itemId = (int) post_string('item_id');

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


    // Tentukan halaman asal, contohnya balik ke detail produk.
    // Kalau asalnya nggak jelas, pakai halaman cadangan.
    private function halamanAsal($cadangan)
    {
        $alamatAsal = '';

        if (isset($_SERVER['HTTP_REFERER'])) {
            $alamatAsal = $_SERVER['HTTP_REFERER'];
        }

        if ($alamatAsal === '') {
            return $cadangan;
        }

        // Asal dari situs lain diabaikan
        $hostAsal = strtolower((string) parse_url($alamatAsal, PHP_URL_HOST));
        $hostSekarang = strtolower((string) $this->hostSekarang());

        if ($hostAsal === '' || $hostAsal !== $hostSekarang) {
            return $cadangan;
        }

        // Harus datang dari index.php aplikasi ini
        $jalurAsal = parse_url($alamatAsal, PHP_URL_PATH);

        if ($jalurAsal === false || $jalurAsal === null) {
            return $cadangan;
        }

        $jalurSeharusnya = public_base_path() . '/index.php';

        if ($jalurAsal !== $jalurSeharusnya) {
            return $cadangan;
        }

        $queryAsal = parse_url($alamatAsal, PHP_URL_QUERY);

        if ($queryAsal === false || $queryAsal === null) {
            return $cadangan;
        }

        parse_str($queryAsal, $parameter);

        if (!isset($parameter['url'])) {
            return $cadangan;
        }

        $halaman = $parameter['url'];

        if (!is_string($halaman) || $halaman === '') {
            return $cadangan;
        }

        $halaman = trim($halaman, '/');

        // Cuma huruf, angka, tanda hubung, dan garis miring
        $pola = '~^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$~';

        if (!preg_match($pola, $halaman)) {
            return $cadangan;
        }

        // Parameter lain kayak filter ikut dibawa
        unset($parameter['url']);

        $parameterTambahan = http_build_query($parameter);

        if ($parameterTambahan !== '') {
            return $halaman . '?' . $parameterTambahan;
        }

        return $halaman;
    }


    // Nama host dari alamat yang sedang dibuka
    private function hostSekarang()
    {
        $host = '';

        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }

        return (string) parse_url('http://' . $host, PHP_URL_HOST);
    }
}