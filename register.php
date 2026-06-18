<?php
$page_title = 'Daftar Akun';
require_once __DIR__ . '/includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard_user.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validations
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Check if email already registered
        $stmt = $pdo->prepare("SELECT id_user FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain atau masuk.';
        } else {
            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password, role, premium_status, premium_until) VALUES (?, ?, ?, 'user', 0, NULL)");
            
            if ($insert->execute([$name, $email, $hashed_password])) {
                $success = 'Pendaftaran berhasil! Silakan masuk ke akun Anda.';
            } else {
                $error = 'Gagal mendaftarkan akun. Silakan coba lagi.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 450px; margin: 40px auto; padding: 20px;">
  <div class="card" style="background-color: var(--color-surface-card);">
    <h2 class="card-title" style="text-align: center; margin-bottom: 30px;">Daftar Akun Baru</h2>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <strong>Error:</strong> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success">
        <strong>Sukses:</strong> <?php echo $success; ?> 
        <a href="login.php" style="font-weight: 600; text-decoration: underline;">Masuk di sini</a>.
      </div>
    <?php endif; ?>

    <?php if (empty($success)): ?>
      <form action="register.php" method="POST">
        <div class="form-group">
          <label for="name" class="form-label">Nama Lengkap</label>
          <input type="text" id="name" name="name" class="form-input" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
        </div>

        <div class="form-group">
          <label for="email" class="form-label">Alamat Email</label>
          <input type="email" id="email" name="email" class="form-input" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Password (Min. 6 Karakter)</label>
          <input type="password" id="password" name="password" class="form-input" required>
        </div>

        <div class="form-group">
          <label for="confirm_password" class="form-label">Konfirmasi Password</label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px; margin-top: 15px;">Daftar Akun</button>
      </form>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--color-muted);">
      Sudah punya akun? <a href="login.php" style="font-weight: 500;">Masuk ke akun</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
