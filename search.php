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
            SELECT m.*, s.title as song_title, s.artist as song_artist, s.cover_image as song_cover, u.name as author_name 
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
            SELECT m.*, s.title as song_title, s.artist as song_artist, s.cover_image as song_cover, u.name as author_name 
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
        <a href="view_message.php?id=<?php echo $msg['id_message']; ?>" style="text-decoration: none; color: inherit; display: block;">
        <div class="message-card <?php echo $theme_class; ?>" style="cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
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
          
          <div style="margin-top: 15px; border-top: 1px solid rgba(0,0,0,0.08); padding-top: 15px; display: flex; align-items: center; gap: 12px;">
            <?php if (!empty($msg['song_cover'])): ?>
              <img src="<?php echo sanitize($msg['song_cover']); ?>" alt="Cover" style="width: 44px; height: 44px; border-radius: 4px; object-fit: cover;">
            <?php else: ?>
              <div style="width: 44px; height: 44px; border-radius: 4px; background: rgba(0,0,0,0.05);"></div>
            <?php endif; ?>
            <div style="flex: 1; overflow: hidden;">
              <div style="font-weight: 600; font-size: 14px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">
                <?php echo sanitize($msg['song_title']); ?>
              </div>
              <div style="color: var(--color-muted); font-size: 12px; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;">
                <?php echo sanitize($msg['song_artist']); ?>
              </div>
            </div>
            <svg style="width: 24px; height: 24px; fill: #1DB954; flex-shrink: 0;" viewBox="0 0 24 24">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.6 14.4c-.2.3-.6.4-.9.2-2.4-1.5-5.5-1.8-9.1-1-.4.1-.7-.2-.8-.6-.1-.4.2-.7.6-.8 4-.9 7.4-.5 10 .1.3.1.4.5.2.8zm1.3-2.9c-.2.4-.7.5-1.1.3-2.8-1.7-7.1-2.2-10.4-1.2-.5.1-.9-.1-1.1-.6-.1-.5.1-.9.6-1.1 3.8-1.2 8.6-.6 11.8 1.4.4.2.5.7.2 1.2zm.1-3c-3.3-2-8.8-2.2-12-1.2-.6.2-1.3-.2-1.4-.8-.2-.6.2-1.3.8-1.4 3.7-1.1 9.9-.9 13.7 1.4.5.3.7 1 .4 1.5-.3.6-.9.8-1.5.5z"/>
            </svg>
          </div>
        </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
