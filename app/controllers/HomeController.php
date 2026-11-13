<?php
/**
 * CONTROLLER BERANDA
 * ==================
 * Menyiapkan data untuk halaman utama (homepage).
 */
class HomeController
{
    /**
     * Menampilkan halaman beranda.
     */
    public function index()
    {
        // Ambil 4 produk unggulan untuk ditampilkan di beranda
        $produkUnggulan = Product::featured(4);

        // Ambil semua kategori beserta jumlah produknya
        $kategori = Category::all();

        render('home/index', [
            'pageTitle'  => 'ZORA — Electronics for Everyday Life',
            'featured'   => $produkUnggulan,
            'categories' => $kategori,
        ]);
    }
}
