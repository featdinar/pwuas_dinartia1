<?php
$page_title = 'Dashboard Pengguna';
require_once __DIR__ . '/includes/config.php';

// Enforce login
requireLogin();

// Prevent administrators from entering user area incorrectly
if (isAdmin()) {
    header("Location: dashboard_admin.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';

try {
    // 1. Fetch total user messages count
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE id_user = ?");
    $stmt_count->execute([$user_id]);
    $total_messages = $stmt_count->fetchColumn();

    // 2. Fetch user's recent messages
    $stmt_msgs = $pdo->prepare("
        SELECT m.*, s.title as song_title, s.artist as song_artist 
        FROM messages m 
        JOIN songs s ON m.id_song = s.id_song 
        WHERE m.id_user = ? 
        ORDER BY m.created_at DESC 
        LIMIT 5
    ");
    $stmt_msgs->execute([$user_id]);
    $recent_messages = $stmt_msgs->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal mengambil data dashboard.';
    $total_messages = 0;
    $recent_messages = [];
}

$is_prem = isPremium();
$expiry = getPremiumExpiry();
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="dashboard-grid">
  <!-- Sidebar -->
  <div class="sidebar-card">
    <h3 style="font-size: 20px; border-bottom: 1px solid var(--color-hairline); padding-bottom: 10px; margin-bottom: 15px;">Dashboard</h3>
    <p style="font-size: 14px; font-weight: 500;"><?php echo sanitize($_SESSION['user_name']); ?></p>
    <p style="font-size: 12px; color: var(--color-muted);"><?php echo sanitize($_SESSION['user_email']); ?></p>
    
    <ul class="sidebar-menu">
      <li>
        <a href="dashboard_user.php" class="active">
          <span>📊</span> Dashboard
        </a>
      </li>
      <li>
        <a href="profile.php">
          <span>⚙️</span> Pengaturan Profil
        </a>
      </li>
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
    </ul>
  </div>

  <!-- Main Area -->
  <div>
    <h2>Selamat Datang Kembali, <?php echo sanitize($_SESSION['user_name']); ?>!</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Di sini Anda dapat mengelola pesan unsent dan melihat status keanggotaan Anda.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <!-- Metrics Stats grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <h4 style="font-size: 14px; color: var(--color-muted); text-transform: uppercase;">Total Pesan</h4>
        <div class="stat-num"><?php echo $total_messages; ?></div>
      </div>
      
      <div class="stat-card" style="position: relative;">
        <h4 style="font-size: 14px; color: var(--color-muted); text-transform: uppercase;">Status Akun</h4>
        <?php if ($is_prem): ?>
          <div class="stat-num" style="color: var(--color-primary);">Premium</div>
          <span style="font-size: 11px; color: var(--color-muted); display: block; margin-top: 4px;">
            Hingga: <?php echo date('d-m-Y', strtotime($expiry)); ?>
          </span>
        <?php else: ?>
          <div class="stat-num" style="color: var(--color-muted);">Standard</div>
          <a href="premium.php" style="font-size: 11px; font-weight: 500; margin-top: 4px; display: inline-block;">Upgrade sekarang →</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Premium advertisement / active features status -->
    <?php if (!$is_prem): ?>
      <div class="card" style="background-color: var(--color-surface-cream-strong); border: 1px solid var(--color-primary-disabled); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div style="max-width: 500px;">
          <h4 style="font-size: 18px; font-weight: 600; margin-bottom: 6px;">Buka Fitur Premium Anda</h4>
          <p style="font-size: 13px; color: var(--color-body);">
            Tulis pesan secara Anonim, simpan sebagai Pesan Privat, jadwalkan tanggal publikasi, dan gunakan tema kustom yang cantik pada pesan Anda!
          </p>
        </div>
        <a href="premium.php" class="btn btn-primary">Dapatkan Premium</a>
      </div>
    <?php endif; ?>

    <!-- Recent Messages -->
    <div class="card" style="background-color: var(--color-surface-card);">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h3 style="font-size: 20px;">Pesan Terakhir Anda</h3>
        <div style="display: flex; gap: 10px;">
          <a href="my_messages.php" class="btn btn-secondary" style="height:32px; padding:4px 12px; font-size:12px;">Lihat Semua</a>
          <a href="create_message.php" class="btn btn-primary" style="height:32px; padding:4px 12px; font-size:12px;">+ Tulis Pesan</a>
        </div>
      </div>

      <?php if (empty($recent_messages)): ?>
        <div style="text-align: center; padding: 30px 10px;">
          <p style="color: var(--color-muted); margin-bottom: 15px;">Anda belum menulis pesan apapun.</p>
          <a href="create_message.php" class="btn btn-primary">Tulis Pesan Pertama Anda</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Penerima</th>
                <th>Pesan Singkat</th>
                <th>Lagu Terkait</th>
                <th>Tanggal Dibuat</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_messages as $msg): ?>
                <tr>
                  <td style="font-weight: 600; color: var(--color-ink);"><?php echo sanitize($msg['recipient_name']); ?></td>
                  <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    <?php echo sanitize($msg['message_content']); ?>
                  </td>
                  <td>
                    <span style="font-size: 13px; font-weight: 500; color: var(--color-primary);">
                      <?php echo sanitize($msg['song_title']); ?>
                    </span>
                  </td>
                  <td><?php echo date('d-m-Y', strtotime($msg['created_at'])); ?></td>
                  <td>
                    <?php if ($msg['private_message']): ?>
                      <span class="badge badge-dark" style="font-size: 10px; padding: 2px 6px;">Privat</span>
                    <?php else: ?>
                      <span class="badge badge-cream" style="font-size: 10px; padding: 2px 6px;">Publik</span>
                    <?php endif; ?>

                    <?php if ($msg['anonymous']): ?>
                      <span class="badge badge-teal" style="font-size: 10px; padding: 2px 6px;">Anonim</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display: flex; gap: 8px;">
                      <a href="view_message.php?id=<?php echo $msg['id_message']; ?>" class="btn btn-secondary" style="height:28px; padding:2px 8px; font-size:11px;" title="Baca Selengkapnya">Lihat</a>
                      <a href="edit_message.php?id=<?php echo $msg['id_message']; ?>" class="btn btn-secondary" style="height:28px; padding:2px 8px; font-size:11px;" title="Ubah Pesan">Edit</a>
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
