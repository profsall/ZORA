<?php
class AuthController
{
    public function login(): void
    {
        if(is_logged_in()) redirect('account');
        $errors=[];
        if(is_post()) {
            verify_csrf(); $user=User::authenticate($_POST['email']??'',$_POST['password']??'');
            if($user){ $oldSessionId=session_id(); $_SESSION['user']=$user; session_regenerate_id(true); Cart::mergeGuestCart((int)$user['id'],$oldSessionId); redirect(is_admin()?'admin':'account'); }
            $errors[]='Email atau password tidak sesuai.';
        }
        render('auth/login',['pageTitle'=>'Masuk','errors'=>$errors]);
    }
    public function register(): void
    {
        if(is_logged_in()) redirect('account');
        $errors=[];
        if(is_post()) {
            verify_csrf(); $name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??'')); $phone=trim($_POST['phone']??''); $password=$_POST['password']??''; $confirm=$_POST['password_confirmation']??'';
            if(!required_value($name)) $errors[]='Nama wajib diisi.';
            if(!valid_email($email)) $errors[]='Format email tidak valid.';
            if(User::findByEmail($email)) $errors[]='Email sudah terdaftar.';
            if(!valid_password($password)) $errors[]='Password minimal 8 karakter.';
            if($password!==$confirm) $errors[]='Konfirmasi password tidak sama.';
            if(!$errors){ $oldSessionId=session_id(); $id=User::create(compact('name','email','phone','password')); $_SESSION['user']=User::find($id); session_regenerate_id(true); Cart::mergeGuestCart($id,$oldSessionId); redirect('account'); }
        }
        render('auth/register',['pageTitle'=>'Buat Akun','errors'=>$errors]);
    }
    public function logout(): void
    { if(!is_post()){http_response_code(405);exit('Method not allowed.');} verify_csrf(); unset($_SESSION['user']); session_regenerate_id(true); redirect('home'); }
}
