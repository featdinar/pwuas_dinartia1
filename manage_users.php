<?php
$page_title = 'Kelola User';
require_once __DIR__ . '/includes/config.php';

// Enforce admin access
requireAdmin();

$error = '';
$success = '';
$edit_user = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. UPDATE USER
    if (isset($_POST['update_user'])) {
        $id_user = (int)$_POST['id_user'];
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password']; // optional
        $role = sanitize($_POST['role']);
        $premium_status = isset($_POST['premium_status']) ? 1 : 0;
        $premium_until = !empty($_POST['premium_until']) ? $_POST['premium_until'] : null;

        if (empty($name) || empty($email)) {
            $error = 'Nama dan email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format email tidak valid.';
        } else {
            // Check email uniqueness
            $check = $pdo->prepare("SELECT id_user FROM users WHERE email = ? AND id_user != ?");
            $check->execute([$email, $id_user]);
            if ($check->fetch()) {
                $error = 'Email sudah terdaftar pada user lain.';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Update basic details
                    $update = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, role = ?, premium_status = ?, premium_until = ? 
                        WHERE id_user = ?
                    ");
                    $update->execute([$name, $email, $role, $premium_status, $premium_until, $id_user]);
                    
                    // Update password if provided
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $update_pwd = $pdo->prepare("UPDATE users SET password = ? WHERE id_user = ?");
                        $update_pwd->execute([$hashed_password, $id_user]);
                    }
                    
                    $pdo->commit();
                    $success = 'Detail user berhasil diperbarui.';
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $error = 'Kesalahan database: ' . $e->getMessage();
                }
            }
        }
    }
    
    // 3. DELETE USER
    elseif (isset($_POST['delete_user'])) {
        $id_user = (int)$_POST['delete_id'];
        
        // Prevent deleting oneself
        if ($id_user === $_SESSION['user_id']) {
            $error = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.';
        } else {
            try {
                $delete = $pdo->prepare("DELETE FROM users WHERE id_user = ?");
                if ($delete->execute([$id_user])) {
                    $success = 'User berhasil dihapus.';
                } else {
                    $error = 'Gagal menghapus user.';
                }
            } catch (PDOException $e) {
                $error = 'Gagal menghapus user. User mungkin terikat dengan data transaksi lain.';
            }
        }
    }
}

// Fetch user detail for editing if requested
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_user = ?");
        $stmt->execute([$edit_id]);
        $edit_user = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Gagal memuat data user.';
    }
}

// Fetch all users
try {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $error = 'Gagal mengambil daftar user.';
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="dashboard-grid">
  <!-- Admin Sidebar -->
  <div class="sidebar-card">
    <h3 style="font-size: 20px; border-bottom: 1px solid var(--color-hairline); padding-bottom: 10px; margin-bottom: 15px;">Admin Panel</h3>
    <ul class="sidebar-menu">
      <li><a href="dashboard_admin.php"><span>📊</span> Dashboard Stats</a></li>
      <li><a href="manage_users.php" class="active"><span>👥</span> Kelola User</a></li>
      <li><a href="manage_messages.php"><span>✉️</span> Kelola Pesan</a></li>
      <li><a href="manage_songs.php"><span>🎵</span> Kelola Katalog Lagu</a></li>
      <li><a href="manage_premium.php"><span>★</span> Kelola Paket Premium</a></li>
      <li><a href="manage_payments.php"><span>💳</span> Kelola Transaksi</a></li>
    </ul>
  </div>

  <!-- Main Area -->
  <div>
    <h2>Kelola Akun Pengguna</h2>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><strong>Error:</strong> <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><strong>Sukses:</strong> <?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Insert / Edit Panel -->
    <div style="margin-bottom: 30px;">
      <?php if ($edit_user): ?>
        <!-- Edit User Card -->
        <div class="card" style="background-color: var(--color-surface-card);">
          <h3 style="font-size: 20px; margin-bottom: 15px;">Edit Pengguna: <?php echo sanitize($edit_user['name']); ?></h3>
          <form action="manage_users.php" method="POST">
            <input type="hidden" name="id_user" value="<?php echo $edit_user['id_user']; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
              <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-input" required value="<?php echo sanitize($edit_user['name']); ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" required value="<?php echo sanitize($edit_user['email']); ?>">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
              <div class="form-group">
                <label class="form-label">Password Baru (Biarkan kosong jika tidak diganti)</label>
                <input type="password" name="password" class="form-input" placeholder="Masukkan password baru...">
              </div>
              <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-input">
                  <option value="user" <?php echo ($edit_user['role'] === 'user') ? 'selected' : ''; ?>>User Biasa</option>
                  <option value="admin" <?php echo ($edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); align-items: center;">
              <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="premium_status" id="edit_prem" value="1" <?php echo ($edit_user['premium_status'] == 1) ? 'checked' : ''; ?>>
                <label for="edit_prem" class="form-label" style="margin: 0; cursor:pointer;">Aktifkan Status Premium</label>
              </div>
              <div class="form-group">
                <label class="form-label">Masa Berlaku Premium</label>
                <input type="datetime-local" name="premium_until" class="form-input" value="<?php echo $edit_user['premium_until'] ? date('Y-m-d\TH:i', strtotime($edit_user['premium_until'])) : ''; ?>">
              </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
              <a href="manage_users.php" class="btn btn-secondary">Batal</a>
              <button type="submit" name="update_user" class="btn btn-primary">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <!-- Users List Table -->
    <div class="card" style="background-color: var(--color-surface-card);">
      <h3 style="font-size: 20px; margin-bottom: 15px;">Daftar Seluruh User</h3>
      
      <?php if (empty($users)): ?>
        <p style="color: var(--color-muted); text-align: center; padding: 20px;">Tidak ada pengguna terdaftar.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Premium</th>
                <th>Registrasi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $usr): ?>
                <tr>
                  <td><?php echo $usr['id_user']; ?></td>
                  <td style="font-weight: 600; color: var(--color-ink);"><?php echo sanitize($usr['name']); ?></td>
                  <td><?php echo sanitize($usr['email']); ?></td>
                  <td>
                    <?php if ($usr['role'] === 'admin'): ?>
                      <span class="badge badge-dark" style="font-size: 10px;">Admin</span>
                    <?php else: ?>
                      <span class="badge badge-cream" style="font-size: 10px;">User</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php 
                      $is_active_premium = ($usr['premium_status'] == 1 || (!empty($usr['premium_until']) && strtotime($usr['premium_until']) > time()));
                      if ($is_active_premium): 
                    ?>
                      <span class="badge badge-coral" style="font-size: 10px;">★ Premium</span>
                      <?php if ($usr['premium_status'] == 0): ?>
                        <span class="badge badge-dark" style="font-size: 10px; margin-left: 4px;">Canceled</span>
                      <?php endif; ?>
                      <div style="font-size: 10px; color: var(--color-muted); margin-top: 2px;">
                        Berlaku s/d: <?php echo $usr['premium_until'] ? date('d-m-Y H:i', strtotime($usr['premium_until'])) : 'Unlimited'; ?>
                      </div>
                    <?php else: ?>
                      <span class="badge badge-cream" style="font-size: 10px; color: var(--color-muted);">Standard</span>
                      <?php if (!empty($usr['premium_until']) && strtotime($usr['premium_until']) <= time()): ?>
                        <div style="font-size: 10px; color: var(--color-muted); margin-top: 2px;">Expired: <?php echo date('d-m-Y', strtotime($usr['premium_until'])); ?></div>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                  <td><?php echo date('d-m-Y H:i', strtotime($usr['created_at'])); ?></td>
                  <td>
                    <div style="display: flex; gap: 8px;">
                      <a href="manage_users.php?edit=<?php echo $usr['id_user']; ?>" class="btn btn-secondary" style="height:28px; padding:2px 8px; font-size:11px;">Edit</a>
                      
                      <!-- Delete Form -->
                      <form action="manage_users.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini? Semua pesan user ini juga akan terhapus.');" style="display: inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $usr['id_user']; ?>">
                        <button type="submit" name="delete_user" class="btn btn-danger" style="height:28px; padding:2px 8px; font-size:11px;" <?php echo ($usr['id_user'] === $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                          Hapus
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
