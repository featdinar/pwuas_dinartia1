<?php
$page_title = 'Pesan Saya';
require_once __DIR__ . '/includes/config.php';

// Enforce login
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle flash success messages
if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Handle message deletion securely via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    
    try {
        // Verify ownership before deleting
        $check = $pdo->prepare("SELECT id_message FROM messages WHERE id_message = ? AND id_user = ?");
        $check->execute([$delete_id, $user_id]);
        
        if ($check->fetch()) {
            $delete = $pdo->prepare("DELETE FROM messages WHERE id_message = ?");
            if ($delete->execute([$delete_id])) {
                $success = 'Pesan berhasil dihapus dari sistem.';
            } else {
                $error = 'Gagal menghapus pesan. Silakan coba lagi.';
            }
        } else {
            $error = 'Anda tidak memiliki otoritas untuk menghapus pesan ini.';
        }
    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
    }
}

// Fetch all messages written by user
try {
    $stmt = $pdo->prepare("
        SELECT m.*, s.title as song_title, s.artist as song_artist 
        FROM messages m 
        JOIN songs s ON m.id_song = s.id_song 
        WHERE m.id_user = ? 
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $user_messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal mengambil data pesan Anda.';
    $user_messages = [];
}
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
        <a href="dashboard_user.php">
          <span>📊</span> Dashboard
        </a>
      </li>
      <li>
        <a href="profile.php">
          <span>⚙️</span> Pengaturan Profil
        </a>
      </li>
      <li>
        <a href="my_messages.php" class="active">
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
      <div>
        <h2>Daftar Pesan Saya</h2>
        <p style="color: var(--color-muted); font-size: 14px;">Kelola dan pantau seluruh pesan rahasia yang telah Anda tulis.</p>
      </div>
      <a href="create_message.php" class="btn btn-primary">+ Tulis Pesan Baru</a>
    </div>

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

    <div class="card" style="background-color: var(--color-surface-card);">
      <?php if (empty($user_messages)): ?>
        <div style="text-align: center; padding: 40px 10px;">
          <p style="color: var(--color-muted); font-size: 16px; margin-bottom: 20px;">Anda belum pernah menulis pesan.</p>
          <a href="create_message.php" class="btn btn-primary">Tulis Pesan Pertama Sekarang</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Penerima</th>
                <th>Pesan Singkat</th>
                <th>Lagu Terkait</th>
                <th>Tanggal Rilis</th>
                <th>Status Opsi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($user_messages as $msg): ?>
                <tr>
                  <td style="font-weight: 600; color: var(--color-ink);"><?php echo sanitize($msg['recipient_name']); ?></td>
                  <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo sanitize($msg['message_content']); ?>">
                    <?php echo sanitize($msg['message_content']); ?>
                  </td>
                  <td>
                    <span style="font-size: 13px; font-weight: 500; color: var(--color-primary);">
                      <?php echo sanitize($msg['song_title']); ?>
                    </span>
                    <span style="display:block; font-size:11px; color: var(--color-muted);"><?php echo sanitize($msg['song_artist']); ?></span>
                  </td>
                  <td>
                    <?php 
                      if ($msg['scheduled_date'] !== null) {
                          $sched = strtotime($msg['scheduled_date']);
                          if ($sched > time()) {
                              echo '<span style="color: var(--color-warning); font-weight:500;">Terjadwal: ' . date('d-m-Y', $sched) . '</span>';
                          } else {
                              echo 'Rilis (' . date('d-m-Y', $sched) . ')';
                          }
                      } else {
                          echo 'Instan (' . date('d-m-Y', strtotime($msg['created_at'])) . ')';
                      }
                    ?>
                  </td>
                  <td>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                      <?php if ($msg['private_message']): ?>
                        <span class="badge badge-dark" style="font-size: 10px; width: fit-content; text-align: center;">Privat</span>
                      <?php else: ?>
                        <span class="badge badge-cream" style="font-size: 10px; width: fit-content; text-align: center;">Publik</span>
                      <?php endif; ?>

                      <?php if ($msg['anonymous']): ?>
                        <span class="badge badge-teal" style="font-size: 10px; width: fit-content; text-align: center;">Anonim</span>
                      <?php endif; ?>
                      
                      <span class="badge badge-cream" style="font-size: 10px; width: fit-content; text-align: center; text-transform: capitalize;">Tema: <?php echo $msg['theme']; ?></span>
                    </div>
                  </td>
                  <td>
                    <div style="display: flex; gap: 8px; align-items: center;">
                      <a href="view_message.php?id=<?php echo $msg['id_message']; ?>" class="btn btn-secondary" style="height:28px; padding:2px 8px; font-size:11px;">Lihat</a>
                      <a href="edit_message.php?id=<?php echo $msg['id_message']; ?>" class="btn btn-secondary" style="height:28px; padding:2px 8px; font-size:11px;">Edit</a>
                      
                      <!-- Delete Form -->
                      <form action="my_messages.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesan ini? Tindakan ini tidak bisa dibatalkan.');" style="display: inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $msg['id_message']; ?>">
                        <button type="submit" class="btn btn-danger" style="height:28px; padding:2px 8px; font-size:11px;">Hapus</button>
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
