<?php

include '../koneksi.php';   // panggil koneksi database
cekLogin();                 // hanya yang sudah login boleh masuk

// Variabel untuk tampilan layout (dipakai header.php)
$layout = 'panel';
$act    = 'admin/produk.php';
$judul  = 'Kelola Katalog';
$crumb  = 'kasir';

$pesan = '';   // untuk menyimpan pesan kalau produk gagal dihapus


// ----- 1. PROSES HAPUS PRODUK -----
// Dijalankan kalau tombol "Ya, hapus" di modal konfirmasi ditekan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hapus'])) {
    $id_hapus = (int) $_POST['hapus'];   // (int) supaya pasti berupa angka

    // Cek dulu: apakah produk ini sudah pernah dipakai di transaksi?
    $cek = mysqli_query($koneksi, "SELECT COUNT(*) AS jumlah FROM detail_transaksi WHERE id_produk = $id_hapus");
    $data_cek = mysqli_fetch_assoc($cek);

    if ($data_cek['jumlah'] > 0) {
        $pesan = 'Produk tidak bisa dihapus karena sudah dipakai di transaksi.';
    } else {
        // Ambil nama file foto dulu supaya fotonya bisa ikut dihapus
        $ambil = mysqli_query($koneksi, "SELECT foto FROM produk WHERE id_produk = $id_hapus");
        $data_foto = mysqli_fetch_assoc($ambil);

        // Gunakan try-catch untuk mengantisipasi error relasi tabel lain (foreign key)
        try {
            $hapus_sukses = mysqli_query($koneksi, "DELETE FROM produk WHERE id_produk = $id_hapus");
        } catch (mysqli_sql_exception $ex) {
            $hapus_sukses = false;
        }

        if ($hapus_sukses) {
            // Hapus file foto di folder uploads (kalau ada)
            if ($data_foto && $data_foto['foto'] != '') {
                $lokasi_foto = '../uploads/' . basename($data_foto['foto']);
                if (file_exists($lokasi_foto)) {
                    unlink($lokasi_foto);
                }
            }
            pindah('admin/produk.php');   // kembali ke halaman daftar
        } else {
            $pesan = 'Produk tidak bisa dihapus karena masih terhubung dengan data lain.';
        }
    }
}


// ----- 2. AMBIL DATA UNTUK PENCARIAN & FILTER -----
$cari = '';
if (isset($_GET['q'])) {
    // mysqli_real_escape_string untuk mengamankan teks dari user sebelum masuk query
    $cari = mysqli_real_escape_string($koneksi, trim($_GET['q']));
}

$id_kategori = 0;
if (isset($_GET['kategori'])) {
    $id_kategori = (int) $_GET['kategori'];
}

// Ambil semua kategori untuk isi dropdown
$daftar_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori");


// ----- 3. AMBIL DATA PRODUK -----
// JOIN dipakai untuk menggabungkan tabel produk dengan tabel kategori
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


// ----- 4. DATA UNTUK POPUP KONFIRMASI HAPUS -----
// Popup muncul kalau di alamat ada ?hapus=ID
$produk_hapus = null;
if (isset($_GET['hapus'])) {
    $id_popup = (int) $_GET['hapus'];
    $ambil_popup = mysqli_query($koneksi, "SELECT * FROM produk WHERE id_produk = $id_popup");
    $produk_hapus = mysqli_fetch_assoc($ambil_popup);
}

include '../includes/header.php';
?>

<!-- CSS untuk tampilan halaman ini -->
<style>
  .kat-bar{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:20px}
  .kat-bar input[name=q]{flex:1;min-width:180px}
  .prod-card{display:grid;grid-template-columns:160px 1fr;gap:20px;background:#fff;padding:18px;margin-bottom:16px;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,0.1)}
  .prod-img{background:#ddd;aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:2px}
  .prod-img img{width:100%;height:100%;object-fit:cover;display:block}
  .prod-info h3{margin:0 0 4px;font-size:1.25rem;color:#333}
  .prod-info .kat{font-size:.85rem;color:#666;margin-bottom:6px}
  .prod-info .harga{font-weight:700;color:#2c3e50;margin-bottom:10px}
  .err{color: #d9534f; background: #f2dede; padding: 10px; border-radius: 4px; margin-bottom: 15px;}
  @media(max-width:600px){.prod-card{grid-template-columns:1fr}}
</style>

<h1>Katalog Produk</h1>

<!-- Pesan kalau produk gagal dihapus -->
<?php if (!empty($pesan)): ?>
    <div class="err"><?= e($pesan) ?></div>
<?php endif; ?>

<!-- Form pencarian dan filter kategori -->
<form class="kat-bar" method="get">
  <input name="q" placeholder="Cari produk..." value="<?= e($_GET['q'] ?? '') ?>">

  <select name="kategori">
    <option value="0">Semua kategori</option>
    <?php while ($k = mysqli_fetch_assoc($daftar_kategori)) { ?>
      <option value="<?= $k['id_kategori'] ?>" <?php if ($id_kategori == $k['id_kategori']) echo 'selected'; ?>>
        <?= e($k['nama_kategori']) ?>
      </option>
    <?php } ?>
  </select>

  <button class="btn btn-o" type="submit">Cari</button>
  <a class="btn" href="produk_form.php">+ Tambah Produk</a>
</form>

<!-- Kalau tidak ada produk, tampilkan tulisan ini -->
<?php if (mysqli_num_rows($hasil) == 0) { ?>
  <p class="muted">Produk tidak ditemukan.</p>
<?php } ?>

<!-- Tampilkan produk satu per satu (diulang selama masih ada data) -->
<?php while ($x = mysqli_fetch_assoc($hasil)) { ?>
  <div class="prod-card">
    <div class="prod-img">
      <?php if (!empty($x['foto']) && file_exists('../uploads/' . $x['foto'])) { ?>
        <img src="../uploads/<?= e($x['foto']) ?>" alt="<?= e($x['nama_produk']) ?>">
      <?php } else { ?>
        <span class="muted" style="font-size: 0.8rem;">Tanpa Foto</span>
      <?php } ?>
    </div>

    <div class="prod-info">
      <h3><?= e($x['nama_produk']) ?></h3>
      <div class="kat">Kode: <?= e($x['kode_produk']) ?> | Kategori: <?= e($x['nama_kategori']) ?></div>
      <div class="kat">Stok: <?= $x['stok'] ?></div>
      <div class="harga"><?= rupiah($x['harga']) ?></div>

      <div class="actions">
        <a class="btn btn-o btn-sm" href="produk_form.php?id=<?= $x['id_produk'] ?>">Ubah</a>
        <a class="btn btn-hapus btn-sm" href="?hapus=<?= $x['id_produk'] ?>">Hapus</a>
      </div>
    </div>
  </div>
<?php } ?>

<!-- Popup konfirmasi hapus (muncul kalau $produk_hapus ada isinya) -->
<?php if ($produk_hapus) { ?>
  <div class="modal-bg">
    <div class="modal">
      <h2>Hapus produk?</h2>
      <p class="muted"><b><?= e($produk_hapus['nama_produk']) ?></b> akan dihapus permanen dan tidak bisa dikembalikan.</p>
      <form method="post">
        <a class="btn btn-o" href="produk.php">Batal</a>
        <button class="btn btn-d" name="hapus" value="<?= $produk_hapus['id_produk'] ?>">Ya, hapus</button>
      </form>
    </div>
  </div>
<?php } ?>

<?php include '../includes/footer.php'; ?>