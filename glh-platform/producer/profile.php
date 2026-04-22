<?php
require_once 'includes/header.php';

// Always re-fetch fresh user data from DB (not just what header.php loaded)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

$errors  = [];
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email']     ?? '');
        $phone    = trim($_POST['phone']     ?? '');
        $address  = trim($_POST['address']   ?? '');

        if (empty($fullName)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        // Check email not taken by another user
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $errors[] = 'That email address is already in use by another account.';
            }
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("
                UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?
                WHERE id = ?
            ");
            $stmt->execute([$fullName, $email, $phone, $address, $_SESSION['user_id']]);

            $_SESSION['full_name'] = $fullName;
            $success = 'Profile updated successfully.';

            // Re-fetch updated data so the form and card reflect changes immediately
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $currentUser = $stmt->fetch();
        }
    }

    if ($_POST['action'] === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password']     ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }

        if (empty($errors)) {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $_SESSION['user_id']]);
            $success = 'Password changed successfully.';
        }
    }
}

// Producer stats for the summary card
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE producer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalProducts = $stmt->fetch()['total'];

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT o.id) as total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.producer_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$totalOrders = $stmt->fetch()['total'];

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(o.total_amount_display), 0) as revenue
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.producer_id = ? AND o.status = 'completed'
");
$stmt->execute([$_SESSION['user_id']]);
$totalRevenue = $stmt->fetch()['revenue'];

$stmt = $conn->prepare("SELECT created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$memberSince = $stmt->fetch()['created_at'];
?>

<div class="row">
    <!-- Left: Profile Summary Card -->
    <div class="col-lg-4 mb-4">
        <div class="stat-card text-center">
            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                 style="width:90px;height:90px;border-radius:50%;
                        background:linear-gradient(135deg,#4caf50,#2e7d32);
                        font-size:2.2rem;color:white;font-weight:700;">
                <?php echo strtoupper(substr($currentUser['full_name'] ?? 'P', 0, 1)); ?>
            </div>

            <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?></h5>
            <p class="text-muted small mb-1">@<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?></p>
            <span class="badge bg-success mb-3">Producer</span>

            <hr>

            <div class="row text-center g-0">
                <div class="col-4">
                    <div class="fw-bold fs-5"><?php echo number_format($totalProducts); ?></div>
                    <div class="text-muted" style="font-size:.75rem;">Products</div>
                </div>
                <div class="col-4 border-start border-end">
                    <div class="fw-bold fs-5"><?php echo number_format($totalOrders); ?></div>
                    <div class="text-muted" style="font-size:.75rem;">Orders</div>
                </div>
                <div class="col-4">
                    <div class="fw-bold fs-5">
                        <?php echo ($currentCurrency['currency_symbol'] ?? '$') . number_format($totalRevenue, 0); ?>
                    </div>
                    <div class="text-muted" style="font-size:.75rem;">Revenue</div>
                </div>
            </div>

            <hr>

            <p class="text-muted small mb-1">
                <i class="fas fa-envelope me-1"></i>
                <?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>
            </p>
            <?php if (!empty($currentUser['phone'])): ?>
                <p class="text-muted small mb-1">
                    <i class="fas fa-phone me-1"></i>
                    <?php echo htmlspecialchars($currentUser['phone']); ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($currentUser['address'])): ?>
                <p class="text-muted small mb-0">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    <?php echo htmlspecialchars($currentUser['address']); ?>
                </p>
            <?php endif; ?>

            <hr>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar me-1"></i>
                Member since <?php echo date('F Y', strtotime($memberSince)); ?>
            </p>
        </div>
    </div>

    <!-- Right: Edit Forms -->
    <div class="col-lg-8">

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

        <!-- Profile Info Form -->
        <div class="stat-card mb-4">
            <h6 class="fw-bold mb-4">
                <i class="fas fa-user-edit me-2 text-success"></i>Personal Information
            </h6>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" class="form-control"
                               value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>" disabled>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Address / Farm Location</label>
                    <textarea name="address" class="form-control" rows="3"
                    ><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-success px-4">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="stat-card">
            <h6 class="fw-bold mb-4">
                <i class="fas fa-lock me-2 text-success"></i>Change Password
            </h6>
            <form method="POST" id="passwordForm">
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Current Password</label>
                    <div class="input-group">
                        <input type="password" name="current_password" id="currentPass" class="form-control" required>
                        <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="currentPass">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="newPass"
                                   class="form-control" minlength="6" required>
                            <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="newPass">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirmPass"
                                   class="form-control" minlength="6" required>
                            <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="confirmPass">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Password strength bar -->
                <div class="mb-3">
                    <div class="progress" style="height:6px;">
                        <div id="strengthBar" class="progress-bar" style="width:0%;transition:width .3s;"></div>
                    </div>
                    <small id="strengthLabel" class="text-muted"></small>
                </div>

                <button type="submit" class="btn btn-warning px-4">
                    <i class="fas fa-key me-1"></i> Change Password
                </button>
            </form>
        </div>

    </div>
</div>

<script>
// Toggle password visibility
document.querySelectorAll('.toggle-pass').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        const icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

// Password strength indicator
document.getElementById('newPass').addEventListener('input', function () {
    const val = this.value;
    let strength = 0;
    if (val.length >= 6)                         strength++;
    if (val.length >= 10)                        strength++;
    if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
    if (/[0-9]/.test(val))                       strength++;
    if (/[^A-Za-z0-9]/.test(val))               strength++;

    const bar   = document.getElementById('strengthBar');
    const label = document.getElementById('strengthLabel');
    const pct   = (strength / 5) * 100;
    bar.style.width = pct + '%';

    if (strength <= 1) {
        bar.className   = 'progress-bar bg-danger';
        label.textContent = 'Weak';
        label.className = 'text-danger small';
    } else if (strength <= 3) {
        bar.className   = 'progress-bar bg-warning';
        label.textContent = 'Fair';
        label.className = 'text-warning small';
    } else {
        bar.className   = 'progress-bar bg-success';
        label.textContent = 'Strong';
        label.className = 'text-success small';
    }
});

// Client-side confirm password match
document.getElementById('passwordForm').addEventListener('submit', function (e) {
    const np = document.getElementById('newPass').value;
    const cp = document.getElementById('confirmPass').value;
    if (np !== cp) {
        e.preventDefault();
        Swal.fire('Mismatch', 'New passwords do not match.', 'error');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>