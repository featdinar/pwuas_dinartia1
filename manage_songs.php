<?php
$page_title = 'Kelola Lagu';
require_once __DIR__ . '/includes/config.php';

// Enforce admin access
requireAdmin();

$error = '';
$success = '';
$edit_song = null;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADD SONG
    if (isset($_POST['add_song'])) {
        $title = sanitize($_POST['title']);
        $artist = sanitize($_POST['artist']);
        $link = sanitize($_POST['link']);
        $cover_image = sanitize($_POST['cover_image']);
        $spotify_url = sanitize($_POST['spotify_url']);

        if (empty($title) || empty($artist) || empty($link)) {
            $error = 'Judul, artis, dan tautan lagu wajib diisi.';
        } else {
            try {
                $insert = $pdo->prepare("INSERT INTO songs (title, artist, link, cover_image, spotify_url) VALUES (?, ?, ?, ?, ?)");
                if ($insert->execute([$title, $artist, $link, empty($cover_image) ? null : $cover_image, empty($spotify_url) ? null : $spotify_url])) {
                    $success = 'Lagu baru berhasil ditambahkan ke katalog.';
                } else {
                    $error = 'Gagal menambahkan lagu.';
                }
            } catch (PDOException $e) {
                $error = 'Kesalahan database: ' . $e->getMessage();
            }
        }
    }

    // 2. UPDATE SONG
    elseif (isset($_POST['update_song'])) {
        $id_song = (int)$_POST['id_song'];
        $title = sanitize($_POST['title']);
        $artist = sanitize($_POST['artist']);
        $link = sanitize($_POST['link']);
        $cover_image = sanitize($_POST['cover_image']);
        $spotify_url = sanitize($_POST['spotify_url']);

        if (empty($title) || empty($artist) || empty($link)) {
            $error = 'Judul, artis, dan tautan lagu wajib diisi.';
        } else {
            try {
                $update = $pdo->prepare("UPDATE songs SET title = ?, artist = ?, link = ?, cover_image = ?, spotify_url = ? WHERE id_song = ?");
                if ($update->execute([$title, $artist, $link, empty($cover_image) ? null : $cover_image, empty($spotify_url) ? null : $spotify_url, $id_song])) {
                    $success = 'Informasi lagu berhasil diperbarui.';
                } else {
                    $error = 'Gagal memperbarui lagu.';
                }
            } catch (PDOException $e) {
                $error = 'Kesalahan database: ' . $e->getMessage();
            }
        }
    }

    // 3. DELETE SONG
    elseif (isset($_POST['delete_song'])) {
        $id_song = (int)$_POST['delete_id'];
        try {
            $delete = $pdo->prepare("DELETE FROM songs WHERE id_song = ?");
            if ($delete->execute([$id_song])) {
                $success = 'Lagu berhasil dihapus dari katalog.';
            } else {
                $error = 'Gagal menghapus lagu.';
            }
        } catch (PDOException $e) {
            $error = 'Gagal menghapus lagu. Lagu ini sedang digunakan oleh satu atau beberapa pesan.';
        }
    }
}

// Fetch song detail for editing if requested
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM songs WHERE id_song = ?");
        $stmt->execute([$edit_id]);
        $edit_song = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Gagal memuat detail lagu.';
    }
}

// Fetch all songs
try {
    $songs = $pdo->query("SELECT * FROM songs ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $songs = [];
    $error = 'Gagal mengambil katalog lagu.';
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
      <li><a href="manage_messages.php"><span>✉️</span> Kelola Pesan</a></li>
      <li><a href="manage_songs.php" class="active"><span>🎵</span> Kelola Katalog Lagu</a></li>
      <li><a href="manage_premium.php"><span>★</span> Kelola Paket Premium</a></li>
      <li><a href="manage_payments.php"><span>💳</span> Kelola Transaksi</a></li>
    </ul>
  </div>

  <!-- Main Area -->
  <div>
    <h2>Kelola Katalog Lagu</h2>
    <p style="color: var(--color-muted); margin-bottom: 25px;">Tambahkan lagu-lagu baru yang dapat dipilih oleh pengguna saat menyusun pesan.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><strong>Error:</strong> <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
      <div class="alert alert-success"><strong>Sukses:</strong> <?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Insert / Edit Panel -->
    <div style="margin-bottom: 30px;">
      <?php if ($edit_song): ?>
        <!-- Edit Song Card -->
        <div class="card" style="background-color: var(--color-surface-card);">
          <h3 style="font-size: 20px; margin-bottom: 15px;">Edit Lagu: <?php echo sanitize($edit_song['title']); ?></h3>
          <form action="manage_songs.php" method="POST">
            <input type="hidden" name="id_song" value="<?php echo $edit_song['id_song']; ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
              <div class="form-group">
                <label class="form-label">Judul Lagu</label>
                <input type="text" name="title" class="form-input" required value="<?php echo sanitize($edit_song['title']); ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Nama Artis / Band</label>
                <input type="text" name="artist" class="form-input" required value="<?php echo sanitize($edit_song['artist']); ?>">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
              <div class="form-group">
                <label class="form-label">Cover Album (URL)</label>
                <input type="url" name="cover_image" class="form-input" placeholder="https://..." value="<?php echo sanitize($edit_song['cover_image']); ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Tautan Spotify</label>
                <input type="url" name="spotify_url" class="form-input" placeholder="https://open.spotify.com/track/..." value="<?php echo sanitize($edit_song['spotify_url']); ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Tautan Lagu (YouTube/Lainnya)</label>
              <input type="url" name="link" class="form-input" required value="<?php echo sanitize($edit_song['link']); ?>">
              <span style="font-size: 11px; color:var(--color-muted);">Masukkan tautan URL eksternal pemutar lagu.</span>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 15px;">
              <a href="manage_songs.php" class="btn btn-secondary">Batal</a>
              <button type="submit" name="update_song" class="btn btn-primary">Simpan Perubahan</button>
            </div>
          </form>
        </div>
      <?php else: ?>
        <!-- Add Song Accordion Card -->
        <details class="card" style="background-color: var(--color-surface-card); cursor: pointer;" <?php echo isset($_POST['add_song']) && !empty($error) ? 'open' : ''; ?>>
          <summary style="font-size: 18px; font-weight: 500; outline: none; list-style: none; display: flex; justify-content: space-between; align-items: center;">
            <span>+ Tambahkan Lagu Baru ke Katalog</span>
            <span style="font-size: 12px; color: var(--color-muted);">Klik untuk membuka form</span>
          </summary>
          
          <form action="manage_songs.php" method="POST" style="margin-top: 20px; cursor: default;" onsubmit="event.stopPropagation();">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
              <div class="form-group">
                <label class="form-label">Judul Lagu</label>
                <input type="text" name="title" class="form-input" required value="<?php echo isset($_POST['title']) && isset($_POST['add_song']) ? sanitize($_POST['title']) : ''; ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Nama Artis / Band</label>
                <input type="text" name="artist" class="form-input" required value="<?php echo isset($_POST['artist']) && isset($_POST['add_song']) ? sanitize($_POST['artist']) : ''; ?>">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
              <div class="form-group">
                <label class="form-label">Cover Album (URL)</label>
                <input type="url" name="cover_image" class="form-input" placeholder="https://..." value="<?php echo isset($_POST['cover_image']) && isset($_POST['add_song']) ? sanitize($_POST['cover_image']) : ''; ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Tautan Spotify</label>
                <input type="url" name="spotify_url" class="form-input" placeholder="https://open.spotify.com/track/..." value="<?php echo isset($_POST['spotify_url']) && isset($_POST['add_song']) ? sanitize($_POST['spotify_url']) : ''; ?>">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Tautan Lagu (YouTube/Lainnya)</label>
              <input type="url" name="link" class="form-input" placeholder="https://..." required value="<?php echo isset($_POST['link']) && isset($_POST['add_song']) ? sanitize($_POST['link']) : ''; ?>">
            </div>

            <button type="submit" name="add_song" class="btn btn-primary" style="margin-top: 15px; float: right;">Tambah Lagu</button>
            <div style="clear: both;"></div>
          </form>
        </details>
      <?php endif; ?>
    </div>

    <!-- Songs List Table -->
    <div class="card" style="background-color: var(--color-surface-card);">
      <h3 style="font-size: 20px; margin-bottom: 15px;">Daftar Lagu Terkatalog</h3>
      
      <?php if (empty($songs)): ?>
        <p style="color: var(--color-muted); text-align: center; padding: 20px;">Katalog lagu kosong.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Judul Lagu</th>
                <th>Artis / Band</th>
                <th>Tautan</th>
                <th>Tanggal Ditambahkan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($songs as $sng): ?>
                <tr>
                  <td><?php echo $sng['id_song']; ?></td>
                  <td style="font-weight: 600; color: var(--color-ink);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <?php if (!empty($sng['cover_image'])): ?>
                        <img src="<?php echo sanitize($sng['cover_image']); ?>" alt="Cover" style="width: 36px; height: 36px; object-fit: cover; border-radius: 4px; border: 1px solid var(--color-hairline);">
                      <?php else: ?>
                        <div style="width: 36px; height: 36px; background-color: var(--color-surface-soft); border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 1px solid var(--color-hairline);">🎵</div>
                      <?php endif; ?>
                      <span><?php echo sanitize($sng['title']); ?></span>
                    </div>
                  </td>
                  <td><?php echo sanitize($sng['artist']); ?></td>
                  <td>
                    <div style="display: flex; flex-direction: column; gap: 4px; font-size: 13px;">
                      <a href="<?php echo sanitize($sng['link']); ?>" target="_blank" style="font-weight: 500;">
                        YouTube/Lainnya ↗
                      </a>
                      <?php if (!empty($sng['spotify_url'])): ?>
                        <a href="<?php echo sanitize($sng['spotify_url']); ?>" target="_blank" style="color: #1ed760; font-weight: 600;">
                          Spotify ↗
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td><?php echo date('d-m-Y H:i', strtotime($sng['created_at'])); ?></td>
                  <td>
                    <div style="display: flex; gap: 8px;">
                      <a href="manage_songs.php?edit=<?php echo $sng['id_song']; ?>" class="btn btn-secondary" style="height:28px; padding:2px 8px; font-size:11px;">Edit</a>
                      
                      <!-- Delete Form -->
                      <form action="manage_songs.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lagu ini? Lagu tidak bisa dihapus jika sedang tertaut dengan pesan.');" style="display: inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $sng['id_song']; ?>">
                        <button type="submit" name="delete_song" class="btn btn-danger" style="height:28px; padding:2px 8px; font-size:11px;">Hapus</button>
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
