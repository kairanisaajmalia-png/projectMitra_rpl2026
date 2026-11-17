<?php
include 'koneksi.php';

$judul = 'Informasi Bengkel';
include 'includes/header.php';

$info = q("SELECT * FROM informasi_bengkel LIMIT 1");
$data = $info[0] ?? [];
?>

<h1>Informasi & Lokasi Bengkel</h1>

<div class="card">
    <?php if ($data): ?>
        <p><strong>Alamat:</strong> <?= e($data['alamat']) ?></p>
        <p><strong>Jam Operasional:</strong> <?= e($data['jam_operasional']) ?></p>
        <p><strong>Kontak / WA:</strong> <?= e($data['kontak']) ?></p>
        <?php if (!empty($data['peta'])): ?>
            <p><a class="btn btn-o" href="<?= e($data['peta']) ?>" target="_blank" rel="noopener">Lihat di Google Maps</a></p>
        <?php endif; ?>
    <?php else: ?>
        <p class="muted">Informasi bengkel belum diatur.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>