<?php
class OrderController
{
    public function index(): void
    { require_login(); render('account/orders',['pageTitle'=>'Pesanan Saya','orders'=>Order::forUser((int)current_user()['id'])]); }
    public function show(string $id): void
    {
        require_login(); $order=Order::findForUser((int)$id,(int)current_user()['id']);
        if(!$order){http_response_code(404);render('home/not-found',['pageTitle'=>'Pesanan tidak ditemukan']);return;}
        render('account/order',['pageTitle'=>'Detail Pesanan','order'=>$order,'items'=>Order::items((int)$id),'payment'=>Payment::forOrder((int)$id)]);
    }
}
