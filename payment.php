<?php
$page_title = 'Pembayaran Paket';
require_once __DIR__ . '/includes/config.php';

// Enforce login
requireLogin();

$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$pkg = null;
$error = '';
$success = '';

// Load package details
try {
    $stmt = $pdo->prepare("SELECT * FROM premium_packages WHERE id_package = ?");
    $stmt->execute([$package_id]);
    $pkg = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Gagal memuat paket.';
}

if (!$pkg) {
    header("Location: premium.php");
    exit;
}

$user_id = $_SESSION['user_id'];
// Fetch user premium details for invoice status display
$stmt = $pdo->prepare("SELECT premium_status, premium_until FROM users WHERE id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = sanitize($_POST['payment_method']);
    $simulated_status = sanitize($_POST['simulate_status']); // 'completed' or 'failed'
    
    // Generate simulated transactional metadata
    $transaction_id = 'TX-' . strtoupper(bin2hex(random_bytes(6)));
    $amount = $pkg['price'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insert Payment Log
        $insert = $pdo->prepare("
            INSERT INTO payments (id_user, id_package, amount, payment_method, transaction_id, payment_status, payment_date)
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $insert->execute([$user_id, $pkg['id_package'], $amount, $payment_method, $transaction_id, $simulated_status]);
        
        if ($simulated_status === 'success') {
            // 2. Fetch current user premium info
            $u_stmt = $pdo->prepare("SELECT premium_status, premium_until FROM users WHERE id_user = ?");
            $u_stmt->execute([$user_id]);
            $usr = $u_stmt->fetch();
            
            $duration_days = (int)$pkg['duration_days'];
            $new_until = null;
            
            if ($usr && $usr['premium_status'] == 1 && $usr['premium_until'] !== null) {
                // If already premium, extend from their current expiry date!
                $current_expiry = strtotime($usr['premium_until']);
                if ($current_expiry > time()) {
                    $new_until = date('Y-m-d H:i:s', strtotime("+$duration_days days", $current_expiry));
                } else {
                    $new_until = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
                }
            } else {
                // Not premium / expired, start from now
                $new_until = date('Y-m-d H:i:s', strtotime("+$duration_days days"));
            }
            
            // 3. Update User Premium details
            $update_usr = $pdo->prepare("UPDATE users SET premium_status = 1, premium_until = ? WHERE id_user = ?");
            $update_usr->execute([$new_until, $user_id]);
            
            // 4. Update session
            $_SESSION['user_premium'] = 1;
            
            $pdo->commit();
            $success = 'Pembayaran Sukses! Status premium Anda telah diaktifkan sampai ' . date('d-m-Y H:i', strtotime($new_until)) . '.';
        } else {
            // Payment failed, just commit the failed transaction log
            $pdo->commit();
            $error = 'Pembayaran Gagal! Simulasi pembayaran ditolak atau gagal dilakukan.';
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Terjadi kesalahan sistem saat memproses transaksi: ' . $e->getMessage();
    }
}
?>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="payment-mock-container" style="margin: 40px auto;">
  <div class="payment-mock-header">
    <span style="font-family: var(--font-display); font-size: 20px; font-weight: 500;">Simulasi Payment Gateway</span>
    <span class="badge badge-coral">Midtrans SandBox</span>
  </div>

  <div class="payment-mock-body">
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 20px;">
        <strong>Transaksi Gagal:</strong> <?php echo $error; ?>
        <p style="font-size: 12px; margin-top: 4px;">Anda dapat mencobanya kembali dengan memilih metode pembayaran lain.</p>
      </div>
      <div style="text-align: center; margin-top: 15px;">
        <a href="payment.php?package_id=<?php echo $pkg['id_package']; ?>" class="btn btn-primary">Coba Lagi</a>
        <a href="premium.php" class="btn btn-secondary">Pilih Paket Lain</a>
      </div>
    <?php elseif (!empty($success)): ?>
      <div class="alert alert-success" style="margin-bottom: 20px; text-align: center; display: block;">
        <span style="font-size: 32px; display: block; margin-bottom: 10px;">✓</span>
        <strong><?php echo $success; ?></strong>
      </div>
<?php
// Fetch receipt data for this transaction
$receipt_stmt = $pdo->prepare(
    "SELECT p.transaction_id, p.amount, p.payment_method, p.payment_status, p.payment_date,
            u.name AS user_name, u.email AS user_email,
            u.premium_status, u.premium_until, u.stop_date,
            pkg.package_name, pkg.duration_days
     FROM payments p
     JOIN users u ON p.id_user = u.id_user
     JOIN premium_packages pkg ON p.id_package = pkg.id_package
     WHERE p.transaction_id = ?"
);
$receipt_stmt->execute([$transaction_id]);
$pay = $receipt_stmt->fetch();

$package_status = ($pay['premium_status'] == 1) ? 'Aktif' : 'Dihentikan';
if ($pay['premium_status'] == 1 && !empty($pay['premium_until'])) {
    $active_until = date('d-m-Y H:i', strtotime($pay['premium_until']));
} else {
    $active_until = $pay['stop_date'] ? date('d-m-Y H:i', strtotime($pay['stop_date'])) : 'Tidak aktif';
}
?>
<div class="card" style="background-color: var(--color-surface-card); max-width:600px; margin:auto; border:1px solid var(--color-hairline-soft); padding:20px; margin-top:20px;">
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
    <tr><td style="padding:8px; font-weight:600;">Status Paket</td><td style="padding:8px;"><?php echo $package_status; ?></td></tr>
  </table>
</div>
<div style="text-align: center; margin-top: 25px;">
  <a href="dashboard_user.php" class="btn btn-primary" style="padding: 0 30px;">Ke Dashboard Anda</a>
  <a href="create_message.php" class="btn btn-secondary">Tulis Pesan Premium</a>
</div>
    <?php else: ?>
      <!-- Checkout Details -->
      <div style="margin-bottom: 25px; border-bottom: 1px solid var(--color-hairline); padding-bottom: 15px;">
        <h3 style="font-size: 22px; margin-bottom: 15px;">Detail Tagihan</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
          <tr>
            <td style="padding: 8px 0; color: var(--color-muted);">Paket Langganan:</td>
            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--color-ink);"><?php echo sanitize($pkg['package_name']); ?></td>
          </tr>
          <tr>
            <td style="padding: 8px 0; color: var(--color-muted);">Masa Aktif:</td>
            <td style="padding: 8px 0; text-align: right; font-weight: 600; color: var(--color-ink);"><?php echo $pkg['duration_days']; ?> Hari</td>
          </tr>
          <tr>
            <td style="padding: 12px 0 8px 0; font-weight: 600; font-size: 16px; border-top: 1px solid var(--color-hairline-soft);">Total Pembayaran:</td>
            <td style="padding: 12px 0 8px 0; text-align: right; font-weight: 600; font-size: 18px; color: var(--color-primary); border-top: 1px solid var(--color-hairline-soft);">
              <?php echo formatRupiah($pkg['price']); ?>
            </td>
          </tr>
        </table>
      </div>

      <!-- Simulated Checkout Form -->
      <form action="payment.php?package_id=<?php echo $pkg['id_package']; ?>" method="POST" id="payment-form">
        <h4 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: var(--color-ink);">Pilih Metode Pembayaran</h4>
        
        <div class="payment-method-option" onclick="document.getElementById('method-trf').checked = true;">
          <input type="radio" name="payment_method" id="method-trf" value="Bank Transfer" checked>
          <div>
            <div style="font-size: 14px; font-weight: 600;">Transfer Bank Virtual Account</div>
            <div style="font-size: 12px; color: var(--color-muted);">BCA, Mandiri, BNI, BRI</div>
          </div>
        </div>

        <div class="payment-method-option" onclick="document.getElementById('method-gopay').checked = true;">
          <input type="radio" name="payment_method" id="method-gopay" value="GoPay/QRIS">
          <div>
            <div style="font-size: 14px; font-weight: 600;">GoPay / QRIS</div>
            <div style="font-size: 12px; color: var(--color-muted);">Bayar instan dengan scan QR Code</div>
          </div>
        </div>

        <div class="payment-method-option" onclick="document.getElementById('method-card').checked = true;">
          <input type="radio" name="payment_method" id="method-card" value="Credit Card">
          <div>
            <div style="font-size: 14px; font-weight: 600;">Kartu Kredit / Debit</div>
            <div style="font-size: 12px; color: var(--color-muted);">Visa, MasterCard, JCB</div>
          </div>
        </div>

        <!-- Simulator status selector -->
        <input type="hidden" name="simulate_status" id="simulate-status-field" value="success">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-top: 25px;">
          <button type="submit" class="btn btn-primary" style="height: 44px;">Konfirmasi Pembayaran</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<script>
  function submitSimulatedPayment(status) {
    const field = document.getElementById('simulate-status-field');
    const form = document.getElementById('payment-form');
    if (field && form) {
      field.value = status;
      form.submit();
    }
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
