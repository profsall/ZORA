<?php
class CartController
{
    public function index(): void { render('cart/index',['pageTitle'=>'Keranjang','items'=>Cart::items(),'subtotal'=>Cart::subtotal()]); }
    public function add(): void
    {
        if(!is_post()){http_response_code(405);return;} verify_csrf();
        try{Cart::add((int)($_POST['product_id']??0),max(1,(int)($_POST['quantity']??1))); flash('success','Produk ditambahkan ke keranjang.');}
        catch(Throwable $e){flash('error',$e->getMessage());}
        redirect('products');
    }
    public function update(): void
    { verify_csrf(); try{Cart::update((int)($_POST['item_id']??0),max(1,(int)($_POST['quantity']??1)));flash('success','Keranjang diperbarui.');}catch(Throwable $e){flash('error',$e->getMessage());} redirect('cart'); }
    public function remove(): void
    { verify_csrf(); Cart::remove((int)($_POST['item_id']??0)); flash('success','Produk dihapus dari keranjang.'); redirect('cart'); }
}
