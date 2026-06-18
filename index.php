<?php
$page_title = 'Pesan yang Tak Terkirim';
require_once __DIR__ . '/includes/header.php';

// Fetch the 6 latest public messages
try {
    $stmt = $pdo->prepare("
        SELECT m.*, s.title as song_title, s.artist as song_artist, u.name as author_name 
        FROM messages m 
        JOIN songs s ON m.id_song = s.id_song 
        JOIN users u ON m.id_user = u.id_user 
        WHERE m.private_message = 0 
          AND (m.scheduled_date IS NULL OR m.scheduled_date <= CURRENT_DATE())
        ORDER BY m.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute();
    $latest_messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $latest_messages = [];
}
?>

<!-- Hero Section -->
<section class="hero">
  <div>
    <h1 class="hero-title">The Messages You Never Sent.</h1>
    <p class="hero-subtitle">
      Tuliskan pesan yang tidak pernah sempat disampaikan kepada seseorang, hubungkan dengan melodi lagu yang mewakili perasaan Anda, dan biarkan ia mengapung di lautan kata-kata.
    </p>
    <div class="hero-actions">
      <a href="create_message.php" class="btn btn-primary">Tulis Pesan Sekarang</a>
      <a href="search.php" class="btn btn-secondary">Cari Pesan Penerima</a>
    </div>
  </div>
  <div class="hero-illustration">
    <!-- Inline minimal SVG doodle of an envelope floating out of a music vinyl -->
    <svg class="hero-doodle" viewBox="0 0 100 100">
      <circle cx="50" cy="50" r="40" stroke="#cc785c" stroke-width="2" fill="none" stroke-dasharray="4 2" />
      <circle cx="50" cy="50" r="15" stroke="#141413" stroke-width="1.5" fill="none" />
      <path d="M40 45 h20 v12 h-20 z M40 45 l10 7 l10 -7" stroke="#cc785c" stroke-width="1.5" fill="none" />
      <path d="M25 25 Q35 15 50 25 T75 25" stroke="#8e8b82" stroke-width="1" fill="none" />
    </svg>
    <p style="font-family: var(--font-display); font-size: 18px; color: var(--color-ink);">Pesan & Musik</p>
    <span style="font-size: 12px; color: var(--color-muted); margin-top: 4px;">Terhubung secara tulus</span>
  </div>
</section>

<!-- Search Section -->
<section class="search-container">
  <h2 style="font-size: 28px; text-align: center; margin-bottom: 20px;">Cari Pesan Berdasarkan Penerima</h2>
  <form action="search.php" method="GET" class="search-form">
    <input type="text" name="q" placeholder="Masukkan nama penerima (misal: Adinda, Sarah, Budi)..." class="form-input" required>
    <button type="submit" class="btn btn-primary">Cari Pesan</button>
  </form>
</section>

<!-- Latest Messages Section -->
<section style="margin-bottom: var(--spacing-section);">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--spacing-lg);">
    <h2>Pesan-Pesan Terbaru</h2>
    <a href="search.php" style="font-weight: 500; font-size: 14px;">Lihat semua pesan →</a>
  </div>

  <?php if (empty($latest_messages)): ?>
    <div class="card" style="text-align: center; padding: 48px;">
      <p style="color: var(--color-muted); font-size: 18px;">Belum ada pesan publik yang dikirimkan.</p>
      <a href="create_message.php" class="btn btn-primary" style="margin-top: 15px;">Jadilah yang pertama menulis</a>
    </div>
  <?php else: ?>
    <div class="messages-grid">
      <?php foreach ($latest_messages as $msg): ?>
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
</section>

<!-- Premium Advertisement Section -->
<section style="background-color: var(--color-surface-soft); border-radius: var(--rounded-lg); padding: var(--spacing-xxl) var(--spacing-xl); margin-bottom: var(--spacing-section); border: 1px solid var(--color-hairline);">
  <div style="text-align: center; max-width: 700px; margin: 0 auto;">
    <span class="badge badge-coral" style="margin-bottom: 12px;">Fitur Eksklusif</span>
    <h2 style="font-size: 36px; margin-bottom: 15px;">Dapatkan Kontrol Lebih dengan Premium</h2>
    <p style="color: var(--color-body); margin-bottom: 30px;">
      Ekspresikan perasaan Anda secara lebih personal dengan paket premium kami. Dapatkan akses ke fitur-fitur eksklusif untuk menyempurnakan pesan Anda.
    </p>
  </div>

  <div class="feature-grid">
    <div class="feature-card">
      <div class="feature-icon">👤</div>
      <h3 style="font-size: 20px; font-weight: 500; margin-bottom: 8px;">Kirim secara Anonim</h3>
      <p style="font-size: 14px; color: var(--color-muted); flex: 1;">
        Sembunyikan profil pengirim Anda. Pesan akan tampil dengan pengirim "Seseorang" tanpa mengungkap identitas asli Anda di sistem.
      </p>
    </div>

    <div class="feature-card">
      <div class="feature-icon">🔒</div>
      <h3 style="font-size: 20px; font-weight: 500; margin-bottom: 8px;">Pesan Privat</h3>
      <p style="font-size: 14px; color: var(--color-muted); flex: 1;">
        Kunci pesan Anda agar tidak terindeks di hasil pencarian publik atau halaman utama. Hanya orang yang memiliki tautan langsung yang dapat membacanya.
      </p>
    </div>

    <div class="feature-card">
      <div class="feature-icon">📅</div>
      <h3 style="font-size: 20px; font-weight: 500; margin-bottom: 8px;">Publikasi Terjadwal</h3>
      <p style="font-size: 14px; color: var(--color-muted); flex: 1;">
        Tentukan tanggal rilis pesan Anda di masa depan. Pesan tidak akan dipublikasikan sampai tanggal yang Anda jadwalkan tercapai.
      </p>
    </div>

    <div class="feature-card">
      <div class="feature-icon">🎨</div>
      <h3 style="font-size: 20px; font-weight: 500; margin-bottom: 8px;">Tema Kustom</h3>
      <p style="font-size: 14px; color: var(--color-muted); flex: 1;">
        Pilih warna latar belakang kartu dan halaman pembacaan yang sesuai dengan mood pesan Anda (Warm Cream, Dark Navy, Sunset Coral, Ocean Teal).
      </p>
    </div>
  </div>

  <div style="text-align: center; margin-top: 40px;">
    <a href="premium.php" class="btn btn-primary" style="height: 46px; padding: 0 30px;">Lihat Paket Premium</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
