<?php
// Halaman katalog dan detail produk.
class ProductController
{

    // Katalog dengan pencarian, filter kategori, dan urutan
    public function index()
    {
        $kataKunci = get_string('q');
        $kategori = get_string('category');
        $urutan = get_string('sort');

        // Cuma urutan ini yang boleh dipakai
        $urutanValid = ['price_asc', 'price_desc', 'name'];

        if (!in_array($urutan, $urutanValid, true)) {
            $urutan = '';
        }

        $filter = [
            'q'        => $kataKunci,
            'category' => $kategori,
            'sort'     => $urutan,
        ];

        $produk = Product::all($filter);
        $daftarKategori = Category::all();

        render('products/index', [
            'pageTitle'  => 'Semua Produk',
            'products'   => $produk,
            'categories' => $daftarKategori,
            'filters'    => $filter,
        ]);
    }


    // Detail satu produk, dicari dari slug di URL
    public function show($slug)
    {
        $slug = trim($slug);

        if ($slug === '') {
            http_response_code(404);
            render('home/not-found', ['pageTitle' => 'Produk tidak ditemukan']);
            return;
        }

        $produk = Product::findBySlug($slug);

        // Produk nggak ada atau sudah diarsipkan
        if ($produk === null) {
            http_response_code(404);
            render('home/not-found', ['pageTitle' => 'Produk tidak ditemukan']);
            return;
        }

        render('products/detail', [
            'pageTitle' => $produk['name'],
            'product'   => $produk,
        ]);
    }
}