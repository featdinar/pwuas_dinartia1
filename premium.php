<?php
$page_title = 'Paket Premium';
require_once __DIR__ . '/includes/header.php';

// Fetch active premium packages
try {
    $stmt = $pdo->prepare("SELECT * FROM premium_packages ORDER BY price ASC");
    $stmt->execute();
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    $packages = [];
    $error = 'Gagal memuat paket premium.';
}

// User current status
$user_premium = isPremium();
$expiry = getPremiumExpiry();
?>

<div style="text-align: center; max-width: 800px; margin: 0 auto 40px auto;">
  <span class="badge badge-coral" style="margin-bottom: 12px;">Paket Langganan</span>
  <h2 style="font-size: 38px; margin-bottom: 15px;">Tingkatkan Layanan ke Premium</h2>
  <p style="color: var(--color-muted); font-size: 16px;">
    Tulis pesan tanpa batas dan ungkapkan perasaan Anda dengan berbagai fitur kustomisasi terbaik kami. Pilih paket yang sesuai untuk Anda.
  </p>

  <?php if ($user_premium && $expiry): ?>
    <div class="alert alert-success" style="margin-top: 25px; display: inline-flex; justify-content: center; width: auto; gap: 10px;">
      <span><strong>Langganan Anda Aktif!</strong> Anda memiliki status premium hingga <strong><?php echo date('d-m-Y H:i', strtotime($expiry)); ?></strong>. Anda masih bisa membeli paket tambahan untuk memperpanjang durasi.</span>
    </div>
  <?php endif; ?>
</div>

<div class="pricing-grid">
  <?php foreach ($packages as $index => $pkg): ?>
    <?php 
      // Mark Silver Premium (typically the second one / index 1) as the featured card!
      $is_featured = ($index === 1);
      $card_class = $is_featured ? 'pricing-card featured' : 'pricing-card';
      $btn_class = $is_featured ? 'btn btn-primary' : 'btn btn-secondary';
      
      // Parse package features
      $features_list = array_map('trim', explode(',', $pkg['features']));
    ?>
    <div class="<?php echo $card_class; ?>">
      <div>
        <h3 class="price-title"><?php echo sanitize($pkg['package_name']); ?></h3>
        
        <?php if ($is_featured): ?>
          <span class="badge badge-coral" style="position: absolute; top: 20px; right: 20px;">Paling Populer</span>
        <?php endif; ?>

        <div class="price-amount">
          <?php echo formatRupiah($pkg['price']); ?>
          <span class="price-period">/ <?php echo $pkg['duration_days']; ?> Hari</span>
        </div>

        <ul class="price-features">
          <?php foreach ($features_list as $feat): ?>
            <li>
              <svg style="width: 16px; height: 16px;" viewBox="0 0 24 24">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
              </svg>
              <?php echo sanitize($feat); ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div style="margin-top: 30px;">
        <?php if (isLoggedIn()): ?>
          <a href="payment.php?package_id=<?php echo $pkg['id_package']; ?>" class="<?php echo $btn_class; ?>" style="width: 100%; height: 44px;">Beli Paket Ini</a>
        <?php else: ?>
          <a href="login.php" class="<?php echo $btn_class; ?>" style="width: 100%; height: 44px;">Masuk Untuk Membeli</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Extra note block -->
<div class="card" style="margin-top: 40px; background-color: var(--color-surface-soft); text-align: center; border-style: dashed;">
  <h4 style="font-size: 20px; margin-bottom: 8px;">Jaminan Pembayaran Aman & Mudah</h4>
  <p style="font-size: 14px; color: var(--color-muted); max-width: 600px; margin: 0 auto;">
    Proses pembayaran disimulasikan menggunakan Payment Gateway dummy. Anda dapat mensimulasikan pembayaran tanpa menggunakan kartu kredit atau uang sungguhan untuk mempermudah pengujian.
  </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
