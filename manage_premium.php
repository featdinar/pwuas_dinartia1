<?php
$page_title = 'Kelola Paket Premium';
require_once __DIR__ . '/includes/config.php';

// Enforce admin access
requireAdmin();

$error = '';
$success = '';
$edit_package = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADD PACKAGE
    if (isset($_POST['add_package'])) {
        $package_name = sanitize($_POST['package_name']);
        $price = (float)$_POST['price'];
        $duration_days = (int)$_POST['duration_days'];
        $features = sanitize($_POST['features']);

        if (empty($package_name) || $price < 0 || $duration_days <= 0 || empty($features)) {
            $error = 'Semua field wajib diisi dengan nilai yang valid.';
        } else {
            try {
                $insert = $pdo->prepare("
                    INSERT INTO premium_packages (package_name, price, duration_days, features)
                    VALUES (?, ?, ?, ?)
                ");
                if ($insert->execute([$package_name, $price, $duration_days, $features])) {
                    $success = 'Paket premium baru berhasil ditambahkan.';
                } else {
                    $error = 'Gagal menambahkan paket premium.';
                }
            } catch (PDOException $e) {
                $error = 'Kesalahan database: ' . $e->getMessage();
            }
        }
    }

    // 2. UPDATE PACKAGE
    elseif (isset($_POST['update_package'])) {
        $id_package = (int)$_POST['id_package'];
        $package_name = sanitize($_POST['package_name']);
        $price = (float)$_POST['price'];
        $duration_days = (int)$_POST['duration_days'];
        $features = sanitize($_POST['features']);

        if (empty($package_name) || $price < 0 || $duration_days <= 0 || empty($features)) {
            $error = 'Semua field wajib diisi dengan nilai yang valid.';
        } else {
            try {
                $update = $pdo->prepare("
                    UPDATE premium_packages 
                    SET package_name = ?, price = ?, duration_days = ?, features = ? 
                    WHERE id_package = ?
                ");
                if ($update->execute([$package_name, $price, $duration_days, $features, $id_package])) {
                    $success = 'Informasi paket premium berhasil diperbarui.';
                } else {
                    $error = 'Gagal memperbarui paket premium.';
                }
            } catch (PDOException $e) {
                $error = 'Kesalahan database: ' . $e->getMessage();
            }
        }
    }

    // 3. DELETE PACKAGE
    elseif (isset($_POST['delete_package'])) {
        $id_package = (int)$_POST['delete_id'];
        try {
            $delete = $pdo->prepare("DELETE FROM premium_packages WHERE id_package = ?");
            if ($delete->execute([$id_package])) {
                $success = 'Paket premium berhasil dihapus.';
            } else {
                $error = 'Gagal menghapus paket premium.';
            }
        } catch (PDOException $e) {
            $error = 'Gagal menghapus paket premium. Paket ini mungkin sedang terikat dengan data transaksi pembayaran.';
        }
    }
}

// Fetch package detail for editing if requested
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM premium_packages WHERE id_package = ?");
        $stmt->execute([$edit_id]);
        $edit_package = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Gagal memuat detail paket.';
    }
}

// Fetch all packages
try {
    $packages = $pdo->query("SELECT * FROM premium_packages ORDER BY price ASC")->fetchAll();
} catch (PDOException $e) {
    $packages = [];
    $error = 'Gagal mengambil data paket premium.';
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="dashboard-grid">
  <!-- Admin Sidebar -->
  <div class="sidebar-card">
    <h3 style="font-size: 20px; border-bottom: 1px solid var(--color-hairline); padding-bottom: 10px; margin-bottom: 15px;">Admin Panel</h3>
    <ul class="sidebar-menu">
      <li><a href="dashboard_admin.php"><span>📊</span> Dashboard Stats</a></li>
      <li><a href="manage_users.php"><span>👥</span> Kelola User</a></li>
      <li><a href="manage_messages.php"><span>✉️</span> Kelola Pesan</a></li>
      <li><a href="manage_songs.php"><span>🎵</span> Kelola Katalog Lagu</a></li>
      <li><a href="manage_premium.php" class="active"><span>★</span> Kelola Paket Premium</a></li>
      <li><a href="manage_payments.php"><span>💳</span> Kelola Transaksi</a></li>
    </ul>
  </div>

  <!-- Main Area -->
  <div>
    <h2>Kelola Paket Premium</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Konfigurasi pilihan paket, tarif harga, jangka masa aktif, dan fitur paket premium.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><strong>Error:</strong> <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><strong>Sukses:</strong> <?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Insert / Edit Panel -->
    <div style="margin-bottom: 30px;">
      <?php if ($edit_package): ?>
        <!-- Edit Package Card -->
        <div class="card" style="background-color: var(--color-surface-card);">
          <h3 style="font-size: 20px; margin-bottom: 15px;">Edit Paket: <?php echo sanitize($edit_package['package_name']); ?></h3>
          <form action="manage_premium.php" method="POST">
            <input type="hidden" name="id_package" value="<?php echo $edit_package['id_package']; ?>">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: var(--spacing-md);">
              <div class="form-group">
                <label class="form-label">Nama Paket</label>
                <input type="text" name="package_name" class="form-input" required value="<?php echo sanitize($edit_package['package_name']); ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Harga (IDR)</label>
                <input type="number" step="100" name="price" class="form-input" required value="<?php echo $edit_package['price']; ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Durasi (Hari)</label>
                <input type="number" name="duration_days" class="form-input" required value="<?php echo $edit_package['duration_days']; ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Fitur Paket (Pisahkan dengan tanda koma)</label>
              <input type="text" name="features" class="form-input" required value="<?php echo sanitize($edit_package['features']); ?>">
              <span style="font-size: 11px; color:var(--color-muted);">Contoh: Anonim, Privat, Jadwal Rilis, Kustom Tema</span>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
              <a href="manage_premium.php" class="btn btn-secondary">Batal</a>
              <button type="submit" name="update_package" class="btn btn-primary">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      <?php else: ?>
        <!-- Add Package Accordion Card -->
        <details class="card" style="background-color: var(--color-surface-card); cursor: pointer;" <?php echo isset($_POST['add_package']) && !empty($error) ? 'open' : ''; ?>>
          <summary style="font-size: 18px; font-weight: 500; outline: none; list-style: none; display: flex; justify-content: space-between; align-items: center;">
            <span>+ Tambahkan Paket Premium Baru</span>
            <span style="font-size: 12px; color: var(--color-muted);">Klik untuk membuka form</span>
          </summary>
          
          <form action="manage_premium.php" method="POST" style="margin-top: 20px; cursor: default;" onsubmit="event.stopPropagation();">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: var(--spacing-md);">
              <div class="form-group">
                <label class="form-label">Nama Paket</label>
                <input type="text" name="package_name" class="form-input" placeholder="Misal: Platinum Premium" required value="<?php echo isset($_POST['package_name']) && isset($_POST['add_package']) ? sanitize($_POST['package_name']) : ''; ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Harga (IDR)</label>
                <input type="number" step="100" name="price" class="form-input" placeholder="IDR..." required value="<?php echo isset($_POST['price']) && isset($_POST['add_package']) ? $_POST['price'] : ''; ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Durasi (Hari)</label>
                <input type="number" name="duration_days" class="form-input" placeholder="Hari..." required value="<?php echo isset($_POST['duration_days']) && isset($_POST['add_package']) ? $_POST['duration_days'] : ''; ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Fitur Paket (Pisahkan dengan tanda koma)</label>
              <input type="text" name="features" class="form-input" placeholder="Fitur1, Fitur2, Fitur3..." required value="<?php echo isset($_POST['features']) && isset($_POST['add_package']) ? sanitize($_POST['features']) : 'Anonymous Message, Private Message, Scheduled Message, Custom Theme'; ?>">
            </div>

            <button type="submit" name="add_package" class="btn btn-primary" style="margin-top: 15px; float: right;">Tambah Paket</button>
            <div style="clear: both;"></div>
          </form>
        </details>
      <?php endif; ?>
    </div>

    <!-- Packages List Table -->
    <div class="card" style="background-color: var(--color-surface-card);">
      <h3 style="font-size: 20px; margin-bottom: 15px;">Daftar Paket Premium Aktif</h3>
      
      <?php if (empty($packages)): ?>
        <p style="color: var(--color-muted); text-align: center; padding: 20px;">Tidak ada paket premium yang tersedia.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama Paket</th>
                <th>Harga Paket</th>
                <th>Masa Aktif (Hari)</th>
                <th>Fitur Termasuk</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($packages as $pkg): ?>
                <tr>
                  <td><?php echo $pkg['id_package']; ?></td>
                  <td style="font-weight: 600; color: var(--color-ink);"><?php echo sanitize($pkg['package_name']); ?></td>
                  <td style="font-weight: 600; color: var(--color-success);"><?php echo formatRupiah($pkg['price']); ?></td>
                  <td><?php echo $pkg['duration_days']; ?> Hari</td>
                  <td>
                    <div style="max-width: 300px; font-size:12px; color: var(--color-body);">
                      <?php echo sanitize($pkg['features']); ?>
                    </div>
                  </td>
                  <td>
                    <div style="display: flex; gap: 8px;">
                      <a href="manage_premium.php?edit=<?php echo $pkg['id_package']; ?>" class="btn btn-secondary" style="height:28px; padding:2px 8px; font-size:11px;">Edit</a>
                      
                      <!-- Delete Form -->
                      <form action="manage_premium.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket premium ini? Paket tidak bisa dihapus jika ada user yang sedang berlangganan.');" style="display: inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $pkg['id_package']; ?>">
                        <button type="submit" name="delete_package" class="btn btn-danger" style="height:28px; padding:2px 8px; font-size:11px;">Hapus</button>
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
