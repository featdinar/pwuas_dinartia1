<?php
$page_title = 'Profil Pengguna';
require_once __DIR__ . '/includes/config.php';

// Enforce login
requireLogin();

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Get fresh user details from DB
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Gagal memuat data profil.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);

        if (empty($name) || empty($email)) {
            $error = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Check if email already registered by another user
            $check = $pdo->prepare("SELECT id_user FROM users WHERE email = ? AND id_user != ?");
            $check->execute([$email, $user_id]);
            if ($check->fetch()) {
                $error = 'Email sudah digunakan oleh akun lain.';
            } else {
                // Update user details
                $update = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id_user = ?");
                if ($update->execute([$name, $email, $user_id])) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $success = 'Detail profil berhasil diperbarui.';
                    // Refresh local copy
                    $user['name'] = $name;
                    $user['email'] = $email;
                } else {
                    $error = 'Gagal memperbarui profil.';
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Semua field password wajib diisi.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password baru minimal harus 6 karakter.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            // Check old password
            if (password_verify($old_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $update = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                if ($update->execute([$hashed_password, $user_id])) {
                    // Update current array to avoid immediate failures on double change
                    $user['password'] = $hashed_password;
                    $success = 'Password berhasil diubah.';
                } else {
                    $error = 'Gagal mengubah password.';
                }
            } else {
                $error = 'Password lama Anda salah.';
            }
        }
    }
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="dashboard-grid">
  <!-- Sidebar -->
  <div class="sidebar-card">
    <h3 style="font-size: 20px; border-bottom: 1px solid var(--color-hairline); padding-bottom: 10px; margin-bottom: 15px;">Akun Saya</h3>
    <p style="font-size: 14px; font-weight: 500;"><?php echo sanitize($user['name']); ?></p>
    <p style="font-size: 12px; color: var(--color-muted);"><?php echo sanitize($user['email']); ?></p>
    
    <ul class="sidebar-menu">
      <li>
        <a href="<?php echo isAdmin() ? 'dashboard_admin.php' : 'dashboard_user.php'; ?>">
          <span>📊</span> Dashboard
        </a>
      </li>
      <li>
        <a href="profile.php" class="active">
          <span>⚙️</span> Pengaturan Profil
        </a>
      </li>
      <?php if (!isAdmin()): ?>
        <li>
          <a href="my_messages.php">
            <span>✉️</span> Pesan Saya
          </a>
        </li>
        <li>
          <a href="premium.php">
            <span>★</span> Upgrade Premium
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </div>

  <!-- Main profile panel -->
  <div>
    <h2>Pengaturan Profil</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Kelola informasi profil Anda dan ubah kata sandi.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <strong>Error:</strong> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success">
        <strong>Sukses:</strong> <?php echo $success; ?>
      </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-xl);">
      
      <!-- Profile details form -->
      <div class="card" style="background-color: var(--color-surface-card);">
        <h3 style="font-size: 20px; margin-bottom: 20px;">Ubah Detail Profil</h3>
        <form action="profile.php" method="POST">
          <div class="form-group">
            <label for="name" class="form-label">Nama Lengkap</label>
            <input type="text" id="name" name="name" class="form-input" required value="<?php echo sanitize($user['name']); ?>">
          </div>
          
          <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-input" required value="<?php echo sanitize($user['email']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label">Status Keanggotaan</label>
            <?php if ($user['role'] === 'admin'): ?>
              <span class="badge badge-dark">ADMINISTRATOR</span>
            <?php elseif (isPremium()): ?>
              <span class="badge badge-coral">★ PREMIUM SUBSCRIBER</span>
              <div style="font-size: 12px; color: var(--color-muted); margin-top: 6px;">
                Berlaku hingga: <?php echo date('d-m-Y H:i', strtotime($user['premium_until'])); ?>
              </div>
            <?php else: ?>
              <span class="badge badge-cream">STANDARD USER</span>
              <div style="margin-top: 8px;">
                <a href="premium.php" class="btn btn-primary" style="height: 30px; font-size:12px; padding:0 12px;">Upgrade Premium</a>
              </div>
            <?php endif; ?>
          </div>

          <button type="submit" name="update_profile" class="btn btn-primary" style="width: 100%; margin-top: 15px;">Simpan Perubahan</button>
        </form>
      </div>

      <!-- Password change form -->
      <div class="card" style="background-color: var(--color-surface-card);">
        <h3 style="font-size: 20px; margin-bottom: 20px;">Ganti Password</h3>
        <form action="profile.php" method="POST">
          <div class="form-group">
            <label for="old_password" class="form-label">Password Lama</label>
            <input type="password" id="old_password" name="old_password" class="form-input" required>
          </div>

          <div class="form-group">
            <label for="new_password" class="form-label">Password Baru (Min. 6 Karakter)</label>
            <input type="password" id="new_password" name="new_password" class="form-input" required>
          </div>

          <div class="form-group">
            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
          </div>

          <button type="submit" name="change_password" class="btn btn-secondary" style="width: 100%; margin-top: 15px;">Ganti Password</button>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
