<?php
/**
 * CONTROLLER AUTENTIKASI
 * ======================
 * Menangani daftar akun, login, dan logout.
 */
class AuthController
{
    /**
     * Menampilkan form login dan memproses login.
     */
    public function login()
    {
        // Kalau sudah login, tidak perlu login lagi
        if (is_logged_in()) {
            if (is_admin()) {
                redirect('admin');
            } else {
                redirect('account');
            }
        }

        $error = [];

        // Proses hanya kalau form dikirim
        if (is_post()) {
            verify_csrf();

            // Ambil isi form
            $email = strtolower(post_string('email'));
            $password = post_string('password');

            // Validasi format email
            if (!valid_email($email)) {
                $error[] = 'Format email tidak valid.';
            } else {
                // Cocokkan email dan password ke database
                $pengguna = User::authenticate($email, $password);

                if ($pengguna !== null) {
                    // Simpan ID session lama untuk menggabungkan keranjang
                    $sessionLama = session_id();

                    // Simpan data pengguna ke session (artinya sudah login)
                    $_SESSION['user'] = $pengguna;

                    // Ganti ID session demi keamanan
                    session_regenerate_id(true);

                    // Pindahkan barang keranjang tamu ke akun ini
                    Cart::mergeGuestCart((int) $pengguna['id'], $sessionLama);

                    if (is_admin()) {
                        redirect('admin');
                    } else {
                        redirect('account');
                    }
                }

                // Pesan dibuat umum supaya tidak membocorkan
                // apakah email atau password yang salah
                $error[] = 'Email atau password tidak sesuai.';
            }
        }

        render('auth/login', [
            'pageTitle' => 'Masuk',
            'errors'    => $error,
        ]);
    }


    /**
     * Menampilkan form daftar akun dan menyimpan akun baru.
     */
    public function register()
    {
        // Kalau sudah login, tidak perlu daftar lagi
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

            // Ambil isi form
            $nama = post_string('name');
            $email = strtolower(post_string('email'));
            $telepon = post_string('phone');
            $password = post_string('password');
            $konfirmasi = post_string('password_confirmation');

            // --------------------------------------------------------
            // Validasi data
            // --------------------------------------------------------
            if (!required_value($nama)) {
                $error[] = 'Nama wajib diisi.';
            }

            if (!valid_email($email)) {
                $error[] = 'Format email tidak valid.';
            } else {
                // Email tidak boleh dipakai dua kali
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

            // --------------------------------------------------------
            // Kalau semua validasi lolos, simpan akun baru
            // --------------------------------------------------------
            if (empty($error)) {
                try {
                    $sessionLama = session_id();

                    // Buat akun baru
                    $userId = User::create($nama, $email, $telepon, $password);

                    $pengguna = User::find($userId);

                    if ($pengguna === null) {
                        throw new RuntimeException('Data pengguna gagal dibuat.');
                    }

                    // Langsung login setelah daftar
                    $_SESSION['user'] = $pengguna;
                    session_regenerate_id(true);

                    // Pindahkan barang keranjang tamu ke akun baru
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


    /**
     * Keluar dari akun (logout).
     * Harus lewat form POST supaya tidak bisa dipicu dari link biasa.
     */
    public function logout()
    {
        if (!is_post()) {
            http_response_code(405);
            exit('Method not allowed.');
        }

        verify_csrf();

        // Hapus data pengguna dari session
        unset($_SESSION['user']);

        // Ganti ID session dengan yang baru dan bersih
        session_regenerate_id(true);

        redirect('home');
    }
}
