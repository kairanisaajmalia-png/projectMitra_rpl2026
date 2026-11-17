<?php
// ===== HALAMAN KATALOG (untuk pelanggan, tanpa login) =====

include 'koneksi.php';   // panggil koneksi database

// Variabel untuk header.php (layout publik = menu di bagian atas)
$layout = 'publik';
$act    = 'index.php';
$judul  = 'Katalog';


// ----- 0. TAMBAH PRODUK KE PESANAN (keranjang) -----
// Dijalankan saat tombol "Tambah Pesanan" ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $id_tambah = (int) $_POST['tambah'];

    // Pastikan produknya benar-benar ada
    $p = q("SELECT id_produk, nama_produk, stok FROM produk WHERE id_produk = ?", [$id_tambah]);

    if ($p) {
        $sudah = $_SESSION['cart'][$id_tambah] ?? 0;

        if ($sudah < $p[0]['stok']) {
            // Jumlah pesanan belum melebihi stok -> tambah 1
            $_SESSION['cart'][$id_tambah] = $sudah + 1;
            $_SESSION['flash'] = $p[0]['nama_produk'] . ' ditambahkan ke pesanan.';
        } else {
            $_SESSION['flash'] = 'Jumlah pesanan ' . $p[0]['nama_produk'] . ' sudah mencapai stok yang tersedia.';
        }
    }

    // Kembali ke katalog dengan pencarian/kategori yang sama (supaya tidak ter-submit ulang saat refresh)
    $balik = http_build_query(array_filter([
        'q'        => $_GET['q'] ?? '',
        'kategori' => $_GET['kategori'] ?? '',
    ]));
    pindah('index.php' . ($balik != '' ? '?' . $balik : ''));
}

// Pesan singkat setelah menambah pesanan (tampil sekali saja)
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Jumlah seluruh barang di pesanan
$jumlah_pesanan = array_sum($_SESSION['cart'] ?? []);


// ----- 1. AMBIL DATA PENCARIAN & FILTER -----
$cari = '';
if (isset($_GET['q'])) {
    // Amankan teks dari user sebelum dipakai di query
    $cari = mysqli_real_escape_string($koneksi, trim($_GET['q']));
}

$id_kategori = 0;
if (isset($_GET['kategori'])) {
    $id_kategori = (int) $_GET['kategori'];
}

// Ambil semua kategori untuk isi dropdown
$daftar_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori");


// ----- 2. AMBIL DATA PRODUK -----
$sql = "SELECT produk.*, kategori.nama_kategori
        FROM produk
        JOIN kategori ON produk.id_kategori = kategori.id_kategori
        WHERE produk.nama_produk LIKE '%$cari%'";

// Kalau user memilih kategori, tambahkan syarat kategori
if ($id_kategori != 0) {
    $sql = $sql . " AND produk.id_kategori = $id_kategori";
}

$sql = $sql . " ORDER BY produk.nama_produk";
$hasil = mysqli_query($koneksi, $sql);


// ----- 3. AMBIL NOMOR KONTAK BENGKEL (untuk tombol WhatsApp) -----
$info = mysqli_query($koneksi, "SELECT kontak FROM informasi_bengkel LIMIT 1");
$data_info = mysqli_fetch_assoc($info);

$nomor_wa = '';
if ($data_info && !empty($data_info['kontak'])) {
    // Bersihkan karakter selain angka (spasi, tanda plus, strip, dll)
    $kontak_bersih = preg_replace('/[^0-9]/', '', $data_info['kontak']);

    // Normalisasi format nomor ke awalan 62
    if (substr($kontak_bersih, 0, 1) === '0') {
        $nomor_wa = '62' . substr($kontak_bersih, 1);
    } elseif (substr($kontak_bersih, 0, 2) === '62') {
        $nomor_wa = $kontak_bersih;
    } else {
        $nomor_wa = '62' . $kontak_bersih;
    }
}

include 'includes/header.php';
?>

<!-- CSS untuk tampilan halaman ini -->
<style>
  /* Bagian atas: foto besar + judul + pencarian */
  .hero{display:grid;grid-template-columns:260px 1fr;gap:28px;align-items:center;margin-bottom:32px}
  .hero-img{background:#ddd;border:2px solid #111;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:8px}
  .hero-img img{width:100%;height:100%;object-fit:cover;display:block}
  .hero h1{margin:0 0 16px;color:#111}
  .hero h1 span{color:#c0392b}
  .cari-bar{display:flex;gap:12px;flex-wrap:wrap}
  .cari-bar input[name=q]{flex:1;min-width:200px}
  .cari-bar select{min-width:180px}

  /* Pesan setelah menambah pesanan + tombol lihat pesanan */
  .notif{background:#eafaf1;border:2px solid #27ae60;color:#1e8449;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-weight:700}
  .bar-pesanan{margin-top:14px}

  /* Kartu produk: foto di kiri, info di tengah, tombol di kanan */
  .produk-card{display:grid;grid-template-columns:200px 1fr auto;gap:24px;align-items:center;border:2px solid #111;border-radius:12px;padding:18px;margin-bottom:18px;background:#fff}
  .produk-foto{background:#ddd;aspect-ratio:4/3;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:8px}
  .produk-foto img{width:100%;height:100%;object-fit:cover;display:block}
  .produk-info h3{margin:0 0 6px;font-size:1.3rem;color:#111}
  .produk-info .kat{color:#555;margin-bottom:8px}
  .produk-info ul{margin:0;padding-left:20px}
  .produk-aksi{text-align:right}
  .produk-aksi .harga{font-size:1.3rem;font-weight:700;color:#c0392b;margin-bottom:12px}
  .produk-aksi form{margin:0 0 10px}
  .produk-aksi .di-pesanan{display:block;color:#555;font-size:.9rem;margin-top:6px}
  .btn-pesan{display:inline-block;background:#c0392b;color:#fff;padding:10px 26px;border:0;border-radius:30px;font-weight:700;cursor:pointer;font-size:1rem}
  .btn-wa{display:inline-block;background:#27ae60;color:#fff;padding:10px 26px;border-radius:30px;text-decoration:none;font-weight:700}
  .btn-habis{display:inline-block;background:#999;color:#fff;padding:10px 26px;border-radius:30px;font-weight:700}

  /* Tampilan HP: semua ditumpuk ke bawah */
  @media(max-width:700px){
    .hero{grid-template-columns:1fr}
    .produk-card{grid-template-columns:1fr}
    .produk-aksi{text-align:left}
  }
</style>

<!-- Pesan setelah tombol Tambah Pesanan ditekan -->
<?php if ($flash != '') { ?>
  <div class="notif"><?= e($flash) ?></div>
<?php } ?>

<!-- ===== BAGIAN ATAS (HERO LOGO BENGKEL) ===== -->
<div class="hero">
  <div class="hero-img">
    <?php
    // Deteksi otomatis berbagai kemungkinan ekstensi file logo di folder assets
    $logo_file = '';
    $nama_logo_pilihan = ['logoBengkel.jpeg', 'logoBengkel.jpg', 'logoBengkel.png', 'bengkel.jpg'];
    
    foreach ($nama_logo_pilihan as $file_cek) {
        if (file_exists(__DIR__ . '/assets/' . $file_cek)) {
            $logo_file = $file_cek;
            break;
        }
    }

    if ($logo_file != '') { ?>
      <!-- Tambahan ?v= time() agar browser tidak menampilkan cache gambar lama -->
      <img src="<?= BASE_URL ?>assets/<?= $logo_file ?>?v=<?= time() ?>" alt="Logo Bengkel">
    <?php } else { ?>
      <span>Foto bengkel</span>
    <?php } ?>
  </div>

  <div>
    <h1>Katalog <span><?= NAMA_BENGKEL ?></span></h1>

    <!-- Form pencarian + dropdown kategori -->
    <form class="cari-bar" method="get">
      <input name="q" placeholder="Cari produk" value="<?= e($cari) ?>">

      <select name="kategori" onchange="this.form.submit()">
        <option value="0">Semua kategori</option>
        <?php while ($k = mysqli_fetch_assoc($daftar_kategori)) { ?>
          <option value="<?= $k['id_kategori'] ?>" <?php if ($id_kategori == $k['id_kategori']) echo 'selected'; ?>>
            <?= e($k['nama_kategori']) ?>
          </option>
        <?php } ?>
      </select>

      <button class="btn btn-o" type="submit">Cari</button>
    </form>

    <!-- Tombol menuju halaman pembayaran (tampil kalau sudah ada pesanan) -->
    <?php if ($jumlah_pesanan > 0) { ?>
      <div class="bar-pesanan">
        <a class="btn" href="<?= BASE_URL ?>pembayaran.php">Lihat Pesanan (<?= $jumlah_pesanan ?>)</a>
      </div>
    <?php } ?>
  </div>
</div>

<!-- ===== DAFTAR PRODUK ===== -->

<!-- Kalau tidak ada produk, tampilkan tulisan ini -->
<?php if (mysqli_num_rows($hasil) == 0) { ?>
  <p class="muted">Produk tidak ditemukan.</p>
<?php } ?>

<!-- Tampilkan produk satu per satu (diulang selama masih ada data) -->
<?php while ($x = mysqli_fetch_assoc($hasil)) { ?>
  <div class="produk-card">

    <!-- Foto produk -->
    <div class="produk-foto">
      <?php if (!empty($x['foto']) && file_exists(__DIR__ . '/uploads/' . $x['foto'])) { ?>
        <img src="<?= BASE_URL ?>uploads/<?= e($x['foto']) ?>" alt="<?= e($x['nama_produk']) ?>">
      <?php } else { ?>
        <span>Gambar produk</span>
      <?php } ?>
    </div>

    <!-- Nama, kategori, dan poin informasi -->
    <div class="produk-info">
      <h3><?= e($x['nama_produk']) ?></h3>
      <div class="kat"><?= e($x['nama_kategori']) ?></div>
      <ul>
        <li>Kode produk: <?= e($x['kode_produk']) ?></li>
        <li>Stok tersedia: <?= $x['stok'] ?></li>
      </ul>
    </div>

    <!-- Harga dan tombol -->
    <div class="produk-aksi">
      <div class="harga"><?= rupiah($x['harga']) ?></div>

      <?php if ($x['stok'] > 0) { ?>
        <!-- Tombol tambah ke pesanan (muncul di halaman pembayaran) -->
        <form method="post">
          <input type="hidden" name="tambah" value="<?= $x['id_produk'] ?>">
          <button class="btn-pesan" type="submit">+ Tambah Pesanan</button>
          <?php $di_keranjang = $_SESSION['cart'][$x['id_produk']] ?? 0; ?>
          <?php if ($di_keranjang > 0) { ?>
            <span class="di-pesanan">Di pesanan: <?= $di_keranjang ?></span>
          <?php } ?>
        </form>

        <!-- Tombol membuka WhatsApp bengkel dengan pesan otomatis -->
        <a class="btn-wa" target="_blank" rel="noopener"
            href="https://wa.me/<?= $nomor_wa ?>?text=<?= urlencode('Halo, saya mau tanya produk ' . $x['nama_produk']) ?>">
          Hubungi Kami
        </a>
      <?php } else { ?>
        <span class="btn-habis">Stok habis</span>
      <?php } ?>
    </div>

  </div>
<?php } ?>

<?php include 'includes/footer.php'; ?>