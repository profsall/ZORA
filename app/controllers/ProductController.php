<?php
class ProductController
{
    public function index(): void
    {
        $filters=['q'=>trim($_GET['q']??''),'category'=>trim($_GET['category']??''),'sort'=>$_GET['sort']??''];
        render('products/index',['pageTitle'=>'Semua Produk','products'=>Product::all($filters),'categories'=>Category::all(),'filters'=>$filters]);
    }
    public function show(string $slug): void
    {
        $product=Product::findBySlug($slug);
        if(!$product){ http_response_code(404); render('home/not-found',['pageTitle'=>'Produk tidak ditemukan']); return; }
        render('products/detail',['pageTitle'=>$product['name'],'product'=>$product]);
    }
}
