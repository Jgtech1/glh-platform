<?php
require_once 'includes/header.php';

// Get statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as total_customers,
        SUM(CASE WHEN role = 'producer' THEN 1 ELSE 0 END) as total_producers
    FROM users
");
$stmt->execute();
$userStats = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_products,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
        SUM(stock_quantity) as total_stock
    FROM products
");
$stmt->execute();
$productStats = $stmt->fetch();

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(total_amount_display) as total_revenue
    FROM orders
");
$stmt->execute();
$orderStats = $stmt->fetch();

// Get monthly revenue for chart
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%M') as month,
        SUM(total_amount_display) as revenue
    FROM orders
    WHERE status = 'completed'
    GROUP BY MONTH(created_at)
    ORDER BY created_at ASC
    LIMIT 6
");
$stmt->execute();
$monthlyRevenue = $stmt->fetchAll();

$months = [];
$revenues = [];
foreach ($monthlyRevenue as $data) {
    $months[] = $data['month'];
    $revenues[] = $data['revenue'];
}

// Get recent orders
$stmt = $conn->prepare("
    SELECT o.*, u.full_name as customer_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recentOrders = $stmt->fetchAll();

// Get recent users
$stmt = $conn->prepare("
    SELECT * FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute();
$recentUsers = $stmt->fetchAll();
?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info text-end">
                    <h3><?php echo number_format($userStats['total_users']); ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="mt-3">
                <small><i class="fas fa-user-check"></i> <?php echo $userStats['total_customers']; ?> Customers | <?php echo $userStats['total_producers']; ?> Producers</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: #2a9d8f;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-icon" style="color: #2a9d8f; background: rgba(42,157,143,0.1);">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info text-end">
                    <h3><?php echo number_format($productStats['total_products']); ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            <div class="mt-3">
                <small><i class="fas fa-check-circle"></i> <?php echo $productStats['active_products']; ?> Active</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: #f4a261;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-icon" style="color: #f4a261; background: rgba(244,162,97,0.1);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info text-end">
                    <h3><?php echo number_format($orderStats['total_orders']); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="mt-3">
                <small><i class="fas fa-clock"></i> <?php echo $orderStats['pending_orders']; ?> Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: #e63946;">
            <div class="d-flex justify-content-between align-items-center">
                <div class="stat-icon" style="color: #e63946; background: rgba(230,57,70,0.1);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info text-end">
                    <h3>$<?php echo number_format($orderStats['total_revenue'] ?? 0, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="table-container">
            <h5><i class="fas fa-chart-line"></i> Revenue Overview (Last 6 Months)</h5>
            <canvas id="revenueChart" height="300"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <h5><i class="fas fa-chart-pie"></i> Order Status Distribution</h5>
            <canvas id="statusChart" height="250"></canvas>
            <div class="mt-3 text-center">
                <p><span class="badge badge-pending">Pending: <?php echo $orderStats['pending_orders']; ?></span></p>
                <p><span class="badge badge-completed">Completed: <?php echo $orderStats['completed_orders']; ?></span></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders & Users -->
<div class="row mt-4">
    <div class="col-md-7">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-clock"></i> Recent Orders</h5>
                <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td>$<?php echo number_format($order['total_amount_display'], 2); ?></td>
                            <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info view-order" data-id="<?php echo $order['id']; ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-user-plus"></i> New Users</h5>
                <a href="users.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>User</th><th>Role</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['full_name']); ?><br><small><?php echo $user['email']; ?></small></td>
                            <td><span class="badge bg-info"><?php echo ucfirst($user['role']); ?></span></td>
                            <td><?php echo date('M d', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Revenue ($)',
            data: <?php echo json_encode($revenues); ?>,
            borderColor: '#2c7da0',
            backgroundColor: 'rgba(44,125,160,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' }
        }
    }
});

// Status Chart
const ctx2 = document.getElementById('statusChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Processing', 'Completed', 'Cancelled'],
        datasets: [{
            data: [
                <?php echo $orderStats['pending_orders']; ?>,
                <?php echo $orderStats['processing_orders'] ?? 0; ?>,
                <?php echo $orderStats['completed_orders']; ?>,
                <?php echo $orderStats['cancelled_orders'] ?? 0; ?>
            ],
            backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545']
        }]
    }
});

// View order details
$('.view-order').click(function() {
    const orderId = $(this).data('id');
    window.location.href = 'orders.php?view=' + orderId;
});
</script>

<?php require_once 'includes/footer.php'; ?>