<?php
$page_title = 'Bukti Pembayaran';
require_once __DIR__ . '/includes/config.php';
requireLogin();

$transaction_id = isset($_GET['transaction_id']) ? sanitize($_GET['transaction_id']) : '';
if (empty($transaction_id)) {
    die('ID transaksi tidak diberikan.');
}

try {
    $stmt = $pdo->prepare(
        "SELECT p.transaction_id, p.amount, p.payment_method, p.payment_status, p.payment_date,
                u.name AS user_name, u.email AS user_email,
                pkg.package_name, pkg.duration_days
         FROM payments p
         JOIN users u ON p.id_user = u.id_user
         JOIN premium_packages pkg ON p.id_package = pkg.id_package
         WHERE p.transaction_id = ?"
    );
    $stmt->execute([$transaction_id]);
    $pay = $stmt->fetch();
    if (!$pay) {
        die('Transaksi tidak ditemukan.');
    }
    // Calculate active period if premium is active
$active_until = ($pay['payment_status'] === 'completed' || $pay['payment_status'] === 'success')
    ? date('d-m-Y H:i', strtotime($pay['payment_date'] . " +" . $pay['duration_days'] . " days"))
    : 'Tidak aktif';
} catch (PDOException $e) {
    die('Kesalahan basis data: ' . $e->getMessage());
}
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>
<div class="dashboard-grid">
  <div class="sidebar-card">
    <h3 style="font-size:20px; border-bottom:1px solid var(--color-hairline); padding-bottom:10px; margin-bottom:15px;">Bukti Pembayaran</h3>
    <ul class="sidebar-menu">
      <li><a href="dashboard_user.php"><span>📊</span> Dashboard</a></li>
      <li><a href="profile.php"><span>⚙️</span> Pengaturan Profil</a></li>
    </ul>
  </div>
  <div>
    <h2>Bukti Pembayaran</h2>
    <div class="card" style="background-color: var(--color-surface-card); max-width:600px; margin:auto; border:1px solid var(--color-hairline-soft); padding:20px;">
      <table style="width:100%; border-collapse:collapse; font-size:14px;">
        <tr><td style="padding:8px; font-weight:600;">Transaction ID</td><td style="padding:8px;"><?php echo htmlspecialchars($pay['transaction_id']); ?></td></tr>
        <tr><td style="padding:8px; font-weight:600;">Nama User</td><td style="padding:8px;"><?php echo htmlspecialchars($pay['user_name']); ?></td></tr>
        <tr><td style="padding:8px; font-weight:600;">Email User</td><td style="padding:8px;"><?php echo htmlspecialchars($pay['user_email']); ?></td></tr>
        <tr><td style="padding:8px; font-weight:600;">Paket Premium</td><td style="padding:8px;"><?php echo htmlspecialchars($pay['package_name']); ?></td></tr>
        <tr><td style="padding:8px; font-weight:600;">Harga</td><td style="padding:8px;"><?php echo formatRupiah($pay['amount']); ?></td></tr>
        <tr><td style="padding:8px; font-weight:600;">Metode Pembayaran</td><td style="padding:8px;"><?php echo htmlspecialchars($pay['payment_method']); ?></td></tr>
        <tr><td style="padding:8px; font-weight:600;">Status Pembayaran</td><td style="padding:8px;"><?php echo ucfirst(htmlspecialchars($pay['payment_status'])); ?></td></tr>
        <tr><td style="padding:8px; font-weight:600;">Tanggal Pembayaran</td><td style="padding:8px;"><?php echo date('d-m-Y H:i', strtotime($pay['payment_date'])); ?></td></tr>
        <tr><td style="padding:8px; font-weight:600;">Masa Aktif Premium</td><td style="padding:8px;"><?php echo $active_until; ?></td></tr>
      </table>
      <div style="margin-top:20px; text-align:center;">
        <a href="dashboard_user.php" class="btn btn-primary" style="padding:8px 20px;">Kembali ke Dashboard</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
