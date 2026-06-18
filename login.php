<?php
$page_title = 'Masuk Akun';
require_once __DIR__ . '/includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_user.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi.';
    } else {
        // Find user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_premium'] = $user['premium_status'];

            // Route user based on role
            if ($user['role'] === 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard_user.php");
            }
            exit;
        } else {
            $error = 'Email atau password salah. Silakan coba lagi.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 450px; margin: 40px auto; padding: 20px;">
  <div class="card" style="background-color: var(--color-surface-card);">
    <h2 class="card-title" style="text-align: center; margin-bottom: 30px;">Masuk ke Akun Anda</h2>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <strong>Error:</strong> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <div class="form-group">
        <label for="email" class="form-label">Alamat Email</label>
        <input type="email" id="email" name="email" class="form-input" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
      </div>

      <div class="form-group" style="margin-bottom: 25px;">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-input" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; height: 44px;">Masuk Sekarang</button>
    </form>

    <div style="text-align: center; margin-top: 20px; font-size: 14px; color: var(--color-muted);">
      Belum punya akun? <a href="register.php" style="font-weight: 500;">Daftar akun baru</a>
    </div>

    <!-- Quick access credentials helper for easy testing -->
    <div style="margin-top: 30px; padding: 12px; border: 1px dashed var(--color-hairline); border-radius: var(--rounded-md); font-size: 12px; background-color: var(--color-surface-soft);">
      <p style="font-weight: 600; color: var(--color-ink); margin-bottom: 4px;">Akun Demo Pengujian:</p>
      <ul style="list-style: none; padding-left: 0;">
        <li><strong>Admin:</strong> admin@unsent.com / admin123</li>
        <li><strong>User Biasa:</strong> jane@example.com / user123</li>
        <li><strong>User Premium:</strong> john@example.com / user123</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
