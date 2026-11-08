
<?php

class ProductController
{
    /**
     * Menampilkan katalog produk.
     */
    public function index(): void
    {
        $keyword = $this->getString('q');

        $category = $this->getString('category');

        $sort = $this->getString('sort');

        // Pilihan sorting yang diizinkan.
        $allowedSorts = [
            'price_asc',
            'price_desc',
            'name'
        ];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = '';
        }

        $filters = [
            'q' => $keyword,
            'category' => $category,
            'sort' => $sort
        ];

        $products = Product::all($filters);

        $categories = Category::all();

        render('products/index', [
            'pageTitle' => 'Semua Produk',
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters
        ]);
    }

    /**
     * Menampilkan detail produk berdasarkan slug.
     */
    public function show(string $slug): void
    {
        $slug = trim($slug);

        if ($slug === '') {
            http_response_code(404);

            render('home/not-found', [
                'pageTitle' => 'Produk tidak ditemukan'
            ]);

            return;
        }

        $product = Product::findBySlug($slug);

        if (!$product) {
            http_response_code(404);

            render('home/not-found', [
                'pageTitle' => 'Produk tidak ditemukan'
            ]);

            return;
        }

        render('products/detail', [
            'pageTitle' => $product['name'],
            'product' => $product
        ]);
    }

    /**
     * Mengambil parameter GET yang berupa string.
     */
    private function getString(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value)
            ? trim($value)
            : '';
    }
}
