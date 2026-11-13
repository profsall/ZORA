<?php
/**
 * Ambil data pengguna yang sedang login.
 */
$pengguna = current_user();

// Nomor telepon, tampilkan keterangan kalau belum diisi
$telepon = $pengguna['phone'];

if (!$telepon) {
    $telepon = 'Belum diisi';
}
?>
<section class="container standard-page">

    <div class="page-title">
        <span class="eyebrow">AKUN ZORA</span>
        <h1>Halo, <?= e($pengguna['name']) ?>.</h1>
        <p>Kelola informasi akun dan lihat aktivitas belanjamu.</p>
    </div>

    <div class="account-grid">

        <!-- ================= INFORMASI AKUN ================= -->
        <div class="account-panel">
            <h3>Informasi akun</h3>

            <p>
                <small>Nama</small>
                <strong><?= e($pengguna['name']) ?></strong>
            </p>

            <p>
                <small>Email</small>
                <strong><?= e($pengguna['email']) ?></strong>
            </p>

            <p>
                <small>Nomor telepon</small>
                <strong><?= e($telepon) ?></strong>
            </p>
        </div>

        <!-- ================= MENU AKUN ================= -->
        <div class="account-panel account-links">
            <h3>Menu akun</h3>

            <a href="<?= base_url('orders') ?>">Riwayat pesanan <span>→</span></a>

            <form method="post" action="<?= base_url('logout') ?>">
                <?= csrf_field() ?>
                <button class="subtle-button">Keluar dari akun →</button>
            </form>
        </div>

    </div>
</section>
