<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/includes/config.php';

// Enforce admin access
requireAdmin();

$error = '';
$stats = [];
$recent_users = [];
$recent_payments = [];

try {
    // 1. Calculate Metrics
    $stats['users_standard'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND premium_status = 0")->fetchColumn();
    $stats['users_premium'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND premium_status = 1")->fetchColumn();
    $stats['messages_count'] = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    $stats['songs_count'] = $pdo->query("SELECT COUNT(*) FROM songs")->fetchColumn();
    $stats['earnings'] = $pdo->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'completed'")->fetchColumn() ?: 0.00;

    // 2. Fetch 5 Recent Registered Users
    $recent_users = $pdo->query("SELECT id_user, name, email, premium_status, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC LIMIT 5")->fetchAll();

    // 3. Fetch 5 Recent Transactions
    $recent_payments = $pdo->query("
        SELECT p.*, u.name as user_name, pkg.package_name 
        FROM payments p 
        JOIN users u ON p.id_user = u.id_user 
        JOIN premium_packages pkg ON p.id_package = pkg.id_package 
        ORDER BY p.payment_date DESC 
        LIMIT 5
    ")->fetchAll();

} catch (PDOException $e) {
    $error = 'Terjadi kesalahan sistem saat memproses metrik: ' . $e->getMessage();
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="dashboard-grid">
  <!-- Admin Sidebar -->
  <div class="sidebar-card">
    <h3 style="font-size: 20px; border-bottom: 1px solid var(--color-hairline); padding-bottom: 10px; margin-bottom: 15px;">Admin Panel</h3>
    <p style="font-size: 14px; font-weight: 500;">Menu Pengelolaan</p>
    
    <ul class="sidebar-menu">
      <li>
        <a href="dashboard_admin.php" class="active">
          <span>📊</span> Dashboard Stats
        </a>
      </li>
      <li>
        <a href="manage_users.php">
          <span>👥</span> Kelola User
        </a>
      </li>
      <li>
        <a href="manage_messages.php">
          <span>✉️</span> Kelola Pesan
        </a>
      </li>
      <li>
        <a href="manage_songs.php">
          <span>🎵</span> Kelola Katalog Lagu
        </a>
      </li>
      <li>
        <a href="manage_premium.php">
          <span>★</span> Kelola Paket Premium
        </a>
      </li>
      <li>
        <a href="manage_payments.php">
          <span>💳</span> Kelola Transaksi
        </a>
      </li>
      <li style="margin-top: 15px; border-top: 1px solid var(--color-hairline); padding-top: 10px;">
        <a href="profile.php">
          <span>⚙️</span> Profil Saya
        </a>
      </li>
    </ul>
  </div>

  <!-- Main Area -->
  <div>
    <h2>Dashboard Kontrol Admin</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Halaman ringkasan performa dan pengelolaan data sistem Unsent.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <!-- Metrics Stat Grid -->
    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
      <div class="stat-card">
        <h4 style="font-size: 12px; color: var(--color-muted); text-transform: uppercase;">Standard User</h4>
        <div class="stat-num" style="color: var(--color-ink);"><?php echo $stats['users_standard'] ?? 0; ?></div>
      </div>
      
      <div class="stat-card">
        <h4 style="font-size: 12px; color: var(--color-muted); text-transform: uppercase;">Premium User</h4>
        <div class="stat-num" style="color: var(--color-primary);"><?php echo $stats['users_premium'] ?? 0; ?></div>
      </div>

      <div class="stat-card">
        <h4 style="font-size: 12px; color: var(--color-muted); text-transform: uppercase;">Total Pesan</h4>
        <div class="stat-num" style="color: var(--color-ink);"><?php echo $stats['messages_count'] ?? 0; ?></div>
      </div>

      <div class="stat-card">
        <h4 style="font-size: 12px; color: var(--color-muted); text-transform: uppercase;">Katalog Lagu</h4>
        <div class="stat-num" style="color: var(--color-accent-teal);"><?php echo $stats['songs_count'] ?? 0; ?></div>
      </div>

      <div class="stat-card" style="min-width: 200px;">
        <h4 style="font-size: 12px; color: var(--color-muted); text-transform: uppercase;">Total Pendapatan</h4>
        <div class="stat-num" style="color: var(--color-success); font-size: 24px; margin-top: 15px;">
          <?php echo formatRupiah($stats['earnings'] ?? 0.00); ?>
        </div>
      </div>
    </div>

    <!-- Recent Subscriptions & Signups Grid -->
    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: var(--spacing-xl); margin-top: 30px;">
      
      <!-- Recent Payments -->
      <div class="card" style="background-color: var(--color-surface-card);">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 15px;">
          <h3 style="font-size: 18px;">Transaksi Terbaru</h3>
          <a href="manage_payments.php" style="font-size: 12px; font-weight: 600;">Semua Transaksi →</a>
        </div>
        
        <?php if (empty($recent_payments)): ?>
          <p style="color: var(--color-muted); text-align: center; padding: 20px;">Belum ada riwayat transaksi.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table" style="font-size: 13px;">
              <thead>
                <tr>
                  <th>Pengguna</th>
                  <th>Paket</th>
                  <th>Jumlah</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_payments as $pay): ?>
                  <tr>
                    <td><?php echo sanitize($pay['user_name']); ?></td>
                    <td><?php echo sanitize($pay['package_name']); ?></td>
                    <td style="font-weight:600;"><?php echo formatRupiah($pay['amount']); ?></td>
                    <td>
                      <?php if ($pay['payment_status'] === 'completed'): ?>
                        <span class="badge badge-success" style="font-size: 9px; padding: 2px 6px;">Sukses</span>
                      <?php elseif ($pay['payment_status'] === 'pending'): ?>
                        <span class="badge badge-cream" style="font-size: 9px; padding: 2px 6px; color:#d4a017;">Tertunda</span>
                      <?php else: ?>
                        <span class="badge badge-danger" style="font-size: 9px; padding: 2px 6px;">Gagal</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Recent User Signups -->
      <div class="card" style="background-color: var(--color-surface-card);">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 15px;">
          <h3 style="font-size: 18px;">User Terbaru</h3>
          <a href="manage_users.php" style="font-size: 12px; font-weight: 600;">Semua User →</a>
        </div>
        
        <?php if (empty($recent_users)): ?>
          <p style="color: var(--color-muted); text-align: center; padding: 20px;">Belum ada user terdaftar.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table" style="font-size: 13px;">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_users as $usr): ?>
                  <tr>
                    <td title="<?php echo sanitize($usr['email']); ?>">
                      <div style="font-weight: 600;"><?php echo sanitize($usr['name']); ?></div>
                      <div style="font-size: 10px; color: var(--color-muted);"><?php echo date('d-m-Y', strtotime($usr['created_at'])); ?></div>
                    </td>
                    <td>
                      <?php if ($usr['premium_status']): ?>
                        <span class="badge badge-coral" style="font-size: 9px; padding: 2px 6px;">★ Prem</span>
                      <?php else: ?>
                        <span class="badge badge-cream" style="font-size: 9px; padding: 2px 6px;">Std</span>
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
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
