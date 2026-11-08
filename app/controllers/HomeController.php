
<?php

class HomeController
{
    /**
     * Menampilkan halaman utama ZORA.
     */
    public function index(): void
    {
        $featuredProducts = Product::featured(4);

        $categories = Category::all();

        render('home/index', [
            'pageTitle' => 'ZORA — Electronics for Everyday Life',
            'featured' => $featuredProducts,
            'categories' => $categories
        ]);
    }
}
