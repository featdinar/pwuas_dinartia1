    </main>
  </div> <!-- Close app-container -->

  <!-- Footer -->
  <footer class="footer-container">
    <div class="footer-content">
      <div class="footer-brand">
        <h4>
          <svg style="width: 18px; height: 18px; fill: var(--color-primary);" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2v20M2 12h20M5 5l14 14M19 5L5 19" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
          </svg>
          Unsent
        </h4>
        <p style="max-width: 300px; margin-top: 10px; line-height: 1.6;">
          Menghubungkan kata-kata yang terlewatkan dengan lagu yang mewakili perasaan yang tak terucapkan.
        </p>
      </div>
      <div class="footer-links">
        <h5>Navigasi</h5>
        <ul>
          <li><a href="index.php">Beranda</a></li>
          <li><a href="search.php">Cari Penerima</a></li>
          <li><a href="premium.php">Layanan Premium</a></li>
        </ul>
      </div>
      <div class="footer-links">
        <h5>Akun</h5>
        <ul>
          <?php if (isLoggedIn()): ?>
            <li><a href="dashboard_user.php">Dashboard</a></li>
            <li><a href="profile.php">Pengaturan Profil</a></li>
            <li><a href="logout.php">Keluar</a></li>
          <?php else: ?>
            <li><a href="login.php">Masuk Akun</a></li>
            <li><a href="register.php">Daftar Baru</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Unsent. Hak Cipta Dilindungi.</p>
      <p style="font-size: 12px; color: var(--color-on-dark-soft);">
        Designed in compliance with the Claude warm-canvas aesthetic system.
      </p>
    </div>
  </footer>

</body>
</html>
