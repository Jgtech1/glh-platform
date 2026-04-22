<?php
require_once 'includes/header.php';

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_currency') {
        $selectedCurrency = $_POST['preferred_currency'] ?? '';

        if ($currencyManager->isValidCurrency($selectedCurrency)) {
            $userObj->updatePreferredCurrency($_SESSION['user_id'], $selectedCurrency);
            $_SESSION['currency'] = $selectedCurrency;
            setcookie('user_currency', $selectedCurrency, time() + (86400 * 30), '/');
            $success = 'Currency preference updated successfully.';
            // JS redirect instead of header() — safe after HTML output
            echo '<script>window.location.href = "settings.php?saved=1";</script>';
            exit();
        } else {
            $errors[] = 'Invalid currency selected.';
        }
    }

    if ($_POST['action'] === 'update_notifications') {
        $notifyOrders   = isset($_POST['notify_orders'])    ? 1 : 0;
        $notifyLowStock = isset($_POST['notify_low_stock']) ? 1 : 0;
        $lowStockQty    = max(1, intval($_POST['low_stock_threshold'] ?? 10));
        $uid            = $_SESSION['user_id'];

        try {
            $stmt = $conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            foreach ([
                ["producer_notify_orders_{$uid}",    $notifyOrders],
                ["producer_notify_low_stock_{$uid}", $notifyLowStock],
                ["producer_low_stock_qty_{$uid}",    $lowStockQty],
            ] as [$key, $val]) {
                $stmt->execute([$key, $val]);
            }
            $success = 'Notification preferences saved.';
        } catch (PDOException $e) {
            $errors[] = 'Could not save notification settings.';
        }
    }
}

if (isset($_GET['saved']) && empty($success)) {
    $success = 'Currency preference updated successfully.';
}

$allCurrencies = $currencyManager->getAllCurrencies();
$currentCode   = $currentCurrency['currency_code'] ?? 'USD';

function getProducerSetting($conn, $key, $default = null) {
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

$uid            = $_SESSION['user_id'];
$notifyOrders   = getProducerSetting($conn, "producer_notify_orders_{$uid}",    1);
$notifyLowStock = getProducerSetting($conn, "producer_notify_low_stock_{$uid}", 1);
$lowStockQty    = getProducerSetting($conn, "producer_low_stock_qty_{$uid}",    10);
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<div class="row">

    <!-- Currency Preference -->
    <div class="col-lg-6 mb-4">
        <div class="stat-card h-100">
            <h6 class="fw-bold mb-4">
                <i class="fas fa-coins me-2 text-success"></i>Currency Preference
            </h6>
            <form method="POST">
                <input type="hidden" name="action" value="update_currency">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Display Currency</label>
                    <select name="preferred_currency" class="form-select">
                        <?php foreach ($allCurrencies as $currency): ?>
                            <option value="<?php echo $currency['currency_code']; ?>"
                                <?php echo $currency['currency_code'] === $currentCode ? 'selected' : ''; ?>>
                                <?php echo $currency['currency_symbol']; ?>
                                <?php echo $currency['currency_name']; ?>
                                (<?php echo $currency['currency_code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">
                        Prices across your dashboard will be shown in this currency.
                    </div>
                </div>

                <!-- Live rate preview -->
                <div class="p-3 rounded mb-3" style="background:#f8f9fa;">
                    <p class="mb-1 small fw-semibold text-muted">
                        Current Exchange Rates (base: <?php echo $currencyManager->getBaseCurrency(); ?>)
                    </p>
                    <?php foreach ($allCurrencies as $cur): ?>
                        <div class="d-flex justify-content-between small
                            <?php echo $cur['currency_code'] === $currentCode ? 'fw-bold text-success' : 'text-muted'; ?>">
                            <span><?php echo $cur['currency_code']; ?> — <?php echo $cur['currency_name']; ?></span>
                            <span><?php echo $cur['currency_symbol'] . number_format($cur['exchange_rate'], 4); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-success px-4">
                    <i class="fas fa-save me-1"></i> Save Currency
                </button>
            </form>
        </div>
    </div>

    <!-- Notification Preferences -->
    <div class="col-lg-6 mb-4">
        <div class="stat-card h-100">
            <h6 class="fw-bold mb-4">
                <i class="fas fa-bell me-2 text-success"></i>Notification Preferences
            </h6>
            <form method="POST">
                <input type="hidden" name="action" value="update_notifications">

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notifyOrders"
                           name="notify_orders" <?php echo $notifyOrders ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="notifyOrders">
                        <span class="fw-semibold">New Order Alerts</span><br>
                        <small class="text-muted">Get notified when a customer places an order for your products.</small>
                    </label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notifyLowStock"
                           name="notify_low_stock" <?php echo $notifyLowStock ? 'checked' : ''; ?>
                           onchange="toggleThreshold(this.checked)">
                    <label class="form-check-label" for="notifyLowStock">
                        <span class="fw-semibold">Low Stock Alerts</span><br>
                        <small class="text-muted">Get notified when a product's stock falls below a threshold.</small>
                    </label>
                </div>

                <div id="thresholdWrapper" class="ms-4 mb-4"
                     style="<?php echo $notifyLowStock ? '' : 'display:none;'; ?>">
                    <label class="form-label fw-semibold small">Low Stock Threshold (units)</label>
                    <input type="number" name="low_stock_threshold" class="form-control form-control-sm w-50"
                           min="1" max="999"
                           value="<?php echo intval($lowStockQty); ?>">
                    <div class="form-text">Alert when stock drops to or below this number.</div>
                </div>

                <button type="submit" class="btn btn-success px-4">
                    <i class="fas fa-save me-1"></i> Save Preferences
                </button>
            </form>
        </div>
    </div>

    <!-- Account Info (read-only) -->
    <div class="col-lg-6 mb-4">
        <div class="stat-card h-100">
            <h6 class="fw-bold mb-4">
                <i class="fas fa-info-circle me-2 text-success"></i>Account Information
            </h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td class="text-muted" style="width:45%;">Username</td>
                    <td class="fw-semibold"><?php echo htmlspecialchars($currentUser['username'] ?? '—'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Email</td>
                    <td class="fw-semibold"><?php echo htmlspecialchars($currentUser['email'] ?? '—'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Role</td>
                    <td><span class="badge bg-success">Producer</span></td>
                </tr>
                <tr>
                    <td class="text-muted">Loyalty Points</td>
                    <td class="fw-semibold text-warning">
                        <i class="fas fa-star me-1"></i>
                        <?php echo number_format($currentUser['loyalty_points'] ?? 0); ?> pts
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Preferred Currency</td>
                    <td class="fw-semibold"><?php echo htmlspecialchars($currentCode); ?></td>
                </tr>
            </table>
            <a href="profile.php" class="btn btn-outline-success btn-sm">
                <i class="fas fa-user-edit me-1"></i> Edit Profile
            </a>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="col-lg-6 mb-4">
        <div class="stat-card h-100 border border-danger">
            <h6 class="fw-bold mb-4 text-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
            </h6>
            <p class="small text-muted mb-3">
                These actions are permanent and cannot be undone.
                Please be absolutely sure before proceeding.
            </p>
            <button class="btn btn-outline-danger btn-sm mb-2 w-100" onclick="confirmDeactivate()">
                <i class="fas fa-user-slash me-1"></i> Deactivate My Account
            </button>
            <button class="btn btn-outline-secondary btn-sm w-100" onclick="confirmLogoutAll()">
                <i class="fas fa-sign-out-alt me-1"></i> Log Out of All Sessions
            </button>
        </div>
    </div>

</div>

<script>
function toggleThreshold(checked) {
    document.getElementById('thresholdWrapper').style.display = checked ? 'block' : 'none';
}

function confirmDeactivate() {
    Swal.fire({
        title: 'Deactivate Account?',
        html: 'Your products will be hidden and you will be logged out.<br><br>Contact support to reactivate.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, deactivate'
    }).then(result => {
        if (result.isConfirmed) {
            $.post('ajax/deactivate-account.php', { user_id: <?php echo intval($_SESSION['user_id']); ?> }, function(res) {
                if (res.success) {
                    window.location.href = '../../public/logout.php';
                } else {
                    Swal.fire('Error', 'Could not deactivate account. Please contact support.', 'error');
                }
            }, 'json');
        }
    });
}

function confirmLogoutAll() {
    Swal.fire({
        title: 'Log out everywhere?',
        text: 'You will be signed out of all active sessions.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1a472a',
        confirmButtonText: 'Yes, log me out'
    }).then(result => {
        if (result.isConfirmed) {
            window.location.href = '../../public/logout.php';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>