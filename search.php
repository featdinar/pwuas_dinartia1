<?php
$page_title = 'Cari Penerima Pesan';
require_once __DIR__ . '/includes/header.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$messages = [];
$searched = false;

try {
    if ($query !== '') {
        $searched = true;
        // Search by recipient name
        $stmt = $pdo->prepare("
            SELECT m.*, s.title as song_title, s.artist as song_artist, u.name as author_name 
            FROM messages m 
            JOIN songs s ON m.id_song = s.id_song 
            JOIN users u ON m.id_user = u.id_user 
            WHERE m.private_message = 0 
              AND (m.scheduled_date IS NULL OR m.scheduled_date <= CURRENT_DATE())
              AND m.recipient_name LIKE ?
            ORDER BY m.created_at DESC
        ");
        $stmt->execute(['%' . $query . '%']);
        $messages = $stmt->fetchAll();
    } else {
        // Retrieve all public messages
        $stmt = $pdo->prepare("
            SELECT m.*, s.title as song_title, s.artist as song_artist, u.name as author_name 
            FROM messages m 
            JOIN songs s ON m.id_song = s.id_song 
            JOIN users u ON m.id_user = u.id_user 
            WHERE m.private_message = 0 
              AND (m.scheduled_date IS NULL OR m.scheduled_date <= CURRENT_DATE())
            ORDER BY m.created_at DESC
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $error = 'Terjadi kesalahan sistem saat mengambil data.';
}
?>

<div style="margin-bottom: 40px;">
  <h2 style="font-size: 36px; text-align: center; margin-bottom: 10px;">Cari Pesan Unsent</h2>
  <p style="text-align: center; color: var(--color-muted); margin-bottom: 30px;">
    Temukan pesan yang ditulis seseorang untuk Anda dengan memasukkan nama Anda di kolom pencarian di bawah ini.
  </p>

  <!-- Search input -->
  <div class="search-container">
    <form action="search.php" method="GET" class="search-form">
      <input type="text" name="q" placeholder="Masukkan nama penerima..." class="form-input" value="<?php echo sanitize($query); ?>" required>
      <button type="submit" class="btn btn-primary">Cari Penerima</button>
    </form>
  </div>
</div>

<div style="margin-bottom: var(--spacing-section);">
  <?php if ($searched): ?>
    <h3 style="margin-bottom: var(--spacing-lg);">Hasil pencarian untuk "<strong><?php echo sanitize($query); ?></strong>" (<?php echo count($messages); ?> ditemukan)</h3>
  <?php else: ?>
    <h3 style="margin-bottom: var(--spacing-lg);">Semua Pesan Publik</h3>
  <?php endif; ?>

  <?php if (empty($messages)): ?>
    <div class="card" style="text-align: center; padding: 48px;">
      <p style="color: var(--color-muted); font-size: 18px; margin-bottom: 15px;">
        <?php echo $searched ? 'Tidak ada pesan yang ditemukan untuk nama penerima tersebut.' : 'Belum ada pesan publik yang ditulis.'; ?>
      </p>
      <p style="font-size: 14px; color: var(--color-muted);">
        Tips: Coba cari dengan variasi nama atau ejaan yang lain.
      </p>
      <a href="search.php" class="btn btn-secondary" style="margin-top: 20px;">Tampilkan Semua Pesan</a>
    </div>
  <?php else: ?>
    <div class="messages-grid">
      <?php foreach ($messages as $msg): ?>
        <?php 
          $theme_class = 'theme-' . $msg['theme'];
          $author = ($msg['anonymous'] == 1) ? 'Seseorang' : sanitize($msg['author_name']);
        ?>
        <div class="message-card <?php echo $theme_class; ?>">
          <div>
            <div class="message-meta">
              <span>Untuk:</span>
              <span><?php echo date('d M Y', strtotime($msg['created_at'])); ?></span>
            </div>
            <h3 class="message-recipient"><?php echo sanitize($msg['recipient_name']); ?></h3>
            <p class="message-snippet">
              "<?php echo nl2br(sanitize($msg['message_content'])); ?>"
            </p>
          </div>
          
          <div>
            <div class="message-song-tag">
              <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24">
                <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
              </svg>
              <span><?php echo sanitize($msg['song_title']); ?> - <?php echo sanitize($msg['song_artist']); ?></span>
            </div>
            
            <div style="margin-top: 15px; text-align: right;">
              <a href="view_message.php?id=<?php echo $msg['id_message']; ?>" class="btn btn-secondary" style="height:32px; padding:4px 12px; font-size:12px;">Baca Selengkapnya</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
