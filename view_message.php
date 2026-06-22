<?php
require_once __DIR__ . '/includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = null;
$error_msg = '';

try {
    // Fetch message details
    $stmt = $pdo->prepare("
        SELECT m.*, s.title as song_title, s.artist as song_artist, s.link as song_link, s.cover_image as song_cover, s.spotify_url as song_spotify, u.name as author_name 
        FROM messages m 
        JOIN songs s ON m.id_song = s.id_song 
        JOIN users u ON m.id_user = u.id_user 
        WHERE m.id_message = ?
    ");
    $stmt->execute([$id]);
    $msg = $stmt->fetch();
} catch (PDOException $e) {
    $error_msg = 'Terjadi kesalahan sistem saat mengambil data.';
}

if (!$msg) {
    $page_title = 'Pesan Tidak Ditemukan';
    require_once __DIR__ . '/includes/header.php';
    echo '<div style="max-width: 600px; margin: 60px auto; text-align: center;" class="card">';
    echo '<h2>Pesan Tidak Ditemukan</h2>';
    echo '<p style="color: var(--color-muted); margin-top: 15px;">Pesan yang Anda cari tidak ada atau telah dihapus.</p>';
    echo '<a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Kembali ke Beranda</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Scheduled check: Hide if scheduled date is in the future
$is_owner = isLoggedIn() && ($_SESSION['user_id'] == $msg['id_user']);
$is_admin = isAdmin();
$is_scheduled_future = false;

if ($msg['scheduled_date'] !== null) {
    $scheduled_time = strtotime($msg['scheduled_date']);
    if ($scheduled_time > time()) {
        $is_scheduled_future = true;
    }
}

if ($is_scheduled_future && !$is_owner && !$is_admin) {
    $page_title = 'Pesan Belum Dirilis';
    require_once __DIR__ . '/includes/header.php';
    echo '<div style="max-width: 600px; margin: 60px auto; text-align: center;" class="card">';
    echo '<h2>Pesan Belum Dirilis</h2>';
    echo '<p style="color: var(--color-muted); margin-top: 15px;">Pesan ini telah dijadwalkan oleh pengirim untuk dirilis pada tanggal <strong>' . date('d F Y', strtotime($msg['scheduled_date'])) . '</strong>.</p>';
    echo '<a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Kembali ke Beranda</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = 'Pesan untuk ' . sanitize($msg['recipient_name']);
require_once __DIR__ . '/includes/header.php';

$theme_class = 'theme-' . $msg['theme'];
$author = ($msg['anonymous'] == 1) ? 'Seseorang yang ingin dirahasiakan' : sanitize($msg['author_name']);
?>

<div class="message-detail-container">
  <div style="margin-bottom: 20px;">
    <a href="javascript:history.back()" style="font-weight: 500; font-size: 14px;">← Kembali</a>
  </div>

  <!-- Message Theme Container -->
  <div class="message-detail-card <?php echo $theme_class; ?>">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.08); padding-bottom: 15px; margin-bottom: 25px;">
      <div>
        <span style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.7;">Untuk Seseorang:</span>
        <h2 class="message-recipient" style="margin: 0; font-size: 32px;"><?php echo sanitize($msg['recipient_name']); ?></h2>
      </div>
      <div style="text-align: right; opacity: 0.8; font-size: 13px;">
        <div>Ditulis: <?php echo date('d M Y', strtotime($msg['created_at'])); ?></div>
        <?php if ($msg['private_message']): ?>
          <span class="badge badge-dark" style="font-size: 10px; margin-top: 4px;">🔒 Privat</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Content -->
    <div class="message-content-text">
      "<?php echo nl2br(sanitize($msg['message_content'])); ?>"
    </div>

    <!-- Author block -->
    <div style="margin-top: 30px; border-top: 1px solid rgba(0,0,0,0.08); padding-top: 15px; font-size: 14px; opacity: 0.9;">
      Dari: <strong><?php echo $author; ?></strong>
    </div>

    <!-- Integrated Song Card (Unsent Project Style) -->
    <?php 
      $click_url = !empty($msg['song_spotify']) ? $msg['song_spotify'] : (!empty($msg['song_link']) ? $msg['song_link'] : '#');
      $is_clickable = ($click_url !== '#');
    ?>
    <?php if ($is_clickable): ?>
      <a href="<?php echo sanitize($click_url); ?>" target="_blank" class="unsent-song-card-link" style="text-decoration: none; display: block; color: inherit;">
    <?php endif; ?>
    <div class="unsent-song-card" id="unsent-song-card">
      <div class="song-card-left">
        <?php if (!empty($msg['song_cover'])): ?>
          <img src="<?php echo sanitize($msg['song_cover']); ?>" alt="Cover Album" class="song-album-cover">
        <?php else: ?>
          <img src="images/default_cover.png" alt="Cover Album" class="song-album-cover">
        <?php endif; ?>
        <div class="song-metadata">
          <div class="song-title"><?php echo sanitize($msg['song_title']); ?></div>
          <div class="song-artist"><?php echo sanitize($msg['song_artist']); ?></div>
        </div>
      </div>
      <div class="song-card-right">
        <?php if (!empty($msg['song_spotify'])): ?>
          <div class="spotify-logo-container" title="Buka di Spotify">
            <svg viewBox="0 0 24 24" class="spotify-logo-svg" style="width: 24px; height: 24px; fill: currentColor;">
              <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.586 14.424c-.18.295-.565.387-.86.207-2.377-1.454-5.37-1.783-8.894-.982-.336.076-.668-.135-.744-.47-.076-.336.135-.668.47-.743 3.856-.88 7.15-.51 9.822 1.13.295.178.387.563.206.858zm1.225-2.72c-.226.367-.707.487-1.074.26-2.72-1.672-6.87-2.157-10.078-1.182-.413.125-.847-.11-.972-.522-.125-.413.11-.847.522-.972 3.67-1.114 8.24-.57 11.34 1.34.366.226.486.708.262 1.076zm.105-2.81c-3.258-1.934-8.634-2.113-11.747-1.168-.5.15-1.025-.133-1.176-.633-.15-.5.133-1.025.633-1.176 3.616-1.1 9.537-.893 13.29 1.336.45.267.6.843.333 1.293-.267.45-.843.6-1.293.333z"/>
            </svg>
          </div>
        <?php else: ?>
          <span class="view-link-fallback" style="font-size: 12px; font-weight: 500; display: flex; align-items: center; gap: 4px;">
            Buka Link ↗
          </span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($is_clickable): ?>
      </a>
    <?php endif; ?>
  </div>

  <!-- Meta notes for premium users -->
  <?php if ($is_owner): ?>
    <div class="card" style="margin-top: 20px; font-size: 14px; background-color: var(--color-surface-soft); border-style: dashed;">
      <p style="font-weight: 600; margin-bottom: 8px;">Informasi Pengirim (Hanya Anda yang dapat melihat kotak ini):</p>
      <ul style="list-style: none; padding-left: 0; line-height: 1.6;">
        <li><strong>Status Pesan:</strong> <?php echo $msg['private_message'] ? 'Privat (Tidak muncul di pencarian)' : 'Publik (Bisa dicari oleh siapapun)'; ?></li>
        <li><strong>Status Anonim:</strong> <?php echo $msg['anonymous'] ? 'Aktif (Nama Anda disembunyikan dari pembaca)' : 'Non-aktif (Nama Anda ditampilkan)'; ?></li>
        <li><strong>Tanggal Rilis:</strong> <?php echo $msg['scheduled_date'] ? date('d-m-Y', strtotime($msg['scheduled_date'])) : 'Instan (Langsung rilis)'; ?></li>
        <li><strong>Tema Terpilih:</strong> <span style="text-transform: capitalize;"><?php echo $msg['theme']; ?></span></li>
      </ul>
      <div style="margin-top: 15px;">
        <a href="edit_message.php?id=<?php echo $msg['id_message']; ?>" class="btn btn-primary" style="height: 32px; font-size: 12px; padding: 0 15px;">Edit Pesan</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
