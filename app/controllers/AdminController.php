<?php
class AdminController
{
    /**
     * Dashboard administrator.
     */
    public function index(): void
    {
        require_admin();
        $pdo = db();
        $stats = [
            'products' => (int) $pdo->query(
                "SELECT COUNT(*) FROM products WHERE status = 'active'"
            )->fetchColumn(),

            'orders' => (int) $pdo->query(
                'SELECT COUNT(*) FROM orders'
            )->fetchColumn(),

            'customers' => (int) $pdo->query(
                "SELECT COUNT(*) FROM users WHERE role = 'customer'"
            )->fetchColumn(),

            'revenue' => (float) $pdo->query(
                "SELECT COALESCE(SUM(total_amount), 0)
                 FROM orders
                 WHERE order_status IN
                 ('confirmed', 'processing', 'shipped', 'completed')"
            )->fetchColumn()
        ];

        render('admin/index', [
            'pageTitle' => 'Dashboard Admin',
            'stats' => $stats,
            'orders' => array_slice(Order::all(), 0, 8)
        ]);
    }

    /**
     * Menampilkan daftar produk.
     */
    public function products(): void
    {
        require_admin();
        render('admin/products', [
            'pageTitle' => 'Kelola Produk',
            'products' => Product::adminAll(),
            'categories' => Category::all(false)
        ]);
    }

    /**
     * Menambahkan produk baru.
     */
    public function createProduct(): void
    {
        require_admin();
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();
        $categoryInput = $_POST['category_id'] ?? '';
        $categoryId = $categoryInput === ''
            ? null
            : filter_var($categoryInput, FILTER_VALIDATE_INT);

        $data = [
            'category_id' => $categoryId,
            'name' => trim((string) ($_POST['name'] ?? '')),
            'sku' => trim((string) ($_POST['sku'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => (float) ($_POST['price'] ?? 0),
            'compare_at_price' => trim((string) ($_POST['compare_at_price'] ?? '')),
            'stock' => max(0, (int) ($_POST['stock'] ?? 0)),
            'color' => trim((string) ($_POST['color'] ?? '')),
            'tag' => trim((string) ($_POST['tag'] ?? '')),
            'image_url' => trim((string) ($_POST['image_url'] ?? '')),
            'is_featured' => !empty($_POST['is_featured'])
        ];

        $errors = [];

        if (!required_value($data['name'])) {
            $errors[] = 'Nama produk wajib diisi.';
        }

        if (!required_value($data['sku'])) {
            $errors[] = 'SKU wajib diisi.';
        }

        if ($data['price'] <= 0) {
            $errors[] = 'Harga produk harus lebih dari nol.';
        }

        if (
            $data['compare_at_price'] !== '' &&
            (
                !is_numeric($data['compare_at_price']) ||
                (float) $data['compare_at_price'] < 0
            )
        ) {
            $errors[] = 'Harga pembanding tidak valid.';
        }

        if (
            $categoryInput !== '' &&
            (
                $categoryId === false ||
                !Category::find((int) $categoryId)
            )
        ) {
            $errors[] = 'Kategori produk tidak valid.';
        }

        if ($data['image_url'] !== '' && !$this->validImageUrl($data['image_url'])) {
            $errors[] = 'URL gambar tidak valid.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('admin/products');
        }

        try {
            Product::create($data);

            flash('success', 'Produk berhasil ditambahkan.');
        } catch (Throwable $e) {
            error_log($e->getMessage());

            flash(
                'error',
                'Gagal menyimpan produk. Periksa SKU dan data produk.'
            );
        }

        redirect('admin/products');
    }

    /**
     * Menampilkan formulir edit produk.
     */
    public function editProduct(int $id): void
    {
        require_admin();

        if ($id <= 0) {
            http_response_code(404);
            render('home/not-found', [
                'pageTitle' => 'Produk tidak ditemukan'
            ]);
            return;
        }

        $product = Product::find($id);

        if (!$product) {
            http_response_code(404);

            render('home/not-found', [
                'pageTitle' => 'Produk tidak ditemukan'
            ]);

            return;
        }

        render('admin/product-edit', [
            'pageTitle' => 'Edit Produk - ' . $product['name'],
            'product' => $product,
            'categories' => Category::all(false)
        ]);
    }

    /**
     * Menyimpan perubahan produk.
     */
    public function updateProduct(): void
    {
        require_admin();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $id = (int) ($_POST['product_id'] ?? 0);

        $product = Product::find($id);

        if ($id <= 0 || !$product) {
            flash('error', 'Produk tidak ditemukan.');
            redirect('admin/products');
        }

        $categoryInput = $_POST['category_id'] ?? '';
        $categoryId = $categoryInput === ''
            ? null
            : filter_var($categoryInput, FILTER_VALIDATE_INT);

        $data = [
            'category_id' => $categoryId,
            'name' => trim((string) ($_POST['name'] ?? '')),
            'sku' => trim((string) ($_POST['sku'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'price' => (float) ($_POST['price'] ?? 0),
            'compare_at_price' => trim((string) ($_POST['compare_at_price'] ?? '')),
            'stock' => max(0, (int) ($_POST['stock'] ?? 0)),
            'color' => trim((string) ($_POST['color'] ?? '')),
            'tag' => trim((string) ($_POST['tag'] ?? '')),
            'image_url' => trim((string) ($_POST['image_url'] ?? '')),
            'status' => $_POST['status'] ?? 'active',
            'is_featured' => !empty($_POST['is_featured'])
        ];

        $errors = [];

        if (!required_value($data['name'])) {
            $errors[] = 'Nama produk wajib diisi.';
        }

        if (!required_value($data['sku'])) {
            $errors[] = 'SKU wajib diisi.';
        }

        if ($data['price'] <= 0) {
            $errors[] = 'Harga harus lebih dari nol.';
        }

        if (
            $data['compare_at_price'] !== '' &&
            (
                !is_numeric($data['compare_at_price']) ||
                (float) $data['compare_at_price'] < 0
            )
        ) {
            $errors[] = 'Harga pembanding tidak valid.';
        }

        if (
            $categoryInput !== '' &&
            (
                $categoryId === false ||
                !Category::find((int) $categoryId)
            )
        ) {
            $errors[] = 'Kategori tidak valid.';
        }

        $allowedStatus = [
            'active',
            'draft',
            'archived'
        ];

        if (!in_array($data['status'], $allowedStatus, true)) {
            $errors[] = 'Status produk tidak valid.';
        }

        if (
            $data['image_url'] !== '' &&
            !$this->validImageUrl($data['image_url'])
        ) {
            $errors[] = 'URL gambar tidak valid.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            redirect('admin/products/edit/' . $id);
        }

        try {
            Product::update($id, $data);

            flash('success', 'Produk berhasil diperbarui.');
            redirect('admin/products');

        } catch (Throwable $e) {
            error_log($e->getMessage());

            flash(
                'error',
                'Gagal memperbarui produk. Periksa SKU dan kategori.'
            );

            redirect('admin/products/edit/' . $id);
        }
    }

    /**
     * Mengarsipkan produk.
     */
    public function deleteProduct(): void
    {
        require_admin();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $id = (int) ($_POST['product_id'] ?? 0);

        if ($id <= 0 || !Product::find($id)) {
            flash('error', 'Produk tidak ditemukan.');
            redirect('admin/products');
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

    /**
     * Memperbarui status pesanan.
     */
    public function updateOrderStatus(): void
    {
        require_admin();

        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        try {
            if ($orderId <= 0) {
                throw new InvalidArgumentException('ID pesanan tidak valid.');
            }

            Order::updateStatus($orderId, $status);

            flash('success', 'Status pesanan berhasil diperbarui.');

        } catch (Throwable $e) {
            error_log($e->getMessage());
            flash('error', $e->getMessage());
        }

        redirect('admin');
    }

    /**
     * Validasi URL gambar.
     */
    private function validImageUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = strtolower(
            (string) parse_url($url, PHP_URL_SCHEME)
        );

        return in_array($scheme, ['http', 'https'], true);
    }
}
