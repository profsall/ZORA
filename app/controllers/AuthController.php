<?php
// Daftar akun, login, dan logout.
class AuthController
{

    // Form login sekaligus prosesnya
    public function login()
    {
        // Sudah login, nggak perlu lagi
        if (is_logged_in()) {
            if (is_admin()) {
                redirect('admin');
            } else {
                redirect('account');
            }
        }

        $error = [];

        if (is_post()) {
            verify_csrf();

            $email = strtolower(post_string('email'));
            $password = post_string('password');

            if (!valid_email($email)) {
                $error[] = 'Format email tidak valid.';
            } else {
                $pengguna = User::authenticate($email, $password);

                if ($pengguna !== null) {
                    // Session lama dipakai buat gabung keranjang
                    $sessionLama = session_id();

                    $_SESSION['user'] = $pengguna;

                    session_regenerate_id(true);

                    Cart::mergeGuestCart((int) $pengguna['id'], $sessionLama);

                    if (is_admin()) {
                        redirect('admin');
                    } else {
                        redirect('account');
                    }
                }

                // Pesannya sengaja umum, biar nggak bocor mana yang salah
                $error[] = 'Email atau password tidak sesuai.';
            }
        }

        render('auth/login', [
            'pageTitle' => 'Masuk',
            'errors'    => $error,
        ]);
    }


    // Form daftar akun sekaligus simpan akun baru
    public function register()
    {
        if (is_logged_in()) {
            if (is_admin()) {
                redirect('admin');
            } else {
                redirect('account');
            }
        }

        $error = [];

        if (is_post()) {
            verify_csrf();

            $nama = post_string('name');
            $email = strtolower(post_string('email'));
            $telepon = post_string('phone');
            $password = post_string('password');
            $konfirmasi = post_string('password_confirmation');

            if (!required_value($nama)) {
                $error[] = 'Nama wajib diisi.';
            }

            if (!valid_email($email)) {
                $error[] = 'Format email tidak valid.';
            } else {
                // Email nggak boleh dobel
                $sudahAda = User::findByEmail($email);

                if ($sudahAda !== null) {
                    $error[] = 'Email sudah terdaftar.';
                }
            }

            if (!valid_password($password)) {
                $error[] = 'Password minimal 8 karakter.';
            }

            if ($password !== $konfirmasi) {
                $error[] = 'Konfirmasi password tidak sama.';
            }

            // Validasi lolos, akun disimpan
            if (empty($error)) {
                try {
                    $sessionLama = session_id();

                    $userId = User::create($nama, $email, $telepon, $password);

                    $pengguna = User::find($userId);

                    if ($pengguna === null) {
                        throw new RuntimeException('Data pengguna gagal dibuat.');
                    }

                    $_SESSION['user'] = $pengguna;
                    session_regenerate_id(true);

                    Cart::mergeGuestCart($userId, $sessionLama);

                    redirect('account');

                } catch (Throwable $e) {
                    error_log($e->getMessage());
                    $error[] = 'Registrasi gagal. Periksa email dan coba kembali.';
                }
            }
        }

        render('auth/register', [
            'pageTitle' => 'Buat Akun',
            'errors'    => $error,
        ]);
    }


    // Logout harus lewat POST, biar nggak bisa dipicu dari link biasa
    public function logout()
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        unset($_SESSION['user']);

        session_regenerate_id(true);

        redirect('home');
    }
}