<?php
require_once 'includes/header.php';

// Helper function to safely escape HTML with null handling
function safeHtml($value, $default = '') {
    // Use null coalescing operator to handle null values
    $value = $value ?? $default;
    return htmlspecialchars($value);
}

// Get user ID (either current or specified)
$userId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];
$isOwnProfile = ($userId == $_SESSION['user_id']);

// Get user data - MODIFIED to ensure all fields are fetched
$stmt = $conn->prepare("SELECT id, full_name, username, email, phone, address, role, loyalty_points, created_at, password FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profileUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profileUser) {
    header('Location: users.php');
    exit();
}

// DEBUG: Check if phone and address are being fetched (remove after testing)
// Uncomment the line below to see what's being fetched
// echo "<!-- Phone: " . var_export($profileUser['phone'], true) . " | Address: " . var_export($profileUser['address'], true) . " -->";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile']) && $isOwnProfile) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
    if ($stmt->execute([$full_name, $email, $phone, $address, $userId])) {
        $_SESSION['full_name'] = $full_name;
        // Refresh user data after update
        $stmt = $conn->prepare("SELECT id, full_name, username, email, phone, address, role, loyalty_points, created_at, password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $profileUser = $stmt->fetch(PDO::FETCH_ASSOC);
        echo '<script>Swal.fire("Success!", "Profile updated successfully!", "success");</script>';
    } else {
        echo '<script>Swal.fire("Error!", "Failed to update profile!", "error");</script>';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password']) && $isOwnProfile) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    if (password_verify($current_password, $profileUser['password'])) {
        if ($new_password === $confirm_password && strlen($new_password) >= 6) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $userId])) {
                echo '<script>Swal.fire("Success!", "Password changed successfully!", "success");</script>';
            }
        } else {
            echo '<script>Swal.fire("Error!", "Passwords do not match or too short!", "error");</script>';
        }
    } else {
        echo '<script>Swal.fire("Error!", "Current password is incorrect!", "error");</script>';
    }
}

// Get user statistics
$stmt = $conn->prepare("
    SELECT COUNT(*) as order_count, COALESCE(SUM(total_amount_display), 0) as total_spent
    FROM orders WHERE user_id = ? AND status = 'completed'
");
$stmt->execute([$userId]);
$userStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent orders
$stmt = $conn->prepare("
    SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5
");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products if producer
$products = [];
if ($profileUser['role'] == 'producer') {
    $stmt = $conn->prepare("SELECT * FROM products WHERE producer_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="table-container text-center">
            <div class="user-avatar" style="width: 100px; height: 100px; font-size: 3rem; margin: 0 auto 20px;">
                <?php echo safeHtml(strtoupper(substr($profileUser['full_name'] ?? '', 0, 1))); ?>
            </div>
            <h4><?php echo safeHtml($profileUser['full_name'] ?? ''); ?></h4>
            <p class="text-muted">
                <i class="fas fa-envelope"></i> <?php echo safeHtml($profileUser['email'] ?? ''); ?><br>
                <i class="fas fa-phone"></i> <?php 
                    // Check if phone exists and is not empty
                    $phoneValue = isset($profileUser['phone']) && !empty($profileUser['phone']) 
                        ? $profileUser['phone'] 
                        : 'Not provided';
                    echo safeHtml($phoneValue);
                ?><br>
                <i class="fas fa-tag"></i> Role: <span class="badge bg-info"><?php echo safeHtml(ucfirst($profileUser['role'] ?? '')); ?></span>
            </p>
            <p><i class="fas fa-calendar"></i> Member since: <?php echo date('F j, Y', strtotime($profileUser['created_at'] ?? 'now')); ?></p>
        </div>
        
        <?php if (($profileUser['role'] ?? '') == 'customer'): ?>
        <div class="table-container mt-4">
            <h5><i class="fas fa-star"></i> Loyalty Program</h5>
            <div class="text-center">
                <h2 class="text-success"><?php echo (int)($profileUser['loyalty_points'] ?? 0); ?></h2>
                <p>Loyalty Points</p>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: <?php echo min(100, (($profileUser['loyalty_points'] ?? 0) % 100)); ?>%"></div>
                </div>
                <small class="text-muted">100 points = $10 discount</small>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <?php if ($isOwnProfile): ?>
        <!-- Edit Profile Form -->
        <div class="table-container">
            <h5><i class="fas fa-edit"></i> Edit Profile</h5>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo safeHtml($profileUser['full_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo safeHtml($profileUser['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo safeHtml($profileUser['phone'] ?? ''); ?>">
                            <small class="text-muted">Current: <?php echo !empty($profileUser['phone']) ? $profileUser['phone'] : 'Not set'; ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" class="form-control" value="<?php echo safeHtml($profileUser['username'] ?? ''); ?>" disabled>
                            <small class="text-muted">Username cannot be changed</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2"><?php echo safeHtml($profileUser['address'] ?? ''); ?></textarea>
                            <small class="text-muted">Current: <?php echo !empty($profileUser['address']) ? $profileUser['address'] : 'Not set'; ?></small>
                        </div>
                    </div>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="table-container mt-4">
            <h5><i class="fas fa-lock"></i> Change Password</h5>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-warning">Change Password</button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="table-container mt-4">
            <h5><i class="fas fa-chart-line"></i> Account Statistics</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded">
                        <h3><?php echo (int)($userStats['order_count'] ?? 0); ?></h3>
                        <p class="text-muted">Total Orders</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded">
                        <h3>$<?php echo number_format($userStats['total_spent'] ?? 0, 2); ?></h3>
                        <p class="text-muted">Total Spent</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3 border rounded">
                        <h3><?php echo (int)($profileUser['loyalty_points'] ?? 0); ?></h3>
                        <p class="text-muted">Loyalty Points</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="table-container mt-4">
            <h5><i class="fas fa-clock"></i> Recent Orders</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentOrders)): ?>
                            <tr><td colspan="4" class="text-center">No orders yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>$<?php echo number_format($order['total_amount_display'], 2); ?></td>
                                <td><span class="badge badge-<?php echo safeHtml($order['status'] ?? ''); ?>"><?php echo safeHtml(ucfirst($order['status'] ?? '')); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Products if Producer -->
        <?php if (($profileUser['role'] ?? '') == 'producer' && !empty($products)): ?>
        <div class="table-container mt-4">
            <h5><i class="fas fa-box"></i> Recent Products</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo safeHtml($product['name'] ?? ''); ?></td>
                            <td>$<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                            <td><?php echo (int)($product['stock_quantity'] ?? 0); ?></td>
                            <td><span class="badge <?php echo ($product['status'] ?? '') == 'active' ? 'bg-success' : 'bg-secondary'; ?>"><?php echo safeHtml(ucfirst($product['status'] ?? 'inactive')); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>