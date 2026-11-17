<?php
session_start();
require 'koneksi.php';

$error = "";

if (isset($_POST['submit'])) {
  $username = trim($_POST['username']);
  $password = $_POST['password'];

  $stmt = $koneksi->prepare(
    "SELECT id_pengguna, username, password, nama FROM pengguna WHERE username = ?"
  );
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  // Mendukung password hash (aman) dan password teks biasa (data awalmu)
  $cocok = $user && (
    password_verify($password, $user['password']) ||
    hash_equals($user['password'], $password)
  );

  if ($cocok) {
    session_regenerate_id(true);
    $_SESSION['id_pengguna'] = $user['id_pengguna'];
    $_SESSION['username']    = $user['username'];
    $_SESSION['nama']        = $user['nama'];
    header("Location: dashboard.php");
    exit;
  } else {
    $error = "Username atau password salah";
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Bengkel</title>

  <!-- Bootstrap -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">

  <link rel="stylesheet" href="style.css">

  <!-- Font Awesome -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    rel="stylesheet">
</head>

<body style="background-color: #ffffff;">

  <div class="container-fluid">

    <div class="row min-vh-100">

      <!-- SIDEBAR -->
      <div class="col-md-7 p-5 d-flex flex-column align-items-center justify-content-center"
        style="background-color: #374151;">

        <img
          src="LOGO.png"
          alt="Logo Zio Motor"
          width="450"
          height="450"
          class="rounded-circle mb-3"
          style="object-fit: cover;">

        <h1 class="text-white fw-bold">
          Zio Motor
        </h1>

      </div>


      <!-- CONTENT -->
      <div class="col-md-5 p-5 d-flex flex-column justify-content-center">

        <!-- JUDUL -->
        <div class="mb-1">

          <h2>
            Login
          </h2>

          <p class="text-muted">
            Welcome to Bengkel
          </p>

        </div>


        <!-- FORM -->
        <form method="POST" action="">

          <!-- USERNAME -->
          <div class="mb-4">

            <label
              for="username"
              class="form-label">
              Username
            </label>

            <input
              type="text"
              class="form-control"
              id="username"
              name="username"
              placeholder="Masukkan username"
              value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
              required>

          </div>


          <!-- PASSWORD -->
          <div class="mb-4">

            <label
              for="password"
              class="form-label">
              Password
            </label>

            <input
              type="password"
              class="form-control"
              id="password"
              name="password"
              placeholder="Masukkan password"
              required>

          </div>


          <!-- PESAN ERROR -->
          <?php if ($error != "") { ?>
            <div class="alert alert-danger py-2" role="alert">
              <?= $error ?>
            </div>
          <?php } ?>


          <!-- BUTTON -->
          <div class="mb-4">

            <button
              type="submit"
              name="submit"
              class="btn btn-primary w-100 py-2">
              Login
            </button>

          </div>

        </form>

      </div>

    </div>

  </div>


  <!-- Bootstrap JS -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
  </script>

</body>

</html>