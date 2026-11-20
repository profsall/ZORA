<?php
/**
 * CONTROLLER PRODUK
 * =================
 * Menangani halaman katalog dan halaman detail produk.
 */
class ProductController
{
    /**
     * Menampilkan halaman katalog produk.
     * Bisa dengan pencarian, filter kategori, dan pengurutan.
     */
    public function index()
    {
        // Baca parameter dari URL
        $kataKunci = get_string('q');
        $kategori = get_string('category');
        $urutan = get_string('sort');

        // Hanya urutan berikut yang diperbolehkan.
        // Ini mencegah orang mengirim nilai sembarangan lewat URL.
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


     /* Menampilkan halaman detail satu produk.
     Produk dicari berdasarkan slug di URL. */
    public function show($slug)
    {
        $slug = trim($slug);

        // Slug kosong berarti produk tidak ada
        if ($slug === '') {
            http_response_code(404);
            render('home/not-found', ['pageTitle' => 'Produk tidak ditemukan']);
            return;
        }

        // Cari produk yang aktif
        $produk = Product::findBySlug($slug);

        // Produk tidak ditemukan atau sudah diarsipkan
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