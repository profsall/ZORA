
<?php

class AuthController
{
    /**
     * Halaman login.
     */
    public function login(): void
    {
        if (is_logged_in()) {
            redirect(is_admin() ? 'admin' : 'account');
        }

        $errors = [];

        if (is_post()) {
            verify_csrf();

            $email = is_string($_POST['email'] ?? null)
                ? strtolower(trim($_POST['email']))
                : '';

            $password = is_string($_POST['password'] ?? null)
                ? $_POST['password']
                : '';

            if (!valid_email($email)) {
                $errors[] = 'Format email tidak valid.';
            } else {
                $user = User::authenticate($email, $password);

                if ($user) {
                    $oldSessionId = session_id();

                    $_SESSION['user'] = $user;

                    session_regenerate_id(true);

                    Cart::mergeGuestCart(
                        (int) $user['id'],
                        $oldSessionId
                    );

                    redirect(is_admin() ? 'admin' : 'account');
                }

                $errors[] = 'Email atau password tidak sesuai.';
            }
        }

        render('auth/login', [
            'pageTitle' => 'Masuk',
            'errors' => $errors
        ]);
    }

    /**
     * Halaman registrasi.
     */
    public function register(): void
    {
        if (is_logged_in()) {
            redirect(is_admin() ? 'admin' : 'account');
        }

        $errors = [];

        if (is_post()) {
            verify_csrf();

            $name = is_string($_POST['name'] ?? null)
                ? trim($_POST['name'])
                : '';

            $email = is_string($_POST['email'] ?? null)
                ? strtolower(trim($_POST['email']))
                : '';

            $phone = is_string($_POST['phone'] ?? null)
                ? trim($_POST['phone'])
                : '';

            $password = is_string($_POST['password'] ?? null)
                ? $_POST['password']
                : '';

            $confirm = is_string($_POST['password_confirmation'] ?? null)
                ? $_POST['password_confirmation']
                : '';

            // Validasi nama
            if (!required_value($name)) {
                $errors[] = 'Nama wajib diisi.';
            }

            // Validasi email
            if (!valid_email($email)) {
                $errors[] = 'Format email tidak valid.';
            } elseif (User::findByEmail($email)) {
                $errors[] = 'Email sudah terdaftar.';
            }

            // Validasi password
            if (!valid_password($password)) {
                $errors[] = 'Password minimal 8 karakter.';
            }

            if ($password !== $confirm) {
                $errors[] = 'Konfirmasi password tidak sama.';
            }

            if (!$errors) {
                try {
                    $oldSessionId = session_id();

                    $userId = User::create([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'password' => $password
                    ]);

                    $user = User::find($userId);

                    if (!$user) {
                        throw new RuntimeException(
                            'Data pengguna gagal dibuat.'
                        );
                    }

                    $_SESSION['user'] = $user;

                    session_regenerate_id(true);

                    Cart::mergeGuestCart(
                        $userId,
                        $oldSessionId
                    );

                    redirect('account');

                } catch (Throwable $e) {
                    error_log($e->getMessage());

                    $errors[] = 'Registrasi gagal. Periksa email dan coba kembali.';
                }
            }
        }

        render('auth/register', [
            'pageTitle' => 'Buat Akun',
            'errors' => $errors
        ]);
    }

    /**
     * Logout pengguna.
     */
    public function logout(): void
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
