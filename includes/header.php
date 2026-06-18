<?php
require_once __DIR__ . '/config.php';

// Determine active file for link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title . ' - Unsent' : 'Unsent - The Messages You Never Sent'; ?></title>
  
  <!-- CSS Stylesheet -->
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

  <!-- Top Navigation -->
  <header class="top-nav-container">
    <div class="top-nav">
      <!-- Logo -->
      <a href="index.php" class="logo-link">
        <svg class="logo-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <!-- Anthropic-style 4-spoke radial-spike mark logo -->
          <path d="M12 2v20M2 12h20M5 5l14 14M19 5L5 19" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/>
        </svg>
        <span>Unsent</span>
      </a>

      <!-- Navigation Links -->
      <nav class="nav-links">
        <a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Beranda</a>
        <a href="search.php" class="<?php echo $current_page == 'search.php' ? 'active' : ''; ?>">Cari Penerima</a>
        <a href="premium.php" class="<?php echo $current_page == 'premium.php' ? 'active' : ''; ?>">Premium</a>

        <?php if (isLoggedIn()): ?>
          <?php if (isAdmin()): ?>
            <!-- Admin Links -->
            <a href="dashboard_admin.php" class="<?php echo $current_page == 'dashboard_admin.php' ? 'active' : ''; ?>">Admin Dashboard</a>
          <?php else: ?>
            <!-- Regular User Links -->
            <a href="dashboard_user.php" class="<?php echo $current_page == 'dashboard_user.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="create_message.php" class="<?php echo $current_page == 'create_message.php' ? 'active' : ''; ?>">Tulis Pesan</a>
            <a href="my_messages.php" class="<?php echo $current_page == 'my_messages.php' ? 'active' : ''; ?>">Pesan Saya</a>
          <?php endif; ?>
        <?php endif; ?>
      </nav>

      <!-- Auth Actions -->
      <div class="nav-actions">
        <?php if (isLoggedIn()): ?>
          <!-- User Details / Role Badges -->
          <?php if (isAdmin()): ?>
            <span class="badge badge-dark">Admin</span>
          <?php elseif (isPremium()): ?>
            <span class="badge badge-coral">★ Premium</span>
          <?php else: ?>
            <span class="badge badge-cream">Standard</span>
          <?php endif; ?>
          
          <a href="profile.php" class="btn btn-secondary" style="height:36px; padding:8px 14px;">Profil</a>
          <a href="logout.php" class="btn btn-primary" style="height:36px; padding:8px 14px;">Keluar</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-secondary" style="height:36px; padding:8px 14px;">Masuk</a>
          <a href="register.php" class="btn btn-primary" style="height:36px; padding:8px 14px;">Daftar</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- App Wrapper -->
  <div class="app-container">
    <main class="main-content">
