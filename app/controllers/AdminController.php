<?php
class AdminController
{
    public function index(): void
    {
        require_admin();
        $stats=['products'=>(int)db()->query("SELECT COUNT(*) FROM products WHERE status='active'")->fetchColumn(),'orders'=>(int)db()->query('SELECT COUNT(*) FROM orders')->fetchColumn(),'customers'=>(int)db()->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn(),'revenue'=>(float)db()->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE order_status IN ('confirmed','processing','shipped','completed')")->fetchColumn()];
        render('admin/index',['pageTitle'=>'Dashboard Admin','stats'=>$stats,'orders'=>array_slice(Order::all(),0,8)]);
    }
    public function products(): void { require_admin(); render('admin/products',['pageTitle'=>'Kelola Produk','products'=>Product::adminAll(),'categories'=>Category::all(false)]); }
    public function createProduct(): void
    {
        require_admin(); verify_csrf();
        $data=['category_id'=>$_POST['category_id']??null,'name'=>trim($_POST['name']??''),'sku'=>trim($_POST['sku']??''),'description'=>trim($_POST['description']??''),'price'=>(float)($_POST['price']??0),'compare_at_price'=>$_POST['compare_at_price']??null,'stock'=>max(0,(int)($_POST['stock']??0)),'color'=>trim($_POST['color']??''),'tag'=>trim($_POST['tag']??''),'image_url'=>trim($_POST['image_url']??''),'is_featured'=>!empty($_POST['is_featured'])];
        if(!$data['name']||!$data['sku']||$data['price']<=0){flash('error','Nama, SKU, dan harga wajib diisi dengan benar.');redirect('admin/products');}
        try{Product::create($data);flash('success','Produk berhasil ditambahkan.');}catch(Throwable $e){flash('error','Gagal menyimpan produk. Pastikan SKU belum digunakan.');}
        redirect('admin/products');
    }
    public function deleteProduct(): void
    { require_admin(); verify_csrf(); Product::delete((int)($_POST['product_id']??0)); flash('success','Produk diarsipkan.'); redirect('admin/products'); }
    public function updateOrderStatus(): void
    { require_admin(); verify_csrf(); try{Order::updateStatus((int)($_POST['order_id']??0),$_POST['status']??'');flash('success','Status pesanan diperbarui.');}catch(Throwable $e){flash('error',$e->getMessage());} redirect('admin'); }
}
