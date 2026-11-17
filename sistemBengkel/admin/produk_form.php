<?php

include '../koneksi.php';   // panggil koneksi database
cekLogin();                 // hanya yang sudah login boleh masuk

$layout = 'panel';
$act    = 'admin/produk.php';

// Ambil id dari alamat. Kalau tidak ada, id = 0 (mode tambah)
$id = 0;
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
}

// Isi awal form (kosong semua)
$kode_produk = '';
$id_kategori = '';
$nama_produk = '';
$harga       = '';
$stok        = '';
$foto        = '';

// Kalau mode ubah, ambil data lama dari database
if ($id != 0) {
    $ambil = mysqli_query($koneksi, "SELECT * FROM produk WHERE id_produk = $id");
    $data  = mysqli_fetch_assoc($ambil);

    if ($data) {
        $kode_produk = $data['kode_produk'];
        $id_kategori = $data['id_kategori'];
        $nama_produk = $data['nama_produk'];
        $harga       = $data['harga'];
        $stok        = $data['stok'];
        $foto        = $data['foto'];
    }
    $judul = 'Ubah Katalog';
    $crumb = 'Kelola Katalog / Ubah';
} else {
    $judul = 'Tambah Katalog';
    $crumb = 'Kelola Katalog / Tambah';
}

$err = array();   // tempat menyimpan pesan error


// ----- PROSES SIMPAN (dijalankan saat tombol Simpan ditekan) -----
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Ambil data dari form
    $kode_produk = trim($_POST['kode_produk']);
    $id_kategori = $_POST['id_kategori'];
    $nama_produk = trim($_POST['nama_produk']);
    $harga       = trim($_POST['harga']);
    $stok        = trim($_POST['stok']);

    // --- Validasi: cek apakah isinya sudah benar ---
    if ($kode_produk == '') {
        $err['kode_produk'] = 'Kode produk wajib diisi';
    } else {
        // Kode produk tidak boleh sama dengan produk lain
        $kode_aman = mysqli_real_escape_string($koneksi, $kode_produk);
        $cek_kode = mysqli_query($koneksi, "SELECT id_produk FROM produk WHERE kode_produk = '$kode_aman' AND id_produk != $id");
        if (mysqli_num_rows($cek_kode) > 0) {
            $err['kode_produk'] = 'Kode produk sudah dipakai';
        }
    }

    if ($nama_produk == '') {
        $err['nama_produk'] = 'Nama produk wajib diisi';
    }

    if ($id_kategori == '') {
        $err['id_kategori'] = 'Pilih kategori';
    }

    // ctype_digit = cek apakah isinya angka semua
    if (!ctype_digit($harga)) {
        $err['harga'] = 'Harga wajib diisi dengan angka';
    }

    if (!ctype_digit($stok)) {
        $err['stok'] = 'Stok wajib diisi dengan angka';
    }

    // --- Cek foto (hanya kalau user memilih file) ---
    $foto_baru = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['name'] != '') {
        $nama_file = $_FILES['foto']['name'];
        $ukuran    = $_FILES['foto']['size'];
        $tmp       = $_FILES['foto']['tmp_name'];

        // Ambil ekstensi file (jpg, png, dll) dan jadikan huruf kecil
        $ekstensi = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

        if (!in_array($ekstensi, array('jpg', 'jpeg', 'jpe', 'png', 'webp'))) {
            $err['foto'] = 'Foto harus berformat JPG, PNG, atau WEBP';
        } elseif ($ukuran > (2 * 1024 * 1024)) { // Batas maksimal 2 MB
            $err['foto'] = 'Ukuran foto maksimal 2 MB';
        } else {
            // Buat nama baru yang unik supaya tidak bentrok dengan foto lain
            $foto_baru = uniqid('p_') . '.' . $ekstensi;
        }
    }

    // --- Kalau data aman dan ada foto baru, pindahkan foto ke folder uploads dulu ---
    if (count($err) == 0 && $foto_baru != '') {

        // Kalau folder uploads belum ada, buat otomatis
        if (!is_dir('../uploads')) {
            mkdir('../uploads', 0755, true);
        }

        // move_uploaded_file mengembalikan true kalau berhasil
        if (move_uploaded_file($tmp, '../uploads/' . $foto_baru)) {

            // Hapus foto lama (kalau ada) supaya folder tidak penuh
            if ($foto != '' && file_exists('../uploads/' . $foto)) {
                unlink('../uploads/' . $foto);
            }
            $foto = $foto_baru;   // pakai nama foto yang baru
        } else {
            $err['foto'] = 'Foto gagal disimpan ke folder uploads';
        }
    }

    // --- Kalau masih tidak ada error (data dan foto aman), simpan ke database ---
    if (count($err) == 0) {

        // Amankan teks sebelum masuk query
        $kode_db = mysqli_real_escape_string($koneksi, $kode_produk);
        $nama_db = mysqli_real_escape_string($koneksi, $nama_produk);
        $foto_db = mysqli_real_escape_string($koneksi, $foto);
        $kat_db  = (int) $id_kategori;
        $harga_db = (int) $harga;
        $stok_db  = (int) $stok;

        if ($id != 0) {
            // Mode ubah: UPDATE data yang sudah ada
            $sql = "UPDATE produk SET
                        kode_produk = '$kode_db',
                        id_kategori = $kat_db,
                        nama_produk = '$nama_db',
                        harga = $harga_db,
                        stok = $stok_db,
                        foto = '$foto_db'
                    WHERE id_produk = $id";
        } else {
            // Mode tambah: INSERT data baru
            $sql = "INSERT INTO produk (kode_produk, id_kategori, nama_produk, harga, stok, foto)
                    VALUES ('$kode_db', $kat_db, '$nama_db', $harga_db, $stok_db, '$foto_db')";
        }

        mysqli_query($koneksi, $sql);
        pindah('admin/produk.php');   // kembali ke daftar produk
    }
}

// Ambil daftar kategori untuk isi dropdown
$daftar_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori");

// Fungsi kecil untuk menampilkan pesan error di bawah kolom
function pesan_error($nama_kolom) {
    global $err;
    if (isset($err[$nama_kolom])) {
        return '<div class="err" style="color: #d9534f; font-size: 0.85rem; margin-top: 4px;">' . $err[$nama_kolom] . '</div>';
    }
    return '';
}

include '../includes/header.php';
?>

<!-- CSS untuk tampilan form -->
<style>
  .prod-wrap{display:grid;grid-template-columns:240px 1fr;gap:24px;align-items:start}
  .prod-foto{background:#ddd;border:1px solid #bbb;aspect-ratio:1/1;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:10px;border-radius:4px}
  .prod-foto img{width:100%;height:100%;object-fit:cover;display:block}
  .form > div > div{margin-bottom: 16px;}
  .form label{display: block; font-weight: 600; margin-bottom: 6px;}
  .form input[type="text"], .form select{width: 100%; padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px;}
  @media(max-width:700px){.prod-wrap{grid-template-columns:1fr}}
</style>

<h1><?= $judul ?></h1>

<!-- enctype="multipart/form-data" WAJIB ada supaya form bisa mengirim file foto -->
<form method="post" enctype="multipart/form-data">
  <div class="card form prod-wrap">

    <!-- Kolom kiri: foto produk -->
    <div>
      <label>Gambar produk</label>
      <div class="prod-foto">
        <?php if ($foto != '') { ?>
          <img src="../uploads/<?= e($foto) ?>" alt="Gambar produk">
        <?php } else { ?>
          <span class="muted" style="font-size: 0.85rem;">Belum ada gambar</span>
        <?php } ?>
      </div>
      <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" style="width: 100%;">
      <?= pesan_error('foto') ?>
    </div>

    <!-- Kolom kanan: data produk -->
    <div>
      <div>
        <label>Kode Produk</label>
        <input type="text" name="kode_produk" placeholder="Contoh: PR-001" value="<?= e($kode_produk) ?>">
        <?= pesan_error('kode_produk') ?>
      </div>

      <div>
        <label>Nama Produk</label>
        <input type="text" name="nama_produk" placeholder="Nama produk" value="<?= e($nama_produk) ?>">
        <?= pesan_error('nama_produk') ?>
      </div>

      <div>
        <label>Kategori</label>
        <select name="id_kategori">
          <option value="">Pilih kategori</option>
          <?php while ($k = mysqli_fetch_assoc($daftar_kategori)) { ?>
            <option value="<?= $k['id_kategori'] ?>" <?php if ($id_kategori == $k['id_kategori']) echo 'selected'; ?>>
              <?= e($k['nama_kategori']) ?>
            </option>
          <?php } ?>
        </select>
        <?= pesan_error('id_kategori') ?>
      </div>

      <div>
        <label>Harga (Rp)</label>
        <input type="text" name="harga" placeholder="Contoh: 70000" value="<?= e($harga) ?>">
        <?= pesan_error('harga') ?>
      </div>

      <div>
        <label>Stok</label>
        <input type="text" name="stok" placeholder="Jumlah stok" value="<?= e($stok) ?>">
        <?= pesan_error('stok') ?>
      </div>

      <div style="margin-top: 20px;">
        <button class="btn btn-d" type="submit">Simpan</button>
        <a class="btn btn-o" href="produk.php">Batal</a>
      </div>
    </div>

  </div>
</form>

<?php include '../includes/footer.php'; ?>