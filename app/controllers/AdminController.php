<?php
// Semua halaman admin: dashboard, kelola produk, dan ubah status pesanan.
class AdminController
{

    // Dashboard admin
    public function index()
    {
        require_admin();

        $statistik = $this->ambilStatistik();

        // Cuma 8 pesanan terbaru yang ditampilkan
        $pesananTerbaru = array_slice(Order::all(), 0, 8);

        render('admin/index', [
            'pageTitle' => 'Dashboard Admin',
            'stats'     => $statistik,
            'orders'    => $pesananTerbaru,
        ]);
    }



    // Daftar produk plus form tambah produk
    public function products()
    {
        require_admin();

        render('admin/products', [
            'pageTitle'  => 'Kelola Produk',
            'products'   => Product::adminAll(),
            'categories' => Category::all(false),
        ]);
    }


    // Simpan produk baru dari form
    public function createProduct()
    {
        require_admin();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $data = $this->ambilInputProduk();

        $error = $this->periksaDataProduk($data);

        if (!empty($error)) {
            flash('error', implode(' ', $error));
            redirect('admin/products');
            return;
        }

        try {
            Product::create($data);
            flash('success', 'Produk berhasil ditambahkan.');

        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('error', 'Gagal menyimpan produk. Periksa SKU dan data produk.');
        }

        redirect('admin/products');
    }



    // Form edit produk
    public function editProduct($id)
    {
        require_admin();

        if ($id <= 0) {
            http_response_code(404);
            render('home/not-found', ['pageTitle' => 'Produk tidak ditemukan']);
            return;
        }

        $produk = Product::find($id);

        if ($produk === null) {
            http_response_code(404);
            render('home/not-found', ['pageTitle' => 'Produk tidak ditemukan']);
            return;
        }

        render('admin/product-edit', [
            'pageTitle'  => 'Edit Produk — ' . $produk['name'],
            'product'    => $produk,
            'categories' => Category::all(false),
        ]);
    }


    // Simpan perubahan dari form edit
    public function updateProduct()
    {
        require_admin();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $id = (int) post_string('product_id');
        $produk = Product::find($id);

        if ($id <= 0 || $produk === null) {
            flash('error', 'Produk tidak ditemukan.');
            redirect('admin/products');
            return;
        }

        // Di edit, status produk ikut diambil dari form
        $data = $this->ambilInputProduk(true);

        $error = $this->periksaDataProduk($data);

        if (!empty($error)) {
            flash('error', implode(' ', $error));
            redirect('admin/products/edit/' . $id);
            return;
        }

        try {
            Product::update($id, $data);
            flash('success', 'Produk berhasil diperbarui.');
            redirect('admin/products');

        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('error', 'Gagal memperbarui produk. Periksa SKU dan kategori.');
            redirect('admin/products/edit/' . $id);
        }
    }



    // Arsipkan produk
    public function deleteProduct()
    {
        require_admin();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $id = (int) post_string('product_id');

        if ($id <= 0 || Product::find($id) === null) {
            flash('error', 'Produk tidak ditemukan.');
            redirect('admin/products');
            return;
        }

        try {
            Product::delete($id);
            flash('success', 'Produk berhasil diarsipkan.');

        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('error', 'Gagal mengarsipkan produk.');
        }

        redirect('admin/products');
    }



    // Aktifkan lagi produk yang sudah diarsipkan
    public function restoreProduct()
    {
        require_admin();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $id = (int) post_string('product_id');

        if ($id <= 0 || Product::find($id) === null) {
            flash('error', 'Produk tidak ditemukan.');
            redirect('admin/products');
            return;
        }

        try {
            Product::restore($id);
            flash('success', 'Produk berhasil diaktifkan kembali dan kini tampil di katalog.');

        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('error', 'Gagal mengaktifkan kembali produk.');
        }

        redirect('admin/products');
    }


    // Ubah status satu pesanan
    public function updateOrderStatus()
    {
        require_admin();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $orderId = (int) post_string('order_id');
        $status = post_string('status');

        try {
            if ($orderId <= 0) {
                throw new InvalidArgumentException('ID pesanan tidak valid.');
            }

            Order::updateStatus($orderId, $status);

            flash('success', 'Status pesanan berhasil diperbarui.');

        } catch (InvalidArgumentException $e) {
            // Pesannya memang buat dibaca admin
            flash('error', $e->getMessage());

        } catch (RuntimeException $e) {
            // Sama, aman ditampilkan ke admin
            flash('error', $e->getMessage());

        } catch (Throwable $e) {
            // Error teknis cukup masuk log
            error_log('[Admin] updateOrderStatus: ' . $e->getMessage());
            flash('error', 'Terjadi kesalahan saat memperbarui status pesanan.');
        }

        redirect('admin');
    }


    // Angka-angka buat dashboard
    private function ambilStatistik()
    {
        $pdo = db();

        // Produk yang masih dijual
        $sqlProduk = "SELECT COUNT(*) FROM products WHERE status = 'active'";
        $jumlahProduk = (int) $pdo->query($sqlProduk)->fetchColumn();

        // Semua pesanan
        $sqlPesanan = "SELECT COUNT(*) FROM orders";
        $jumlahPesanan = (int) $pdo->query($sqlPesanan)->fetchColumn();

        // Pelanggan, admin nggak dihitung
        $sqlPelanggan = "SELECT COUNT(*) FROM users WHERE role = 'customer'";
        $jumlahPelanggan = (int) $pdo->query($sqlPelanggan)->fetchColumn();

        // Omzet dari pesanan yang sudah jalan
        $sqlOmzet = "SELECT COALESCE(SUM(total_amount), 0)
                     FROM orders
                     WHERE order_status IN ('confirmed', 'processing', 'shipped', 'completed')";
        $omzet = (float) $pdo->query($sqlOmzet)->fetchColumn();

        return [
            'products'  => $jumlahProduk,
            'orders'    => $jumlahPesanan,
            'customers' => $jumlahPelanggan,
            'revenue'   => $omzet,
        ];
    }


    // Ambil data produk dari form.
    // Status cuma dipakai saat edit, produk baru selalu "active".
    private function ambilInputProduk($pakaiStatus = false)
    {
        // Kategori kosong berarti tanpa kategori
        $categoryId = null;
        $kategoriInput = post_string('category_id');

        if ($kategoriInput !== '') {
            $categoryId = (int) $kategoriInput;
        }

        // Harga pembanding boleh kosong
        $compareAtPrice = null;
        $hargaBanding = post_string('compare_at_price');

        if ($hargaBanding !== '') {
            $compareAtPrice = $hargaBanding;
        }

        // Tag juga boleh kosong
        $tag = null;
        $tagInput = post_string('tag');

        if ($tagInput !== '') {
            $tag = $tagInput;
        }

        // Stok nggak boleh minus
        $stock = (int) post_string('stock');

        if ($stock < 0) {
            $stock = 0;
        }

        // Centang unggulan jadi 1, nggak dicentang jadi 0
        $isFeatured = 0;

        if (!empty($_POST['is_featured'])) {
            $isFeatured = 1;
        }

        $status = 'active';

        if ($pakaiStatus === true) {
            $status = post_string('status', 'active');
        }

        return [
            'category_id'      => $categoryId,
            'name'             => post_string('name'),
            'sku'              => post_string('sku'),
            'description'      => post_string('description'),
            'price'            => (float) post_string('price'),
            'compare_at_price' => $compareAtPrice,
            'stock'            => $stock,
            'color'            => post_string('color'),
            'tag'              => $tag,
            'image_url'        => post_string('image_url'),
            'status'           => $status,
            'is_featured'      => $isFeatured,
        ];
    }


    // Cek data produk, balikin daftar error (kosong berarti aman)
    private function periksaDataProduk($data)
    {
        $error = [];

        if (!required_value($data['name'])) {
            $error[] = 'Nama produk wajib diisi.';
        }

        if (!required_value($data['sku'])) {
            $error[] = 'SKU wajib diisi.';
        }

        if ($data['price'] <= 0) {
            $error[] = 'Harga produk harus lebih dari nol.';
        }

        // Kalau diisi, harga pembanding harus angka wajar
        if ($data['compare_at_price'] !== null) {
            $angkaValid = is_numeric($data['compare_at_price']);

            if ($angkaValid === false || (float) $data['compare_at_price'] < 0) {
                $error[] = 'Harga pembanding tidak valid.';
            }
        }

        // Kategori yang dipilih harus ada di database
        if ($data['category_id'] !== null) {
            $kategori = Category::find((int) $data['category_id']);

            if ($kategori === null) {
                $error[] = 'Kategori produk tidak valid.';
            }
        }

        // URL gambar harus http atau https
        if ($data['image_url'] !== '') {
            if (!valid_image_url($data['image_url'])) {
                $error[] = 'URL gambar tidak valid.';
            }
        }

        $statusValid = ['active', 'draft', 'archived'];

        if (!in_array($data['status'], $statusValid, true)) {
            $error[] = 'Status produk tidak valid.';
        }

        return $error;
    }
}