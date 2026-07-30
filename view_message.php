<?php
require_once __DIR__ . '/includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = null;
$error_msg = '';

try {
    // Fetch message details
    $stmt = $pdo->prepare(
        "
        SELECT m.*, s.title as song_title, s.artist as song_artist, s.link as song_link, s.cover_image as song_cover, s.spotify_url as song_spotify, s.preview_url as song_preview, u.name as author_name 
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

<?php if (!empty($msg['song_spotify'])): ?>
    <?php
        $spotify_url = $msg['song_spotify'];
        $track_id = '';
        $parsed = parse_url($spotify_url);
        if (isset($parsed['path'])) {
            $path_parts = explode('/', trim($parsed['path'], '/'));
            if (count($path_parts) >= 2 && $path_parts[0] === 'track') {
                $track_id = $path_parts[1];
            }
        }
    ?>
    <div style="margin-top: 25px;">
        <?php if ($track_id): ?>
            <iframe style="border-radius:12px" src="https://open.spotify.com/embed/track/<?php echo htmlspecialchars($track_id); ?>?utm_source=generator" width="100%" height="80" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
        <?php else: ?>
            <div class="song-card" style="display:flex; align-items:center; gap:12px; padding:10px; background:var(--color-surface-soft); border-radius:8px;">
                <?php if (!empty($msg['song_cover'])): ?>
                    <img src="<?= htmlspecialchars($msg['song_cover']) ?>" alt="Cover" style="width:60px; height:60px; object-fit:cover; border-radius:4px;">
                <?php endif; ?>
                <div style="flex: 1;">
                    <div style="font-weight:600;"><?php echo htmlspecialchars($msg['song_title'] ?? '') ?></div>
                    <div style="color:var(--color-muted); font-size:0.9em;"><?php echo htmlspecialchars($msg['song_artist'] ?? '') ?></div>
                    <?php if (!empty($msg['song_preview'])): ?>
                        <audio src="<?php echo htmlspecialchars($msg['song_preview']) ?>" controls style="margin-top:8px; width:100%; max-width:300px;"></audio>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>



</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
