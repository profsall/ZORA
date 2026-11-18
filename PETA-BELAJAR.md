# PETA BELAJAR ZORA

Panduan memahami proyek ZORA dari nol sampai bisa menjawab pertanyaan dosen.

> **Catatan:** dokumen ini untuk keperluan belajar pribadi.
> Jangan disertakan saat mengumpulkan tugas — ini catatan persiapanmu, bukan
> bagian dari aplikasi. Simpan di luar folder proyek, atau hapus sebelum dikumpulkan.

---

## Cara pakai dokumen ini

Kamu **tidak perlu** memahami 4.956 baris kode. Kamu hanya perlu paham **9 konsep**.
Sembilan konsep itu ada di Level 1 dan Level 2 di bawah.

Aturan mainnya:

1. Baca bagian **"Alur besar"** dulu sampai kebayang. Itu pondasi segalanya.
2. Kerjakan **Level 1** sampai kamu bisa menjelaskannya tanpa buka catatan.
3. Lanjut **Level 2**. Ini yang membuatmu terlihat benar-benar paham.
4. Level 3 hanya dibaca sekilas — untuk jaga-jaga kalau ditanya lebih dalam.
5. Bagian **"Pertanyaan dosen"** dibaca terakhir, lalu latihan menjawab.

Setiap konsep punya format sama:

- **Di mana** — file dan baris, buka dan baca sendiri
- **Pertanyaannya** — yang kemungkinan ditanya
- **Jawabannya** — penjelasan pakai bahasamu sendiri

---

## Alur besar aplikasi (baca ini dulu)

Bayangkan kamu buka `index.php?url=cart/add` lalu kirim form.

```
1. Browser kirim permintaan ke public/index.php?url=cart/add
                                    |
2. index.php baca parameter "url"   |  public/index.php baris 66-106
   Pecah "cart/add" jadi 2 bagian:  |
   bagian1 = "cart"                 |
   bagian2 = "add"                  |
                                    |
3. Cek keamanan alamat dengan regex |  public/index.php baris 116-124
   Kalau ada karakter aneh -> 404   |
                                    |
4. Pilih controller berdasarkan      |  public/index.php baris 128-247
   bagian1. "cart" -> CartController |
   bagian2 "add" -> panggil add()   |
                                    |
5. Controller cek: POST? token?      |  CartController.php baris 27-34
                                    |
6. Controller panggil model          |  CartController.php baris 49
   Cart::add()                       |
                                    |
7. Model jalankan query ke MySQL     |  Cart.php baris 113-151
   pakai prepared statement          |
                                    |
8. Controller simpan pesan + redirect|  CartController.php baris 51-58
                                    |
9. Browser minta halaman baru,       |
   index.php jalan lagi dari awal    |
```

**Intinya:** semua permintaan lewat satu pintu (`index.php`), lalu pintu itu yang
mengarahkan ke bagian yang tepat. Ini namanya **front controller**.

Tiga lapisan yang harus kamu hafal namanya:

| Lapisan | Tugas | Contoh file |
|---|---|---|
| **Controller** | Terima permintaan, cek izin, panggil model | `AuthController.php` |
| **Model** | Bicara dengan database | `User.php` |
| **View** | Tampilkan HTML ke pengguna | `auth/login.php` |

Controller **tidak** menulis query. Model **tidak** menampilkan HTML.
View **tidak** mengambil data. Pemisahan ini yang disebut **MVC**.

---

## LEVEL 1 — WAJIB

Kalau kamu hanya punya waktu 1 hari, pahami 5 ini saja.

---

### 1. Cara aplikasi tahu kamu sudah login

**Di mana:** `app/helpers/auth.php` (seluruh file), `app/controllers/AuthController.php` baris 40-57

**Pertanyaannya:** "Bagaimana sistem tahu bahwa yang mengakses ini sudah login?"

**Jawabannya:**

Saat login berhasil, data pengguna disimpan di **session**:

```php
$_SESSION['user'] = $pengguna;   // AuthController.php baris 45
```

Session itu data yang disimpan di server, ditandai dengan cookie di browser.
Jadi browser cuma menyimpan ID session — data aslinya di server.

Setiap kali ada halaman yang butuh login, dipanggil `require_login()`:

```php
function is_logged_in()
{
    if (!isset($_SESSION['user']['id'])) {
        return false;
    }
    return !empty($_SESSION['user']['id']);
}
```

Kalau tidak ada data di session, pengguna dilempar ke halaman login.

Untuk admin, ada lapisan kedua — `require_admin()`. Jadi bukan cuma "sudah login",
tapi juga "role-nya admin". Kalau customer mencoba buka `/admin`, dapat 403.

**Yang penting kamu tahu:**

- `session_start()` di `index.php` baris 10 itu yang menyalakan session
- Hash password **dihapus** dari session sebelum disimpan (User.php baris 116) — supaya hash tidak ikut ke mana-mana
- Setelah login, ID session **diganti** dengan yang baru (`session_regenerate_id(true)`, AuthController.php baris 48). Kenapa? Mencegah **session fixation** — kalau ada orang yang sudah tahu ID session lama, ID itu jadi tidak berlaku lagi setelah login

---

### 2. Kenapa query database aman dari SQL Injection

**Di mana:** `config/database.php`, semua file di `app/models/`

**Pertanyaannya:** "Bagaimana kamu mencegah SQL Injection?"

**Jawabannya:**

Semua query yang menerima input pengguna memakai **prepared statement** dengan
**placeholder**. Contoh dari `User.php` baris 20-22:

```php
$sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
$query = $pdo->prepare($sql);
$query->execute(['email' => $email]);
```

Perhatikan: `:email` itu placeholder. Nilai asli **tidak** ditempel ke dalam teks query.
Nilai dikirim terpisah ke MySQL.

**Kenapa ini penting?**

Kalau ditulis naik begitu — `WHERE email = '$email'` — dan ada orang mengisi email
dengan `' OR '1'='1`, query-nya berubah arti dan bisa bocor semua data.

Dengan prepared statement, isian itu diperlakukan sebagai **teks biasa** saja.
Tidak pernah dibaca sebagai perintah.

Di `config/database.php` ada satu pengaturan yang memperkuat ini:

```php
$koneksi->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
```

Artinya: parameter benar-benar dikirim terpisah ke MySQL, bukan ditempel dulu oleh PHP.
Kalau `true`, PHP yang menempelkan — masih aman, tapi pemisahannya tidak sekuat native.

**Yang penting kamu tahu:**

- Tiga atribut PDO di `config/database.php` sempat dinonaktifkan, sekarang sudah aktif
- Yang mengaktifkan: `ERRMODE_EXCEPTION` (error dilempar sebagai exception), `FETCH_ASSOC` (hasil berupa array asosiatif), `EMULATE_PREPARES=false`
- Tidak semua query pakai prepared — query tanpa input pengguna cukup `->query()`, misalnya daftar produk admin

---

### 3. Form punya token rahasia (CSRF)

**Di mana:** `app/helpers/functions.php` baris 223-268

**Pertanyaannya:** "Kenapa setiap form ada `<input type="hidden" name="_token">`?"
**Jawabannya:**

Itu **CSRF token**. Fungsinya membuktikan bahwa form yang dikirim benar-benar
berasal dari halaman aplikasi sendiri.

Bayangkan kamu sudah login ke ZORA. Lalu kamu buka situs jahat di tab lain.
Situs jahat itu diam-diam mengirim form ke ZORA (misalnya untuk menghapus produk).
Karena browser otomatis menyertakan cookie session, ZORA akan menganggap
permintaan itu sah — padahal itu dari situs lain.

Token menghentikan ini. Situs jahat tidak tahu nilai token di session kamu,
jadi formnya ditolak.

Cara kerjanya:

```php
// Buat token (sekali saja per session)
function csrf_token()
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

// Verifikasi saat form dikirim
function verify_csrf()
{
    $tokenDikirim = (string) ($_POST['_token'] ?? '');
    $cocok = hash_equals(csrf_token(), $tokenDikirim);

    if (!$cocok) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Silakan kembali dan muat ulang halaman.');
    }
}
```

**Kenapa pakai `hash_equals()` bukan `==`?**

Karena `hash_equals()` membandingkan dengan waktu konstan — tidak bocor
informasi lewat perbedaan waktu eksekusi. Ini disebut **timing attack**.

**Yang penting kamu tahu:**

- Semua form POST punya `<?= csrf_field() ?>`, dan semua endpoint POST memanggil `verify_csrf()`
- Logout juga pakai POST + token, bukan link biasa — supaya tidak bisa dipicu dari situs lain

---

### 4. Semua output dinamis di-escape

**Di mana:** `app/helpers/functions.php` baris 114-117, dipakai 76 kali di seluruh view

**Pertanyaannya:** "Kenapa pakai `e()`? Kenapa tidak `echo` langsung?"

**Jawabannya:**

```php
function e($nilai)
{
    return htmlspecialchars((string) $nilai, ENT_QUOTES, 'UTF-8');
}
```

Fungsi ini mengubah karakter berbahaya menjadi teks biasa:

| Karakter | Jadi | Efeknya |
|---|---|---|
| `<` | `&lt;` | Tag HTML tidak jadi tag |
| `>` | `&gt;` | idem |
| `"` | `&quot;` | Tidak bisa keluar dari atribut |
| `'` | `&#039;` | idem |

Ini mencegah **XSS** (Cross-Site Scripting). Contohnya: kalau nama produk diberi
`<script>alert('hack')</script>`, tanpa `e()` script itu akan jalan di browser pengunjung.
Dengan `e()`, yang tampil cuma teksnya.

**Yang penting kamu tahu:**

- `ENT_QUOTES` artinya tanda kutip tunggal **dan** ganda ikut diamankan
- Di dalam folder `app/views/`, **tidak ada satu pun** akses langsung ke `$_GET`/`$_POST`. Semua data masuk lewat controller. Ini disiplin yang bagus dan layak disebut.

---

### 5. Password tidak pernah disimpan apa adanya

**Di mana:** `app/models/User.php` baris 79 (simpan), baris 106 (verifikasi)

**Pertanyaannya:** "Bagaimana password disimpan?"

**Jawabannya:**

Password **di-hash** dulu sebelum masuk database:

```php
'password' => password_hash($password, PASSWORD_DEFAULT)   // User.php baris 79
```

Hash itu **satu arah** — tidak bisa dibalik jadi password asli. Saat login,
yang dibandingkan bukan passwordnya, tapi hash-nya:

```php
if (!password_verify($password, $pengguna['password'])) {
    return null;   // User.php baris 106-108
}
```

**Kenapa bukan MD5 atau SHA1?**

MD5 dan SHA1 terlalu cepat — miliaran percobaan per detik bisa dicoba.
`password_hash()` dengan `PASSWORD_DEFAULT` saat ini memakai **bcrypt**, yang
sengaja lambat dan otomatis menambahkan **salt** (data acak) sehingga dua orang
dengan password sama tetap punya hash berbeda.

**Yang penting kamu tahu:**

- Hash password dihapus dari data sebelum disimpan ke session (baris 116)
- Saat login gagal, pesannya selalu **"Email atau password tidak sesuai"** — tidak pernah bilang mana yang salah. Ini supaya penyerang tidak bisa menyimpulkan email mana yang terdaftar.

---

## LEVEL 2 — PENTING

Ini yang membedakan "sekadar bisa jalan" dengan "paham".

---

### 6. Checkout pakai transaksi database

**Di mana:** `app/models/Order.php` baris 18-179

**Pertanyaannya:** "Kalau dua orang checkout barang terakhir di waktu yang sama, apa yang terjadi?"

**Jawabannya:**

Ini bagian paling matang di proyek. Ada dua mekanisme.

**Pertama, transaksi.** Semua langkah checkout dibungkus:

```php
$pdo->beginTransaction();

try {
    // cek stok, simpan pesanan, simpan item, kurangi stok, catat payment, kosongkan keranjang
    $pdo->commit();
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}
```

Kalau ada satu langkah gagal di tengah, **semuanya dibatalkan**. Jadi tidak akan
ada pesanan setengah jadi — misalnya pesanan tersimpan tapi stok tidak berkurang.

**Kedua, row lock.** Saat cek stok:

```sql
SELECT stock, status FROM products WHERE id = :id FOR UPDATE
```

`FOR UPDATE` mengunci baris produk itu sampai transaksi selesai. Jadi kalau
ada dua orang checkout bersamaan:

- Orang pertama mengunci baris → cek stok cukup → kurangi stok → commit → kunci lepas
- Orang kedua menunggu → baru boleh cek → sekarang stok sudah berkurang → kalau tidak cukup, ditolak

Tanpa `FOR UPDATE`, dua orang bisa sama-sama melihat stok "1", sama-sama lolos cek,
dan stok jadi minus. Ini disebut **race condition**.

**Yang penting kamu tahu:**

- Validasi stok dilakukan **di dalam** transaksi, bukan sebelum. Kalau sebelum, celahnya masih ada.
- Produk yang statusnya bukan `active` juga ditolak
- Total dihitung ulang di server (`Cart::subtotal()`), bukan dipercaya dari form

---

### 7. Pengguna tidak bisa melihat pesanan orang lain

**Di mana:** `app/models/Order.php` baris 226-248, `app/controllers/OrderController.php` baris 44-54

**Pertanyaannya:** "Kalau saya ganti angka di URL `orders/5` jadi `orders/6`, apa yang terjadi?"

**Jawabannya:**

Ditolak. Query-nya selalu memfilter **dua** hal:

```sql
SELECT * FROM orders
WHERE id = :id
  AND user_id = :user_id
LIMIT 1
```

Jadi walaupun ID pesanannya benar ada, kalau bukan milik kamu, hasilnya kosong
dan halaman menampilkan 404.

Kalau query-nya cuma `WHERE id = :id`, itu namanya **IDOR** (Insecure Direct Object
Reference) — kelemahan yang sangat umum. Di sini sudah dicegah.

Hal yang sama berlaku di keranjang: `Cart::update()` dan `Cart::remove()` selalu
menyertakan `cart_id` di WHERE, jadi kamu tidak bisa mengubah keranjang orang lain
walaupun tahu ID item-nya.

**Yang penting kamu tahu:**

- Pola ini konsisten: **setiap** query yang mengambil data milik pengguna, selalu ada `user_id` di WHERE
- Perhatikan bedanya: untuk admin (`Order::all()`), tidak ada filter — memang seharusnya begitu

---

### 8. Keranjang tamu digabung saat login

**Di mana:** `app/models/Cart.php` baris 287-372

**Pertanyaannya:** "Kalau saya isi keranjang sebelum login, lalu login — isinya hilang?"

**Jawabannya:**

Tidak hilang. Ada fungsi `mergeGuestCart()` yang memindahkannya.

Konsepnya: keranjang bisa dimiliki dua jenis pengunjung.

- **Sudah login** → dikenali dari `user_id`
- **Belum login** → dikenali dari `session_id` (ID session di browser)

Waktu login terjadi dua kemungkinan:

- **Pengguna belum punya keranjang** → keranjang tamunya diambil alih (baris 324-339)
- **Pengguna sudah punya keranjang** → barang dari keranjang tamu dipindah satu per satu (baris 342-365)

Untuk yang kedua, kalau produk yang sama sudah ada di dua keranjang, jumlahnya
digabung — bukan dibuat baris baru. Ini yang mencegah duplikat.

**Yang penting kamu tahu:**

- Kuncinya ada di `UNIQUE KEY uq_cart_items_cart_product (cart_id, product_id)` di `zora.sql`. Unique inilah yang membuat penggabungan otomatis bekerja.
- Keranjang tamu setelah digabung ditandai status `abandoned` — tidak dihapus, jadi masih ada jejaknya

---

### 9. Alamat URL produk (slug)

**Di mana:** `app/models/Product.php`, `public/index.php` baris 136-146

**Pertanyaannya:** "Kenapa URL-nya `products/nova-phone-pro`, bukan `products/1`?"

**Jawabannya:**

Bagian `nova-phone-pro` itu namanya **slug** — versi nama produk yang ramah URL.

Dibuat otomatis dari nama produk (`functions.php` baris 137-153):

```php
function slugify($teks)
{
    $teks = strtolower(trim($teks));
    $teks = preg_replace('/[^a-z0-9]+/', '-', $teks);   // karakter aneh jadi tanda hubung
    $teks = trim($teks, '-');
    ...
}
```

Kalau slug sudah dipakai produk lain, ditambah angka: `nova-phone-pro`, `nova-phone-pro-2`, dst.

Fungsi yang mengatur ini memastikan tidak ada dua produk dengan slug sama.

**Yang penting kamu tahu:**

- Slug **ikut berubah** kalau nama produk diedit di admin (baris 235-241) — ini yang kami perbaiki
- Kalau nama dikembalikan ke aslinya, slug juga balik ke slug lama, bukan jadi `-2`. Ini karena produk yang sedang diedit tidak dihitung sebagai "pemakai slug" oleh dirinya sendiri

---

## LEVEL 3 — KALAU DITANYA LEBIH DALAM

Baca sekilas. Kalau ditanya, jawab singkat dan jujur: "bagian itu saya perlu cek dulu".

| Konsep | Di mana | Intinya |
|---|---|---|
| Kurangi stok otomatis | `Order.php` baris 128-148 | Setelah item disimpan, stok produk dikurangi dengan `UPDATE products SET stock = stock - :jumlah` |
| Simpan snapshot produk | `Order.php` baris 119-149 | Nama, SKU, dan gambar produk **disalin** ke `order_items`. Jadi kalau produk diubah admin nanti, riwayat pesanan lama tetap menampilkan data saat dibeli |
| Restock saat dibatalkan | `Order.php` baris 306-329 | Kalau status diubah jadi `cancelled`, stok dikembalikan. Dicek dulu supaya tidak dobel — pesanan yang sudah dibatalkan tidak bisa dibatalkan lagi |
| Nomor pesanan unik | `Order.php` baris 73 | Format `ZOR` + tanggal + 6 karakter acak. Contoh: `ZOR261118EB32D0` |
| Keranjang dikosongkan | `Order.php` baris 161 | `Cart::clear(true, $pdo)` — di dalam transaksi yang sama, jadi kalau checkout gagal, keranjang tidak ikut kosong |
| Keranjang belum ada | `Cart.php` baris 17-72 | Kalau pengunjung belum punya keranjang, keranjang baru otomatis dibuat |

---

## PERTANYAAN DOSEN & CARA MENJAWAB

### "Apakah ini kamu yang buat sendiri?"

Jawab jujur, dan tunjukkan pemahamanmu:

> "Saya yang menyusun strukturnya dan mengerjakan bagian-bagiannya, tapi saya juga
> memakai bantuan AI untuk mempercepat, terutama di bagian yang saya belum pernah
> coba — seperti transaksi database dan penggabungan keranjang. Setelah itu saya
> pelajari satu per satu sampai paham."

Kejujuran dijawab dengan pemahaman jauh lebih kuat daripada klaim yang tidak
bisa dipertahankan. Kalau kamu bisa menjelaskan `FOR UPDATE` dan kenapa CSRF perlu,
itu bukti nyata kamu paham — tidak peduli siapa yang menulis barisnya.

Yang **jangan** dilakukan: mengklaim semua sendiri, lalu tidak bisa menjawab
ketika ditunjuk satu baris.

### "Bagaimana cara memastikan ini tidak sekadar AI generate?"

Kamu bisa menunjukkan hal-hal ini — dan semuanya benar:

> "Ada beberapa bug yang saya temukan dan perbaiki sendiri. Misalnya slug produk
> tidak ikut berubah waktu nama diedit. Ada juga filter harga di model yang tidak
> punya UI, jadi saya hapus. Tiga tabel di database juga tidak dipakai sama sekali.
> Saya audit satu per satu dan bersihkan."

### "Kenapa pakai PHP native, bukan framework?"

> "Karena ketentuan tugasnya PHP native. Tapi struktur saya tetap memakai pola MVC
> yang biasa dipakai framework: ada front controller, controller, model, view, dan helper.
> Jadi alurnya mirip, hanya tanpa library tambahan."

### "Kenapa tidak ada `.htaccess`?"

> "Sengaja. Semua routing lewat parameter `url` di `public/index.php`, jadi tidak
> bergantung pada `mod_rewrite` Apache. Kelebihannya: jalan di konfigurasi server apa pun.
> Kekurangannya: URL-nya kurang cantik. Ini yang jadi catatan di README."

### "Apa yang belum selesai?"

Jawab dari README bagian 15. Yang paling penting:

> "Payment gateway belum ada — metode pembayaran baru dicatat, belum terhubung ke
> penyedia mana pun. Upload gambar juga belum, jadi admin memasukkan URL. Dan halaman
> login belum punya pembatasan percobaan, jadi masih bisa di-brute force."

Catatan: mengakui kekurangan justru menunjukkan kamu tahu batas karyamu.

### "Apa yang terjadi di kode ini?" (dosen menunjuk satu baris acak)

Kalau tidak tahu, jawab:

> "Baris itu bagian [sebut area-nya]. Saya perlu cek dulu untuk yakin."

Jangan mengarang. Mengarang dan salah jauh lebih buruk daripada mengakui.

---

## RENCANA BELAJAR 3 HARI

### Hari 1 — Alur dan Level 1 (2-3 jam)

- [ ] Baca bagian "Alur besar" sampai bisa digambar ulang tanpa lihat
- [ ] Buka `public/index.php`, baca bagian routing (baris 58-124)
- [ ] Pelajari konsep 1 (session/login)
- [ ] Pelajari konsep 2 (prepared statement)
- [ ] **Latihan:** buka `index.php?url=cart/add` di browser, lalu telusuri sendiri
      dari index.php sampai Cart::add() sampai query di Cart.php

### Hari 2 — Level 1 selesai, Level 2 mulai (2-3 jam)

- [ ] Pelajari konsep 3 (CSRF)
- [ ] Pelajari konsep 4 (escaping)
- [ ] Pelajari konsep 5 (password hash)
- [ ] Pelajari konsep 6 (transaksi + FOR UPDATE) — ini yang paling penting di Level 2
- [ ] **Latihan:** coba bayangkan skenario dua pembeli bersamaan, jelaskan pakai bahasamu

### Hari 3 — Level 2 selesai, latihan tanya-jawab (2-3 jam)

- [ ] Pelajari konsep 7 (IDOR) dan 8 (merge cart)
- [ ] Pelajari konsep 9 (slug)
- [ ] Baca bagian "Pertanyaan dosen", **jawab dengan suara** — jangan cuma dibaca
- [ ] Minta temanmu menunjuk baris acak di kode, kamu jelaskan
- [ ] Kalau ada yang tidak bisa dijawab, ulangi bagian itu

### Setelah 3 hari

Kamu tidak akan hafal semua. Tapi kamu akan bisa:

- Menjelaskan alur aplikasi dari awal sampai akhir
- Menjawab 5 pertanyaan tersulit dengan yakin
- Mengakui dengan baik apa yang belum kamu kuasai

Itu sudah cukup. Yang dinilai bukan hafalan — tapi apakah kamu paham apa yang
kamu serahkan.

---

## YANG TIDAK PERLU KAMU KUASAI

Supaya belajarmu fokus. Ini tidak akan ditanya:

- 1.406 baris CSS dan 345 baris responsive CSS — itu soal tampilan
- Cara kerja Bootstrap atau Google Fonts
- Konfigurasi Apache, XAMPP, atau phpMyAdmin
- 600 baris `zora.sql` — cukup tahu isinya 8 tabel dan 16 produk contoh
- Detail setiap helper di `functions.php` — cukup tahu `e()`, `csrf_field()`, `render()`

---

## CHECKLIST KESIAPAN

Centang kalau sudah bisa menjelaskan **tanpa buka catatan**:

- [ ] Alur permintaan dari browser sampai database dan balik lagi
- [ ] Beda tugas controller, model, dan view
- [ ] Kenapa prepared statement mencegah SQL Injection
- [ ] Kenapa form perlu token CSRF
- [ ] Kenapa output perlu di-escape
- [ ] Kenapa password harus di-hash
- [ ] Kenapa checkout perlu transaksi dan `FOR UPDATE`
- [ ] Kenapa `orders/6` tidak bisa dibuka orang lain
- [ ] Kenapa keranjang tamu tidak hilang setelah login

Kalau 9 kotak ini tercentang, kamu siap.

---

*Dokumen ini dibuat setelah audit kode pada 18 November 2026.*
