<?php
$page_title = 'Kelola Transaksi';
require_once __DIR__ . '/includes/config.php';

// Enforce admin access
requireAdmin();

$error = '';
$success = '';

// Handle manual approval or cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. APPROVE TRANSACTION
    if (isset($_POST['approve_id'])) {
        $approve_id = (int)$_POST['approve_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Fetch transaction details
            $stmt = $pdo->prepare("
                SELECT p.*, pkg.duration_days 
                FROM payments p 
                JOIN premium_packages pkg ON p.id_package = pkg.id_package 
                WHERE p.id_payment = ? AND p.payment_status = 'pending'
            ");
            $stmt->execute([$approve_id]);
            $trans = $stmt->fetch();
            
            if ($trans) {
                // Update payment status
                $update_pay = $pdo->prepare("UPDATE payments SET payment_status = 'completed' WHERE id_payment = ?");
                $update_pay->execute([$approve_id]);
                
                // Fetch user subscription details
                $u_stmt = $pdo->prepare("SELECT premium_status, premium_until FROM users WHERE id_user = ?");
                $u_stmt->execute([$trans['id_user']]);
                $usr = $u_stmt->fetch();
                
                $duration_days = (int)$trans['duration_days'];
                $new_until = null;
                
                if ($usr && $usr['premium_status'] == 1 && $usr['premium_until'] !== null) {
                    $current_expiry = strtotime($usr['premium_until']);
                    if ($current_expiry > time()) {
                        $new_until = date('Y-m-d H:i:s', strtotime("+$duration_days days", $current_expiry));
                    } else {
                        $new_until = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
                    }
                } else {
                    $new_until = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
                }
                
                // Update user account premium details
                $update_usr = $pdo->prepare("UPDATE users SET premium_status = 1, premium_until = ? WHERE id_user = ?");
                $update_usr->execute([$new_until, $trans['id_user']]);
                
                $pdo->commit();
                $success = 'Transaksi berhasil disetujui secara manual. Akun pengguna telah ditingkatkan ke premium.';
            } else {
                $pdo->rollBack();
                $error = 'Transaksi tidak ditemukan atau status transaksi sudah bukan pending.';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal memproses persetujuan manual: ' . $e->getMessage();
        }
    }
    
    // 2. CANCEL / FAIL TRANSACTION
    elseif (isset($_POST['cancel_id'])) {
        $cancel_id = (int)$_POST['cancel_id'];
        
        try {
            // Cancel pending payment
            $update = $pdo->prepare("UPDATE payments SET payment_status = 'failed' WHERE id_payment = ? AND payment_status = 'pending'");
            if ($update->execute([$cancel_id])) {
                $success = 'Transaksi berhasil dibatalkan / ditolak.';
            } else {
                $error = 'Gagal membatalkan transaksi. Transaksi mungkin tidak ditemukan atau statusnya sudah diselesaikan.';
            }
        } catch (PDOException $e) {
            $error = 'Kesalahan database: ' . $e->getMessage();
        }
    }
}

// Setup filtering options
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';

$query_str = "
    SELECT p.*, u.name as user_name, u.email as user_email, pkg.package_name 
    FROM payments p 
    JOIN users u ON p.id_user = u.id_user 
    JOIN premium_packages pkg ON p.id_package = pkg.id_package
    WHERE 1=1
";
$params = [];

if ($filter_status !== 'all') {
    $query_str .= " AND p.payment_status = ?";
    $params[] = $filter_status;
}

$query_str .= " ORDER BY p.payment_date DESC";

try {
    $stmt = $pdo->prepare($query_str);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    $payments = [];
    $error = 'Gagal memuat log pembayaran: ' . $e->getMessage();
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
      <li><a href="manage_premium.php"><span>★</span> Kelola Paket Premium</a></li>
      <li><a href="manage_payments.php" class="active"><span>💳</span> Kelola Transaksi</a></li>
    </ul>
  </div>

  <!-- Main Area -->
  <div>
    <h2>Kelola Transaksi Layanan</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Tinjau riwayat pembayaran pengguna, audit status transaksi, dan setujui pembayaran tertunda.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><strong>Error:</strong> <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><strong>Sukses:</strong> <?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Filter Header -->
    <div class="card" style="background-color: var(--color-surface-card); margin-bottom: 25px; padding: 20px;">
      <form action="manage_payments.php" method="GET" style="display: flex; gap: var(--spacing-md); align-items: flex-end; max-width: 500px;">
        <div class="form-group" style="margin-bottom:0; flex:1;">
          <label class="form-label">Filter Status Pembayaran</label>
          <select name="status" class="form-input">
            <option value="all" <?php echo ($filter_status === 'all') ? 'selected' : ''; ?>>Semua Transaksi</option>
            <option value="completed" <?php echo ($filter_status === 'completed') ? 'selected' : ''; ?>>Sukses (Completed)</option>
            <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>Tertunda (Pending)</option>
            <option value="failed" <?php echo ($filter_status === 'failed') ? 'selected' : ''; ?>>Gagal (Failed)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="height:40px;">Saring</button>
      </form>
    </div>

    <!-- Payments Ledger Table -->
    <div class="card" style="background-color: var(--color-surface-card);">
      <h3 style="font-size: 20px; margin-bottom: 15px;">Daftar Transaksi</h3>
      
      <?php if (empty($payments)): ?>
        <p style="color: var(--color-muted); text-align: center; padding: 20px;">Tidak ada data transaksi yang ditemukan.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Transaction ID</th>
                <th>Pengguna</th>
                <th>Paket Premium</th>
                <th>Metode</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $pay): ?>
                <tr>
                  <td><?php echo date('d-m-Y H:i', strtotime($pay['payment_date'])); ?></td>
                  <td style="font-family: var(--font-mono); font-size: 12px; font-weight: 500;">
                    <?php echo sanitize($pay['transaction_id']); ?>
                  </td>
                  <td title="<?php echo sanitize($pay['user_email']); ?>">
                    <div style="font-weight:600;"><?php echo sanitize($pay['user_name']); ?></div>
                  </td>
                  <td><?php echo sanitize($pay['package_name']); ?></td>
                  <td><?php echo sanitize($pay['payment_method']); ?></td>
                  <td style="font-weight: 600; color: var(--color-ink);"><?php echo formatRupiah($pay['amount']); ?></td>
                  <td>
                    <?php if ($pay['payment_status'] === 'completed'): ?>
                      <span class="badge badge-success" style="font-size: 10px; padding: 2px 6px;">Sukses</span>
                    <?php elseif ($pay['payment_status'] === 'pending'): ?>
                      <span class="badge badge-cream" style="font-size: 10px; padding: 2px 6px; color:#d4a017; font-weight:600;">Pending</span>
                    <?php else: ?>
                      <span class="badge badge-danger" style="font-size: 10px; padding: 2px 6px;">Gagal</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($pay['payment_status'] === 'pending'): ?>
                      <div style="display: flex; gap: 6px;">
                        <!-- Approve Form -->
                        <form action="manage_payments.php?status=<?php echo $filter_status; ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui transaksi ini secara manual?');">
                          <input type="hidden" name="approve_id" value="<?php echo $pay['id_payment']; ?>">
                          <button type="submit" class="btn btn-primary" style="height:26px; padding:2px 8px; font-size:10px;">Setujui</button>
                        </form>
                        
                        <!-- Cancel Form -->
                        <form action="manage_payments.php?status=<?php echo $filter_status; ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menolak transaksi ini?');">
                          <input type="hidden" name="cancel_id" value="<?php echo $pay['id_payment']; ?>">
                          <button type="submit" class="btn btn-danger" style="height:26px; padding:2px 8px; font-size:10px;">Tolak</button>
                        </form>
                      </div>
                    <?php else: ?>
                      <span style="font-size: 12px; color: var(--color-muted);">Selesai</span>
                    <?php endif; ?>
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
