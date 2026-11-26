<?php
// Data untuk halaman beranda.
class HomeController
{
    // Tampilkan beranda
    public function index()
    {
        // 4 produk unggulan aja
        $produkUnggulan = Product::featured(4);

        // Kategori beserta jumlah produknya
        $kategori = Category::all();

        render('home/index', [
            'pageTitle'  => 'ZORA — Electronics for Everyday Life',
            'featured'   => $produkUnggulan,
            'categories' => $kategori,
        ]);
    }
}