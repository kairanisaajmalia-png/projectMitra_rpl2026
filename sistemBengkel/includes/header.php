<?php
// ===== HEADER (bagian atas semua halaman) =====
include_once __DIR__ . '/../koneksi.php';

// Ambil data user yang sedang login (kalau belum login isinya kosong)
$u = '';
if (isset($_SESSION['user'])) {
    $u = $_SESSION['user'];
}

// Nilai bawaan kalau halaman tidak mengisi variabel ini
if (!isset($layout)) { $layout = 'publik'; }
if (!isset($act))    { $act = ''; }
if (!isset($crumb))  { $crumb = ''; }
if (!isset($judul))  { $judul = ''; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($judul) ?> | <?= NAMA_BENGKEL ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;700&display=swap">
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body class="<?= $layout ?>">

<?php if ($layout == 'publik') { ?>

  <!-- ===== TAMPILAN PUBLIK (untuk pelanggan): menu di atas ===== -->
  <nav class="nav">
    <a class="brand" href="<?= BASE_URL ?>index.php"><?= logo() . NAMA_BENGKEL ?></a>
    <ul>
      <li><a href="<?= BASE_URL ?>index.php" class="<?php if ($act == 'index.php') echo 'on'; ?>">Katalog</a></li>
      <li><a href="<?= BASE_URL ?>informasi.php" class="<?php if ($act == 'informasi.php') echo 'on'; ?>">Informasi Bengkel</a></li>
      <li><a href="<?= BASE_URL ?>pembayaran.php" class="<?php if ($act == 'pembayaran.php') echo 'on'; ?>">Pembayaran</a></li>
      <li>
        <?php if ($u != '') { ?>
          <a class="btn-line" href="<?= BASE_URL ?>logout.php">Logout</a>
        <?php } else { ?>
          <a class="btn-line" href="<?= BASE_URL ?>login.php">Login</a>
        <?php } ?>
      </li>
    </ul>
  </nav>
  <div class="wrap">

<?php } else { ?>

  <!-- ===== TAMPILAN PANEL (untuk owner / kasir): menu di samping ===== -->
  <?php
  // Kalau yang login adalah owner, tampilkan menu owner
  // Kalau bukan (kasir), tampilkan menu kelola katalog
  if ($u != '' && $u['username'] == 'owner') {
      $nama_peran = 'Owner';
      $menu = array(
          'index.php'           => 'Lihat Katalog',
          'informasi.php'       => 'Informasi Bengkel',
          'owner/transaksi.php' => 'Transaksi Pembayaran'
      );
  } else {
      $nama_peran = 'Kasir';
      $menu = array(
          'admin/produk.php'     => 'Kelola Katalog',
          'admin/informasi.php'  => 'Kelola Informasi Bengkel'
      );
  }

  // Kalau breadcrumb kosong, pakai nama peran
  if ($crumb == '') { $crumb = $nama_peran; }
  ?>

  <aside class="side">
    <div class="brand"><?= logo() . NAMA_BENGKEL ?></div>
    <small><?= e($nama_peran) ?></small>

    <!-- Tampilkan menu satu per satu -->
    <?php foreach ($menu as $alamat => $nama_menu) { ?>
      <a class="m <?php if ($act == $alamat) echo 'on'; ?>" href="<?= BASE_URL . $alamat ?>"><?= $nama_menu ?></a>
    <?php } ?>

    <a class="out" href="<?= BASE_URL ?>logout.php">Logout</a>
  </aside>

  <main class="main">
    <div class="crumb"><?= e($crumb) ?></div>

<?php } ?>