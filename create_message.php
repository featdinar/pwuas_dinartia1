<?php
$page_title = 'Tulis Pesan Baru';
require_once __DIR__ . '/includes/config.php';

// Enforce login
requireLogin();

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];
$is_prem = isPremium();

// Fetch catalog of songs
try {
    $songs_stmt = $pdo->prepare("SELECT id_song, title, artist FROM songs ORDER BY title ASC");
    $songs_stmt->execute();
    $songs = $songs_stmt->fetchAll();
} catch (PDOException $e) {
    $songs = [];
    $error = 'Gagal memuat data lagu.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_name = sanitize($_POST['recipient_name']);
    $message_content = sanitize($_POST['message_content']);
    $id_song = (int)$_POST['id_song'];

    // Premium fields
    $anonymous = 0;
    $private_message = 0;
    $theme = 'cream';
    $scheduled_date = null;

    if ($is_prem) {
        $anonymous = isset($_POST['anonymous']) ? 1 : 0;
        $private_message = isset($_POST['private_message']) ? 1 : 0;
        $theme = sanitize($_POST['theme']);
        
        // Ensure theme is one of the valid options
        if (!in_array($theme, ['cream', 'navy', 'coral', 'teal'])) {
            $theme = 'cream';
        }
        
        $sched_post = trim($_POST['scheduled_date']);
        if (!empty($sched_post)) {
            $scheduled_date = $sched_post;
        }
    }

    // Validations
    if (empty($recipient_name) || empty($message_content) || $id_song <= 0) {
        $error = 'Penerima, isi pesan, dan lagu wajib diisi.';
    } else {
        // Validate song exists
        $song_check = $pdo->prepare("SELECT id_song FROM songs WHERE id_song = ?");
        $song_check->execute([$id_song]);
        if (!$song_check->fetch()) {
            $error = 'Lagu yang dipilih tidak valid.';
        } else {
            // Write message to database
            try {
                $insert = $pdo->prepare("
                    INSERT INTO messages (id_user, recipient_name, message_content, id_song, anonymous, private_message, theme, scheduled_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($insert->execute([$user_id, $recipient_name, $message_content, $id_song, $anonymous, $private_message, $theme, $scheduled_date])) {
                    $_SESSION['flash_success'] = 'Pesan Anda berhasil disimpan dan diterbitkan!';
                    header("Location: my_messages.php");
                    exit;
                } else {
                    $error = 'Gagal menyimpan pesan. Silakan coba lagi.';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan database: ' . $e->getMessage();
            }
        }
    }
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
    <h2>Tulis Pesan Baru</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Kirim pesan rahasia yang tak pernah tersampaikan ke seseorang, lengkap dengan melodi pilihannya.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <strong>Error:</strong> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form action="create_message.php" method="POST" class="card" style="background-color: var(--color-surface-card);">
      
      <!-- Recipient name -->
      <div class="form-group">
        <label for="recipient_name" class="form-label">Nama Penerima / Inisial</label>
        <input type="text" id="recipient_name" name="recipient_name" class="form-input" placeholder="Masukkan nama penerima..." required value="<?php echo isset($_POST['recipient_name']) ? sanitize($_POST['recipient_name']) : ''; ?>">
        <span style="font-size: 12px; color: var(--color-muted);">Gunakan nama panggilan atau inisial yang mudah dikenali oleh penerima.</span>
      </div>

      <!-- Message content -->
      <div class="form-group">
        <label for="message_content" class="form-label">Isi Pesan</label>
        <textarea id="message_content" name="message_content" class="form-input" placeholder="Tuliskan kata-kata yang ingin Anda sampaikan..." required><?php echo isset($_POST['message_content']) ? sanitize($_POST['message_content']) : ''; ?></textarea>
      </div>

      <!-- Song selection -->
      <div class="form-group song-selection">
        <label class="form-label">Pilih Lagu Pendukung</label>
        <div class="song-options" style="display: flex; overflow-x: auto; flex-wrap: nowrap; gap: var(--spacing-md);">
          <?php foreach ($songs as $song): ?>
            <label class="song-option" style="display:flex; flex-direction:column; align-items:center; cursor:pointer;">
              <input type="radio" name="id_song" value="<?php echo $song['id_song']; ?>" <?php echo (isset($_POST['id_song']) && $_POST['id_song'] == $song['id_song']) ? 'checked' : ''; ?> style="margin-bottom:5px;">
              <img src="<?php echo !empty($song['cover_image']) ? sanitize($song['cover_image']) : 'images/default_cover.png'; ?>" alt="Cover" style="width:80px; height:80px; object-fit:cover; border:1px solid var(--color-hairline-soft); border-radius:4px;">
              <span style="font-size:14px; margin-top:4px;"><?php echo sanitize($song['title']); ?> - <?php echo sanitize($song['artist']); ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <span style="font-size:12px; color:var(--color-muted);">Pilih dari daftar lagu yang tersedia di sistem kami.</span>
      </div>

      <!-- Premium section -->
      <div class="premium-locked-section">
        
        <?php if (!$is_prem): ?>
          <!-- Lock overlay for standard users -->
          <div class="premium-lock-overlay">
            <span style="font-size: 28px; margin-bottom: 5px;">🔒</span>
            <div class="lock-title">Fitur Premium Terkunci</div>
            <p style="font-size: 12px; max-width: 320px; color: var(--color-body); margin-bottom: 12px;">
              Tingkatkan status akun Anda menjadi Premium untuk menikmati pengiriman anonim, penyembunyian pesan, penjadwalan, dan pemilihan tema warna.
            </p>
            <a href="premium.php" class="btn btn-primary" style="height: 32px; font-size: 12px; padding: 0 15px;">Aktifkan Premium</a>
          </div>
        <?php endif; ?>

        <h3 style="font-size: 18px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
          <span>★</span> Opsi Fitur Premium
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
          <div>
            <!-- Anonymous Checkbox -->
            <div class="form-group" style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 15px;">
              <input type="checkbox" name="anonymous" id="anonymous" value="1" style="margin-top: 4px;" <?php echo !$is_prem ? 'disabled' : ''; ?>>
              <label for="anonymous" style="font-size: 14px; font-weight: 500; cursor: pointer;">
                Kirim Secara Anonim
                <span style="display: block; font-size: 12px; font-weight: normal; color: var(--color-muted);">
                  Identitas Anda tidak akan ditampilkan ke publik maupun penerima.
                </span>
              </label>
            </div>

            <!-- Private Checkbox -->
            <div class="form-group" style="display: flex; align-items: flex-start; gap: 10px;">
              <input type="checkbox" name="private_message" id="private_message" value="1" style="margin-top: 4px;" <?php echo !$is_prem ? 'disabled' : ''; ?>>
              <label for="private_message" style="font-size: 14px; font-weight: 500; cursor: pointer;">
                Jadikan Pesan Privat
                <span style="display: block; font-size: 12px; font-weight: normal; color: var(--color-muted);">
                  Pesan tidak akan muncul di kolom pencarian atau beranda. Hanya dapat dibuka melalui tautan langsung.
                </span>
              </label>
            </div>
          </div>

          <div>
            <!-- Custom theme -->
            <div class="form-group">
              <label for="theme" class="form-label">Pilih Tema Kartu & Halaman</label>
              <select name="theme" id="theme" class="form-input" <?php echo !$is_prem ? 'disabled' : ''; ?>>
                <option value="cream" selected>Warm Cream (Default)</option>
                <option value="navy">Dark Navy (Misterius)</option>
                <option value="coral">Sunset Coral (Romantis)</option>
                <option value="teal">Ocean Teal (Tenang)</option>
              </select>
            </div>

            <!-- Scheduled publication -->
            <div class="form-group">
              <label for="scheduled_date" class="form-label">Jadwalkan Tanggal Rilis (Opsional)</label>
              <input type="date" name="scheduled_date" id="scheduled_date" class="form-input" min="<?php echo date('Y-m-d'); ?>" <?php echo !$is_prem ? 'disabled' : ''; ?>>
              <span style="font-size: 11px; color: var(--color-muted);">Pesan Anda baru dapat dibaca secara publik setelah tanggal yang dipilih tercapai.</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Submit button -->
      <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
        <a href="dashboard_user.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary" style="padding: 0 30px;">Terbitkan Pesan</button>
      </div>

    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
