# ALUR APLIKASI ZORA — Dokumentasi Lengkap

ZORA adalah web toko elektronik (e-commerce) berbasis PHP murni (tanpa framework) yang berjalan di XAMPP (Apache + MySQL). Pola arsitekturnya adalah **MVC sederhana**:

- **Model** (`app/models/`) — berhubungan langsung dengan database
- **View** (`app/views/`) — tampilan HTML
- **Controller** (`app/controllers/`) — logika alur aplikasi
- **Router** (`public/index.php`) — semua URL masuk lewat file ini (front controller)
- **Helper** (`app/helpers/`) — fungsi bantu (keamanan, format, dsb.)
- **Config** (`config/database.php`) — koneksi database

---

## PETA FILE

| File | Peran |
|---|---|
| `public/index.php` | Front controller / router utama. Semua URL diarahkan ke sini |
| `config/database.php` | Koneksi PDO ke MySQL, database `zora` |
| `app/helpers/functions.php` | `base_url()`, `render()`, `redirect()`, `flash()`, CSRF, `hitung_ongkir()`, dll |
| `app/helpers/auth.php` | `is_logged_in()`, `is_admin()`, `require_login()`, `require_admin()` |
| `app/helpers/validation.php` | `valid_email()`, `valid_password()`, dll |
| `app/models/User.php` | Tabel `users` (daftar akun, login) |
| `app/models/Cart.php` | Tabel `carts` + `cart_items` |
| `app/models/Product.php` | Tabel `products` |
| `app/models/Category.php` | Tabel `categories` |
| `app/models/Order.php` | Tabel `orders` + `order_items` (inti checkout) |
| `app/models/Payment.php` | Tabel `payments` |
| `app/controllers/*.php` | Auth, Home, Product, Cart, Checkout, Order, Admin |
| `app/views/**` | Tampilan tiap halaman |
| `zora.sql` | Struktur & data awal database |

---

## 0. TITIK MASUK: BAGAIMANA SATU URL DIPROSES

Semua permintaan masuk lewat **`public/index.php`**:

1. `session_start()` (baris 10) — mulai session untuk data login & keranjang.
2. Muat config + helper (baris 13–16), lalu autoloader class (baris 21–35) — model/controller dimuat otomatis.
3. Cek koneksi database lewat `db()` (baris 38–56). Kalau MySQL mati → halaman error 500.
4. URL dibaca dari `?url=...` lalu dipecah jadi `bagian1..bagian4` (baris 58–106). Contoh: `index.php?url=cart/add` → bagian1=`cart`, bagian2=`add`.
5. URL divalidasi regex keamanan (baris 116–124) — hanya huruf/angka/`-`/`_`/`/` yang boleh.
6. Routing if-elseif (baris 127–246) menentukan controller + method mana yang dipanggil.
7. Error tak terduga ditangkap `catch` (baris 248–264) → log server, pengguna lihat pesan umum.

Tabel routing (di `public/index.php`):

| URL | Controller | Method | Butuh login? |
|---|---|---|---|
| `home` | HomeController | `index()` | Tidak |
| `products` / `products/{slug}` | ProductController | `index()` / `show()` | Tidak |
| `login` | AuthController | `login()` | Tidak (malah redirect kalau sudah) |
| `register` | AuthController | `register()` | Tidak |
| `logout` | AuthController | `logout()` | POST + CSRF |
| `cart`, `cart/add`, `cart/update`, `cart/remove` | CartController | `index/add/update/remove` | Tidak (tamu boleh) |
| `checkout` | CheckoutController | `index()` / `store()` | **Ya** (`require_login()`) |
| `account` | — (langsung render) | — | **Ya** (baris 192) |
| `orders`, `orders/{id}` | OrderController | `index()` / `show()` | **Ya** |
| `admin/**` | AdminController | berbagai | **Ya + admin** (`require_admin()`) |

---

## 1. MENJELAJAH TANPA AKUN (GUEST)

- **Beranda** — `HomeController::index()` (`app/controllers/HomeController.php`) mengambil 4 produk unggulan (`Product::featured(4)`, `app/models/Product.php` baris 71) + kategori (`Category::all()`). Ditampilkan oleh `app/views/home/index.php`.
- **Katalog** — `ProductController::index()` membaca `?q=`, `?category=`, `?sort=` dari URL. `sort` divalidasi white-list (`price_asc`, `price_desc`, `name`) supaya tidak bisa disisipi SQL (baris 22–26). Query dibangun aman di `Product::all()` (`app/models/Product.php` baris 13) dengan prepared statement.
- **Detail produk** — `ProductController::show($slug)` → `Product::findBySlug()` (baris 97). Hanya produk `status='active'` yang muncul; produk arsip → 404.

### 1a. Tamu menambahkan ke keranjang (belum login)

1. Di halaman detail/katalog, tombol submit form POST ke `cart/add` (view: `app/views/products/detail.php`).
2. Router (`public/index.php` baris 165–177) → `CartController::add()` (`app/controllers/CartController.php` baris 20).
3. Wajib POST + `verify_csrf()` (token form, fungsi di `app/helpers/functions.php` baris 221).
4. `Cart::add($productId, $quantity)` (`app/models/Cart.php` baris 105):
   - Cek produk ada & aktif (`Product::find()`).
   - Cek stok cukup.
   - `Cart::cartId()` (baris 12) mencari/buat keranjang. **Karena belum login**, keranjang dikenali dari `session_id` (baris 34–43) — ini kunci kenapa barang tamu nggak hilang.
   - Kalau produk sudah ada di keranjang, `ON DUPLICATE KEY UPDATE ... LEAST(quantity + VALUES(quantity), :stock)` menambah jumlah tanpa melebihi stok (baris 130–133).
5. Flash message "berhasil ditambahkan" → redirect balik ke halaman asal lewat `halamanAsal()` (baris 126) yang memvalidasi `HTTP_REFERER` (anti open-redirect).

### 1b. Keranjang tamu

- Halaman `cart` → `CartController::index()` → `Cart::items()` menampilkan isi + `Cart::subtotal()`.
- Update jumlah: `cart/update` → `Cart::update()` (baris 149) — item dicek milik keranjang sendiri (anti edit keranjang orang lain), dan tidak boleh melebihi stok.
- Hapus item: `cart/remove` → `Cart::remove()` (baris 198).

---

## 2. REGISTRASI (BELUM PUNYA AKUN)

Kalau pengguna menekan tombol **Masuk** di navbar (`app/views/layouts/navbar.php` baris 50) tapi belum punya akun, di halaman login ada link "Daftar sekarang" (`app/views/auth/login.php` baris 30) menuju halaman register.

**View:** `app/views/auth/register.php`
**Controller:** `AuthController::register()` (`app/controllers/AuthController.php` baris 70)

Alur detail:

1. Kalau ternyata sudah login → langsung diarahkan ke `account`/`admin` (baris 73–79).
2. Form dikirim POST ke `register` → `verify_csrf()`.
3. Ambil input: `name`, `email`, `phone`, `password`, `password_confirmation` via `post_string()` (baris 87–91).
4. Validasi (baris 94–115):
   - Nama wajib → `required_value()` (`app/helpers/validation.php` baris 26).
   - Email format → `valid_email()` (baris 9).
   - Email belum dipakai → `User::findByEmail()` (`app/models/User.php` baris 6).
   - Password min 8 karakter → `valid_password()` (baris 19).
   - Konfirmasi password sama.
5. Kalau lolos semua (baris 118):
   - Simpan ID session lama (untuk merge keranjang tamu).
   - `User::create()` (`app/models/User.php` baris 50) → `INSERT INTO users` dengan role **`customer`** (hardcoded, tidak bisa daftar jadi admin) dan password di-**hash** `password_hash(..., PASSWORD_DEFAULT)` (baris 62).
   - Langsung login otomatis: `$_SESSION['user'] = $pengguna` + `session_regenerate_id(true)` (cegah session fixation) — baris 132–133.
   - `Cart::mergeGuestCart($userId, $sessionLama)` — barang yang sudah dimasukkan sebelum daftar **ikut pindah** ke keranjang akun (lihat bagian 4).
   - Redirect ke `account`.

**Error** (email duplikat, password pendek, dsb.) ditampilkan di atas form oleh view `register.php` dan isian lama tetap terisi lewat helper `old()` (`app/helpers/functions.php` baris 247).

---

## 3. LOGIN

**View:** `app/views/auth/login.php`
**Controller:** `AuthController::login()` (`app/controllers/AuthController.php` baris 7)

Alur:

1. Sudah login? → redirect `admin` (kalau admin) atau `account` (baris 10–16).
2. Form POST ke `login` → `verify_csrf()`.
3. Ambil `email` (lowercase) + `password` (baris 25–26). Validasi format email.
4. `User::authenticate($email, $password)` (`app/models/User.php` baris 70):
   - Cari user by email (`findByEmail`).
   - Cek `status === 'active'` — akun nonaktif tidak bisa login (baris 80).
   - `password_verify($password, hash)` cocokkan password dengan hash database (baris 85).
   - Update `last_login_at = NOW()` (baris 91).
   - **Hash password dihapus sebelum data disimpan ke session** (baris 95) — keamanan.
5. Kalau cocok (AuthController baris 35–53):
   - Simpan session ID lama.
   - `$_SESSION['user'] = $pengguna` → **ini yang menandai "sudah login"**.
   - `session_regenerate_id(true)` → ID session baru, cegah session fixation.
   - `Cart::mergeGuestCart(...)` → keranjang tamu digabung.
   - Redirect: admin → `admin`, customer → `account` (dicek lewat `is_admin()`, `app/helpers/auth.php` baris 28).
6. Kalau gagal → pesan **sengaja umum**: "Email atau password tidak sesuai" (baris 57), supaya tidak membocorkan field mana yang salah.

**Status login dicek di mana-mana lewat helper** (`app/helpers/auth.php`):
- `is_logged_in()` — apakah `$_SESSION['user']['id']` ada.
- `is_admin()` — apakah `role === 'admin'`.
- `require_login()` — belum login? → flash error + redirect `login` (baris 43).
- `require_admin()` — bukan admin? → HTTP 403 "Akses ditolak" (baris 52).

---

## 4. GABUNG KERANJANG TAMU SAAT LOGIN/REGISTER

`Cart::mergeGuestCart($userId, $sessionIdLama)` (`app/models/Cart.php` baris 279):

1. Cari keranjang tamu berdasarkan session ID lama (`user_id IS NULL`, baris 286–291). Nggak ada → selesai.
2. Cari keranjang milik user yang baru login (baris 305–313).
3. **User belum punya keranjang** → keranjang tamu "diambil alih": `UPDATE carts SET user_id = ...` (baris 319).
4. **User sudah punya keranjang** → tiap barang tamu dipindah, kalau produk sama jumlahnya dijumlahkan (`ON DUPLICATE KEY UPDATE`, baris 346–349). Keranjang tamu ditandai `abandoned` (baris 362).

Efeknya: barang yang ditambahkan saat masih tamu tidak hilang setelah login/daftar.

---

## 5. SETELAH LOGIN — HALAMAN AKUN

- **Akun saya** — URL `account` dicek `require_login()` di router (`public/index.php` baris 192) → render `app/views/account/index.php`.
- **Riwayat pesanan** — `OrderController::index()` (`app/controllers/OrderController.php` baris 6): `require_login()`, ambil `Order::forUser($userId)` — hanya pesanan milik sendiri (`app/models/Order.php` baris 156).
- **Detail pesanan** — `OrderController::show($id)` (baris 23): ID divalidasi integer, lalu **`Order::findForUser($orderId, $userId)`** (`app/models/Order.php` baris 189) memakai `WHERE id = :id AND user_id = :user_id` — ini pencegahan **IDOR** (tidak bisa buka pesanan orang lain dengan mengganti angka ID di URL). Ikut menampilkan rincian barang (`Order::items()`) dan data bayar (`Payment::forOrder()`).

Navbar berubah setelah login: tombol "Masuk" diganti nama depan user (`app/views/layouts/navbar.php` baris 45–51), dan angka jumlah barang muncul di ikon Keranjang (dari `Cart::count()`, dihitung di header).

---

## 6. CHECKOUT (INTI TRANSAKSI)

**View:** `app/views/checkout/index.php` (form) + `app/views/checkout/success.php` (sukses)
**Controller:** `CheckoutController` (`app/controllers/CheckoutController.php`)

### 6a. Membuka halaman checkout — `index()` (baris 7)

1. `require_login()` — **checkout wajib login**. Tamu yang checkout akan dilempar ke login (dengan flash "Silakan login terlebih dahulu").
2. Keranjang kosong? → redirect ke `products`.
3. Hitung subtotal (`Cart::subtotal()`) dan ongkir via `hitung_ongkir()` (`app/helpers/functions.php` baris 401): **subtotal ≥ Rp 500.000 → gratis ongkir**, selain itu **Rp 15.000**.
4. Render form: data penerima (nama, telepon, alamat, kota, provinsi, kode pos, catatan) + pilihan pembayaran (transfer bank / QRIS / e-wallet / COD — radio di `checkout/index.php` baris 63–99).

### 6b. Submit pesanan — `store()` (baris 33)

1. `require_login()` + wajib POST + `verify_csrf()`.
2. Kumpulkan data pengiriman (baris 48–56).
3. Validasi (baris 59–88):
   - Kolom wajib: nama penerima, telepon, alamat, kota.
   - Format telepon regex `^[0-9+\-\s]{8,20}$`.
   - Metode bayar harus salah satu dari `bank_transfer`, `qris`, `ewallet`, `cod`.
4. Baca ulang keranjang + subtotal + ongkir (harga dihitung **di server**, bukan percaya browser).
5. Kalau ada error → form ditampilkan lagi beserta pesan (baris 102–111).
6. **`Order::createFromCart($userId, $pengiriman, $metodeBayar)`** — bagian paling penting (`app/models/Order.php` baris 8), semuanya dalam **satu transaksi database** (`beginTransaction`, baris 24):

   | Langkah | Kode | Penjelasan |
   |---|---|---|
   | 1. Kunci & cek stok | baris 30–55 | `SELECT ... FOR UPDATE` mengunci baris produk, supaya dua orang yang beli barang terakhir bersamaan tidak sama-sama dapat. Stok kurang / produk nonaktif → gagal |
   | 2. Nomor pesanan | baris 59 | Format `ZOR` + tanggal + hex acak, contoh `ZOR260311A1B2C3` |
   | 3. Insert `orders` | baris 62–94 | Data pengiriman + subtotal + ongkir + total, status awal `pending` |
   | 4. Insert `order_items` + kurangi stok | baris 100–130 | Nama/SKU/gambar produk disimpan sebagai **snapshot**, jadi riwayat lama tetap benar walau produk diedit admin |
   | 5. Catat pembayaran | baris 133 | `Payment::create()` (`app/models/Payment.php` baris 6) — status `pending`, metode tervalidasi |
   | 6. Kosongkan keranjang | baris 137 | `Cart::clear(true, $pdo)` — hapus item + tandai keranjang `converted` |
   | 7. `commit()` | baris 140 | Semua perubahan jadi permanen |

   Kalau ada langkah gagal → `rollBack()` (baris 146) → **tidak ada setengah-setengah pesanan**.
7. Sukses → render `checkout/success.php` menampilkan nomor pesanan & ringkasan.
8. Stok habis saat checkout → `RuntimeException` dengan pesan ramah → flash + redirect ke `cart` (baris 130–134). Error teknis → log server, pesan umum.

---

## 7. LOGOUT

**Controller:** `AuthController::logout()` (`app/controllers/AuthController.php` baris 158)

1. **Wajib POST** (bukan link biasa) + `verify_csrf()` — supaya logout tidak bisa dipicu pihak lain (CSRF logout).
2. `unset($_SESSION['user'])` → data login dihapus.
3. `session_regenerate_id(true)` → session baru yang bersih.
4. Redirect ke `home`.

Tombol logout ada di view akun/admin (form POST ke `logout`).

---

## 8. SISI ADMIN

Akses: `admin/**` → semua method `AdminController` diawali **`require_admin()`** (`app/controllers/AdminController.php`; fungsi di `app/helpers/auth.php` baris 52). Customer yang coba masuk → HTTP 403.

- **Dashboard** — `AdminController::index()`: statistik (jumlah produk aktif, total pesanan, jumlah pelanggan, omzet — `ambilStatistik()` baris 265) + 8 pesanan terbaru (`Order::all()`).
- **Kelola produk** — `products()`, `createProduct()`, `editProduct($id)`, `updateProduct()`, `deleteProduct()` (arsip, bukan hapus permanen), `restoreProduct()`. Validasi input di `periksaDataProduk()` (baris 370): nama & SKU wajib, harga > 0, kategori harus ada, URL gambar harus http(s). Slug unik dibuat otomatis di `Product::buatSlugUnik()` (`app/models/Product.php` baris 267) — bentrok? jadi `nova-phone-2`, dst.
- **Ubah status pesanan** — `updateOrderStatus()` (baris 222) → `Order::updateStatus()` (`app/models/Order.php` baris 226):
  - Status valid: `confirmed / processing / shipped / completed / cancelled`.
  - Pesanan yang sudah `cancelled` tidak bisa diaktifkan lagi.
  - **Kalau dibatalkan, stok produk otomatis dikembalikan** (baris 258–279) — juga dalam transaksi.

---

## 9. KEAMANAN YANG DIPAKAI (RINGKASAN)

| Mekanisme | Lokasi kode |
|---|---|
| Password di-hash (`password_hash` / `password_verify`) | `app/models/User.php` baris 62, 85 |
| Session fixation dihambat (`session_regenerate_id`) | `AuthController` baris 43, 133, 171 |
| CSRF token di semua form POST | `csrf_field()` / `verify_csrf()` — `app/helpers/functions.php` baris 194–239 |
| XSS dihambat (`e()` = `htmlspecialchars`) saat menampilkan data | `app/helpers/functions.php` baris 98 |
| SQL injection dihambat (prepared statement PDO, emulasi off) | `config/database.php` baris 46 + semua model |
| Proteksi IDOR pesanan (`user_id` di WHERE) | `Order::findForUser()` baris 189 |
| Proteksi halaman (`require_login` / `require_admin`) | `app/helpers/auth.php` |
| Race condition stok (`SELECT ... FOR UPDATE` + transaksi) | `Order::createFromCart()` baris 30 |
| Role tak bisa dipalsukan saat daftar (hardcoded `customer`) | `User::create()` baris 55 |
| Anti open-redirect pada kembali-ke-halaman-asal | `CartController::halamanAsal()` baris 126 |

---

## 10. RINGKASAN ALUR SATU KALI BACA

```
Buka web → index.php (router) → home / katalog / detail produk (tanpa login)
   │
   ├─ tambah ke keranjang? → disimpan sebagai KERANJANG TAMU (dikenali session_id)
   │
   ├─ belum punya akun → REGISTER (validasi → password di-hash → langsung login
   │        → keranjang tamu digabung ke akun → redirect account)
   │
   ├─ sudah punya akun → LOGIN (password_verify → session 'user' diisi
   │        → regenerate session → merge keranjang → redirect account/admin)
   │
   ├─ CHECKOUT (wajib login): isi alamat + pilih bayar
   │        → validasi server → Order::createFromCart (TRANSAKSI):
   │             kunci stok → buat orders → order_items (snapshot) →
   │             kurangi stok → payments (pending) → kosongkan keranjang → commit
   │        → halaman sukses (nomor pesanan ZORyyMMddXXXXXX)
   │
   ├─ riwayat: orders (milik sendiri saja) / detail pesanan
   │
   └─ LOGOUT (POST + CSRF) → session user dihapus → home

Sisi admin (login sebagai admin):
   admin → dashboard statistik → kelola produk (tambah/edit/arsip/restore)
        → ubah status pesanan (cancel = stok dikembalikan)
```
