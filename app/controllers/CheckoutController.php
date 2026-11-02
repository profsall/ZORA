<?php
class CheckoutController
{
    public function index(): void
    {
        require_login(); $items=Cart::items();
        if(!$items){flash('error','Keranjang masih kosong.');redirect('products');}
        render('checkout/index',['pageTitle'=>'Checkout','items'=>$items,'subtotal'=>Cart::subtotal(),'shippingCost'=>Cart::subtotal()>=500000?0:15000]);
    }
    public function store(): void
    {
        require_login(); verify_csrf(); $errors=[];
        $shipping=[
            'recipient_name'=>trim($_POST['recipient_name']??''),'recipient_phone'=>trim($_POST['recipient_phone']??''),
            'address'=>trim($_POST['address']??''),'city'=>trim($_POST['city']??''),'province'=>trim($_POST['province']??''),
            'postal_code'=>trim($_POST['postal_code']??''),'note'=>trim($_POST['note']??''),
        ];
        foreach(['recipient_name','recipient_phone','address','city'] as $field) if(!required_value($shipping[$field])) $errors[]='Nama penerima, nomor telepon, alamat, dan kota wajib diisi.';
        if($errors){render('checkout/index',['pageTitle'=>'Checkout','items'=>Cart::items(),'subtotal'=>Cart::subtotal(),'shippingCost'=>Cart::subtotal()>=500000?0:15000,'errors'=>array_unique($errors)]);return;}
        try{$id=Order::createFromCart((int)current_user()['id'],$shipping,$_POST['payment_method']??'bank_transfer');$order=Order::findForUser($id,(int)current_user()['id']);render('checkout/success',['pageTitle'=>'Pesanan berhasil','order'=>$order]);}
        catch(Throwable $e){flash('error',$e->getMessage());redirect('cart');}
    }
}
