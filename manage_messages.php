<?php
$page_title = 'Kelola Pesan';
require_once __DIR__ . '/includes/config.php';

// Enforce admin access
requireAdmin();

$error = '';
$success = '';

// Handle message deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    try {
        $delete = $pdo->prepare("DELETE FROM messages WHERE id_message = ?");
        if ($delete->execute([$delete_id])) {
            $success = 'Pesan berhasil dihapus dari sistem.';
        } else {
            $error = 'Gagal menghapus pesan.';
        }
    } catch (PDOException $e) {
        $error = 'Kesalahan database: ' . $e->getMessage();
    }
}

// Filters setup
$filter_type = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';
$filter_q = isset($_GET['q']) ? sanitize($_GET['q']) : '';

// Base query construction
$query_str = "
    SELECT m.*, s.title as song_title, s.artist as song_artist, u.name as author_name, u.email as author_email 
    FROM messages m 
    JOIN songs s ON m.id_song = s.id_song 
    JOIN users u ON m.id_user = u.id_user 
    WHERE 1=1
";
$params = [];

if ($filter_q !== '') {
    $query_str .= " AND (m.recipient_name LIKE ? OR m.message_content LIKE ? OR u.name LIKE ?)";
    $params[] = '%' . $filter_q . '%';
    $params[] = '%' . $filter_q . '%';
    $params[] = '%' . $filter_q . '%';
}

if ($filter_type === 'private') {
    $query_str .= " AND m.private_message = 1";
} elseif ($filter_type === 'anonymous') {
    $query_str .= " AND m.anonymous = 1";
} elseif ($filter_type === 'scheduled') {
    $query_str .= " AND m.scheduled_date > CURRENT_DATE()";
}

$query_str .= " ORDER BY m.created_at DESC";

try {
    $stmt = $pdo->prepare($query_str);
    $stmt->execute($params);
    $all_messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_messages = [];
    $error = 'Gagal mengambil data pesan: ' . $e->getMessage();
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
      <li><a href="manage_messages.php" class="active"><span>✉️</span> Kelola Pesan</a></li>
      <li><a href="manage_songs.php"><span>🎵</span> Kelola Katalog Lagu</a></li>
      <li><a href="manage_premium.php"><span>★</span> Kelola Paket Premium</a></li>
      <li><a href="manage_payments.php"><span>💳</span> Kelola Transaksi</a></li>
    </ul>
  </div>

  <!-- Main Area -->
  <div>
    <h2>Kelola Pesan Unsent</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Tinjau, cari, dan kelola seluruh pesan yang telah diterbitkan oleh pengguna.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><strong>Error:</strong> <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><strong>Sukses:</strong> <?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Filter and Search Header -->
    <div class="card" style="background-color: var(--color-surface-card); margin-bottom: 25px; padding: 20px;">
      <form action="manage_messages.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: var(--spacing-sm); align-items: flex-end;">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Cari Kata Kunci (Penerima, Isi, Pengirim)</label>
          <input type="text" name="q" class="form-input" placeholder="Masukkan kata kunci..." value="<?php echo sanitize($filter_q); ?>">
        </div>

        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label">Tipe Pesan</label>
          <select name="type" class="form-input">
            <option value="all" <?php echo ($filter_type === 'all') ? 'selected' : ''; ?>>Semua Pesan</option>
            <option value="private" <?php echo ($filter_type === 'private') ? 'selected' : ''; ?>>Hanya Privat</option>
            <option value="anonymous" <?php echo ($filter_type === 'anonymous') ? 'selected' : ''; ?>>Hanya Anonim</option>
            <option value="scheduled" <?php echo ($filter_type === 'scheduled') ? 'selected' : ''; ?>>Hanya Terjadwal</option>
          </select>
        </div>

        <div style="display: flex; gap: 8px;">
          <button type="submit" class="btn btn-primary" style="flex:1;">Filter</button>
          <a href="manage_messages.php" class="btn btn-secondary" style="flex:1; text-align:center;">Reset</a>
        </div>
      </form>
    </div>

    <!-- Messages table -->
    <div class="card" style="background-color: var(--color-surface-card);">
      <h3 style="font-size: 20px; margin-bottom: 15px;">Daftar Pesan</h3>
      
      <?php if (empty($all_messages)): ?>
        <p style="color: var(--color-muted); text-align: center; padding: 20px;">Tidak ada pesan yang ditemukan.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Pengirim</th>
                <th>Penerima</th>
                <th>Isi Pesan</th>
                <th>Lagu Terkait</th>
                <th>Tanggal Rilis</th>
                <th>Opsi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($all_messages as $msg): ?>
                <tr>
                  <td><?php echo $msg['id_message']; ?></td>
                  <td title="<?php echo sanitize($msg['author_email']); ?>">
                    <strong><?php echo sanitize($msg['author_name']); ?></strong>
                    <?php if ($msg['anonymous']): ?>
                      <div style="font-size: 10px; color: var(--color-muted); font-weight: 500;">(Anonim di Publik)</div>
                    <?php endif; ?>
                  </td>
                  <td style="font-weight: 600; color: var(--color-ink);"><?php echo sanitize($msg['recipient_name']); ?></td>
                  <td>
                    <div style="max-width: 200px; max-height: 60px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; font-size:13px;" title="<?php echo sanitize($msg['message_content']); ?>">
                      "<?php echo sanitize($msg['message_content']); ?>"
                    </div>
                  </td>
                  <td>
                    <div style="font-size:13px; font-weight:500; color: var(--color-primary);"><?php echo sanitize($msg['song_title']); ?></div>
                    <div style="font-size:10px; color:var(--color-muted);"><?php echo sanitize($msg['song_artist']); ?></div>
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
                        <span class="badge badge-dark" style="font-size: 9px; width: fit-content; text-align: center;">Privat</span>
                      <?php else: ?>
                        <span class="badge badge-cream" style="font-size: 9px; width: fit-content; text-align: center;">Publik</span>
                      <?php endif; ?>
                      <span class="badge badge-cream" style="font-size: 9px; width: fit-content; text-align: center; text-transform: capitalize;">Tema: <?php echo $msg['theme']; ?></span>
                    </div>
                  </td>
                  <td>
                    <div style="display: flex; gap: 8px;">
                      <a href="view_message.php?id=<?php echo $msg['id_message']; ?>" class="btn btn-secondary" style="height:28px; padding:2px 8px; font-size:11px;">Lihat</a>
                      <form action="manage_messages.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pesan ini secara permanen dari sistem?');" style="display: inline;">
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
