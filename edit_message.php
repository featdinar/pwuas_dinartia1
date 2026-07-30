<?php
$page_title = 'Edit Pesan';
require_once __DIR__ . '/includes/config.php';

// Enforce login
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$is_prem = isPremium();
$error = '';
$msg = null;

// Fetch message details and verify ownership
try {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id_message = ? AND id_user = ?");
    $stmt->execute([$id, $user_id]);
    $msg = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Gagal memuat detail pesan.';
}

if (!$msg) {
    $_SESSION['flash_error'] = 'Pesan tidak ditemukan atau Anda tidak memiliki akses.';
    header("Location: my_messages.php");
    exit;
}

// Fetch songs list
try {
    $songs_stmt = $pdo->prepare("SELECT id_song, title, artist, cover_image, preview_url FROM songs ORDER BY title ASC");
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

    // Premium fields default to old values or standard values
    $anonymous = 0;
    $private_message = 0;
    $theme = 'cream';
    $scheduled_date = null;

    if ($is_prem) {
        $anonymous = isset($_POST['anonymous']) ? 1 : 0;
        $private_message = isset($_POST['private_message']) ? 1 : 0;
        $theme = sanitize($_POST['theme']);
        
        if (!in_array($theme, ['cream', 'navy', 'coral', 'teal'])) {
            $theme = 'cream';
        }
        
        $sched_post = trim($_POST['scheduled_date']);
        if (!empty($sched_post)) {
            $scheduled_date = $sched_post;
        }
    }

    if (empty($recipient_name) || empty($message_content) || $id_song <= 0) {
        $error = 'Penerima, isi pesan, dan lagu wajib diisi.';
    } else {
        // Validate song exists
        $song_check = $pdo->prepare("SELECT id_song FROM songs WHERE id_song = ?");
        $song_check->execute([$id_song]);
        if (!$song_check->fetch()) {
            $error = 'Lagu yang dipilih tidak valid.';
        } else {
            // Update database
            try {
                $update = $pdo->prepare("
                    UPDATE messages 
                    SET recipient_name = ?, message_content = ?, id_song = ?, anonymous = ?, private_message = ?, theme = ?, scheduled_date = ?
                    WHERE id_message = ? AND id_user = ?
                ");
                if ($update->execute([$recipient_name, $message_content, $id_song, $anonymous, $private_message, $theme, $scheduled_date, $id, $user_id])) {
                    $_SESSION['flash_success'] = 'Pesan Anda berhasil diperbarui!';
                    header("Location: my_messages.php");
                    exit;
                } else {
                    $error = 'Gagal memperbarui pesan. Silakan coba lagi.';
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
    <div style="margin-bottom: 20px;">
      <a href="my_messages.php" style="font-weight: 500; font-size: 14px;">← Kembali ke Daftar</a>
    </div>

    <h2>Edit Pesan Anda</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Ubah detail pesan rahasia yang telah Anda tulis sebelumnya.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger">
        <strong>Error:</strong> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form action="edit_message.php?id=<?php echo $id; ?>" method="POST" class="card" style="background-color: var(--color-surface-card);">
      
      <!-- Recipient name -->
      <div class="form-group">
        <label for="recipient_name" class="form-label">Nama Penerima / Inisial</label>
        <input type="text" id="recipient_name" name="recipient_name" class="form-input" placeholder="Masukkan nama penerima..." required value="<?php echo sanitize($msg['recipient_name']); ?>">
      </div>

      <!-- Message content -->
      <div class="form-group">
        <label for="message_content" class="form-label">Isi Pesan</label>
        <textarea id="message_content" name="message_content" class="form-input" required><?php echo sanitize($msg['message_content']); ?></textarea>
      </div>

      <!-- Song selection -->
      <?php 
        // Find current selected song info
        $current_song = null;
        foreach ($songs as $song) {
          if ($song['id_song'] == $msg['id_song']) {
            $current_song = $song;
            break;
          }
        }
      ?>
      <div class="form-group song-selection" style="position:relative;">
        <label class="form-label" for="song_search">Pilih Lagu Pendukung</label>
        <input type="text" id="song_search" class="form-input" placeholder="Cari lagu..." autocomplete="off" value="<?php echo $current_song ? sanitize($current_song['title']) : ''; ?>" />
        <input type="hidden" name="id_song" id="selected_song_id" value="<?php echo $msg['id_song']; ?>" />
        
        <div id="song_dropdown" class="song-dropdown" style="display:none; position:absolute; left:0; width:100%; max-height:280px; overflow-y:auto; background:var(--color-surface-card); border:1px solid var(--color-hairline); border-radius:12px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:1000; margin-top:4px;">
            <?php foreach ($songs as $song): ?>
            <div class="song-item" data-id="<?php echo $song['id_song']; ?>" data-title="<?php echo htmlspecialchars($song['title'], ENT_QUOTES); ?>" data-artist="<?php echo htmlspecialchars($song['artist'], ENT_QUOTES); ?>" data-cover="<?php echo $song['cover_image']; ?>" data-preview-url="<?php echo $song['preview_url']; ?>" style="display:flex; align-items:center; gap:12px; padding:12px; border-bottom:1px solid rgba(0,0,0,0.05); cursor:pointer;">
                <?php if (!empty($song['cover_image'])): ?>
                  <img src="<?php echo sanitize($song['cover_image']); ?>" alt="Cover" style="width:44px; height:44px; border-radius:4px; object-fit:cover;">
                <?php else: ?>
                  <div style="width:44px; height:44px; border-radius:4px; background:rgba(0,0,0,0.05);"></div>
                <?php endif; ?>
                <div style="flex:1; overflow:hidden;">
                    <div style="font-weight:600; font-size:14px; color:var(--color-ink); white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?php echo sanitize($song['title']); ?></div>
                    <div style="color:var(--color-muted); font-size:12px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?php echo sanitize($song['artist']); ?></div>
                </div>
                <svg style="width:20px; height:20px; fill:#1DB954; flex-shrink:0;" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.6 14.4c-.2.3-.6.4-.9.2-2.4-1.5-5.5-1.8-9.1-1-.4.1-.7-.2-.8-.6-.1-.4.2-.7.6-.8 4-.9 7.4-.5 10 .1.3.1.4.5.2.8zm1.3-2.9c-.2.4-.7.5-1.1.3-2.8-1.7-7.1-2.2-10.4-1.2-.5.1-.9-.1-1.1-.6-.1-.5.1-.9.6-1.1 3.8-1.2 8.6-.6 11.8 1.4.4.2.5.7.2 1.2zm.1-3c-3.3-2-8.8-2.2-12-1.2-.6.2-1.3-.2-1.4-.8-.2-.6.2-1.3.8-1.4 3.7-1.1 9.9-.9 13.7 1.4.5.3.7 1 .4 1.5-.3.6-.9.8-1.5.5z"/>
                </svg>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div id="song_preview" style="margin-top:15px; display:<?php echo $current_song ? 'block' : 'none'; ?>;">
            <div style="display:flex; align-items:center; gap:12px; padding:12px; border:1px solid var(--color-hairline); border-radius:8px; background:rgba(0,0,0,0.02);">
                <img id="preview-cover" src="<?php echo $current_song && !empty($current_song['cover_image']) ? sanitize($current_song['cover_image']) : ''; ?>" alt="Cover" style="width:44px; height:44px; border-radius:4px; object-fit:cover; display:<?php echo $current_song && !empty($current_song['cover_image']) ? 'block' : 'none'; ?>;">
                <div id="preview-cover-fallback" style="width:44px; height:44px; border-radius:4px; background:rgba(0,0,0,0.05); display:<?php echo !$current_song || empty($current_song['cover_image']) ? 'block' : 'none'; ?>;"></div>
                <div style="flex:1; overflow:hidden;">
                    <div id="preview-title" style="font-weight:600; font-size:14px; color:var(--color-ink); white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?php echo $current_song ? sanitize($current_song['title']) : ''; ?></div>
                    <div id="preview-artist" style="color:var(--color-muted); font-size:12px; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?php echo $current_song ? sanitize($current_song['artist']) : ''; ?></div>
                </div>
                <svg style="width:24px; height:24px; fill:#1DB954; flex-shrink:0;" viewBox="0 0 24 24">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.6 14.4c-.2.3-.6.4-.9.2-2.4-1.5-5.5-1.8-9.1-1-.4.1-.7-.2-.8-.6-.1-.4.2-.7.6-.8 4-.9 7.4-.5 10 .1.3.1.4.5.2.8zm1.3-2.9c-.2.4-.7.5-1.1.3-2.8-1.7-7.1-2.2-10.4-1.2-.5.1-.9-.1-1.1-.6-.1-.5.1-.9.6-1.1 3.8-1.2 8.6-.6 11.8 1.4.4.2.5.7.2 1.2zm.1-3c-3.3-2-8.8-2.2-12-1.2-.6.2-1.3-.2-1.4-.8-.2-.6.2-1.3.8-1.4 3.7-1.1 9.9-.9 13.7 1.4.5.3.7 1 .4 1.5-.3.6-.9.8-1.5.5z"/>
                </svg>
            </div>
            <audio id="preview-audio" controls style="margin-top:12px; width:100%; max-width:100%; display:<?php echo $current_song && !empty($current_song['preview_url']) ? 'block' : 'none'; ?>;" src="<?php echo $current_song ? sanitize($current_song['preview_url']) : ''; ?>"></audio>
        </div>
        <span style="font-size:12px; color:var(--color-muted); display:block; margin-top:8px;">Ketik untuk mencari dari daftar lagu yang tersedia.</span>
      </div>
      
<style>
.song-dropdown {
    animation: fadeSlideIn 0.2s ease-out forwards;
}
@keyframes fadeSlideIn {
    from { opacity:0; transform:translateY(-8px); }
    to { opacity:1; transform:translateY(0); }
}
.song-item:hover {
    background:rgba(0,0,0,0.03);
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('song_search');
    const dropdown = document.getElementById('song_dropdown');
    const hiddenInput = document.getElementById('selected_song_id');
    const items = dropdown.getElementsByClassName('song-item');

    // Show dropdown on focus
    searchInput.addEventListener('focus', () => {
        dropdown.style.display = 'block';
    });

    // Filter items as user types
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        let anyVisible = false;
        Array.from(items).forEach(item => {
            const title = item.dataset.title.toLowerCase();
            const artist = item.dataset.artist.toLowerCase();
            const match = title.includes(query) || artist.includes(query);
            item.style.display = match ? 'flex' : 'none';
            if (match) anyVisible = true;
        });
        dropdown.style.display = anyVisible ? 'block' : 'none';
    });

    // Click selection
    Array.from(items).forEach(item => {
        item.addEventListener('click', function() {
            hiddenInput.value = this.dataset.id;
            searchInput.value = this.dataset.title;
            // Update preview UI
            document.getElementById('preview-title').textContent = this.dataset.title;
            document.getElementById('preview-artist').textContent = this.dataset.artist;
            
            var coverImg = document.getElementById('preview-cover');
            var coverFallback = document.getElementById('preview-cover-fallback');
            
            if (this.dataset.cover) {
                coverImg.src = this.dataset.cover;
                coverImg.style.display = 'block';
                coverFallback.style.display = 'none';
            } else {
                coverImg.style.display = 'none';
                coverFallback.style.display = 'block';
            }
            
            var audioElem = document.getElementById('preview-audio');
            if (this.dataset.previewUrl) {
                audioElem.src = this.dataset.previewUrl;
                audioElem.style.display = 'block';
            } else {
                audioElem.removeAttribute('src');
                audioElem.style.display = 'none';
            }
            document.getElementById('song_preview').style.display = 'block';
            dropdown.style.display = 'none';
        });
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
</script>

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
              <input type="checkbox" name="anonymous" id="anonymous" value="1" style="margin-top: 4px;" <?php echo !$is_prem ? 'disabled' : ''; ?> <?php echo ($msg['anonymous'] == 1) ? 'checked' : ''; ?>>
              <label for="anonymous" style="font-size: 14px; font-weight: 500; cursor: pointer;">
                Kirim Secara Anonim
                <span style="display: block; font-size: 12px; font-weight: normal; color: var(--color-muted);">
                  Identitas Anda tidak akan ditampilkan ke publik maupun penerima.
                </span>
              </label>
            </div>

            <!-- Private Checkbox -->
            <div class="form-group" style="display: flex; align-items: flex-start; gap: 10px;">
              <input type="checkbox" name="private_message" id="private_message" value="1" style="margin-top: 4px;" <?php echo !$is_prem ? 'disabled' : ''; ?> <?php echo ($msg['private_message'] == 1) ? 'checked' : ''; ?>>
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
                <option value="cream" <?php echo ($msg['theme'] === 'cream') ? 'selected' : ''; ?>>Warm Cream (Default)</option>
                <option value="navy" <?php echo ($msg['theme'] === 'navy') ? 'selected' : ''; ?>>Dark Navy (Misterius)</option>
                <option value="coral" <?php echo ($msg['theme'] === 'coral') ? 'selected' : ''; ?>>Sunset Coral (Romantis)</option>
                <option value="teal" <?php echo ($msg['theme'] === 'teal') ? 'selected' : ''; ?>>Ocean Teal (Tenang)</option>
              </select>
            </div>

            <!-- Scheduled publication -->
            <div class="form-group">
              <label for="scheduled_date" class="form-label">Jadwalkan Tanggal Rilis (Opsional)</label>
              <input type="date" name="scheduled_date" id="scheduled_date" class="form-input" min="<?php echo date('Y-m-d'); ?>" <?php echo !$is_prem ? 'disabled' : ''; ?> value="<?php echo $msg['scheduled_date']; ?>">
              <span style="font-size: 11px; color: var(--color-muted);">Pesan Anda baru dapat dibaca secara publik setelah tanggal yang dipilih tercapai.</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Submit button -->
      <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
        <a href="my_messages.php" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary" style="padding: 0 30px;">Simpan Perubahan</button>
      </div>

    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
