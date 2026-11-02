<?php
class HomeController
{
    public function index(): void
    {
        render('home/index',[
            'pageTitle'=>'ZORA — Electronics for Everyday Life',
            'featured'=>Product::featured(4),
            'categories'=>Category::all(),
        ]);
    }
}
