<?php
require_once 'includes/header.php';

$conn = Database::getInstance()->getConnection();

// Get product statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_products,
        COALESCE(SUM(stock_quantity), 0) as total_stock
    FROM products 
    WHERE producer_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$productStats = $stmt->fetch();

// Get order statistics — use subquery to avoid inflated counts from JOIN
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'pending'    THEN o.id END) as pending_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'processing' THEN o.id END) as processing_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'completed'  THEN o.id END) as completed_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'cancelled'  THEN o.id END) as cancelled_orders,
        COALESCE(SUM(
            CASE WHEN o.status = 'completed' 
            THEN (
                SELECT COALESCE(SUM(oi2.quantity * oi2.price_at_time), 0)
                FROM order_items oi2
                JOIN products p2 ON oi2.product_id = p2.id
                WHERE oi2.order_id = o.id AND p2.producer_id = ?
            )
            ELSE 0 END
        ), 0) as total_revenue
    FROM orders o
    WHERE EXISTS (
        SELECT 1 FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = o.id AND p.producer_id = ?
    )
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$orderStats = $stmt->fetch();

// Get recent orders
$stmt = $conn->prepare("
    SELECT DISTINCT o.*, u.full_name as customer_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.user_id = u.id
    WHERE p.producer_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recentOrders = $stmt->fetchAll();

// Get monthly sales data for chart - FIXED QUERY
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(o.created_at, '%M') as month,
        COALESCE(SUM(oi.quantity * oi.price_at_time), 0) as total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.status = 'completed'
    AND p.producer_id = ?
    GROUP BY MONTH(o.created_at), DATE_FORMAT(o.created_at, '%M')
    ORDER BY MIN(o.created_at) ASC
    LIMIT 6
");
$stmt->execute([$_SESSION['user_id']]);
$monthlySales = $stmt->fetchAll();

$months = [];
$sales  = [];
foreach ($monthlySales as $data) {
    $months[] = $data['month'];
    $sales[]  = (float)$data['total'];
}
?>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo number_format($productStats['total_products'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Total Products</p>
                </div>
                <i class="fas fa-box fa-2x text-success"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo number_format($productStats['total_stock'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Total Stock</p>
                </div>
                <i class="fas fa-cubes fa-2x text-info"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><?php echo number_format($orderStats['total_orders'] ?? 0); ?></h3>
                    <p class="text-muted mb-0">Total Orders</p>
                </div>
                <i class="fas fa-shopping-cart fa-2x text-warning"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>
                        <?php echo ($currentCurrency['currency_symbol'] ?? '$') . number_format($orderStats['total_revenue'] ?? 0, 2); ?>
                    </h3>
                    <p class="text-muted mb-0">Total Revenue</p>
                </div>
                <i class="fas fa-dollar-sign fa-2x text-success"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mt-4">
    <div class="col-md-8">
        <div class="table-container">
            <h5><i class="fas fa-chart-line"></i> Sales Overview</h5>
            <canvas id="salesChart" height="300"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="table-container">
            <h5><i class="fas fa-chart-pie"></i> Order Status</h5>
            <canvas id="statusChart" height="250"></canvas>
            <div class="mt-3 text-center">
                <p><span class="badge badge-pending">Pending: <?php echo number_format($orderStats['pending_orders'] ?? 0); ?></span></p>
                <p><span class="badge badge-processing">Processing: <?php echo number_format($orderStats['processing_orders'] ?? 0); ?></span></p>
                <p><span class="badge badge-completed">Completed: <?php echo number_format($orderStats['completed_orders'] ?? 0); ?></span></p>
                <p><span class="badge badge-cancelled">Cancelled: <?php echo number_format($orderStats['cancelled_orders'] ?? 0); ?></span></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row mt-4">
    <div class="col-12">
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
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo ($currentCurrency['currency_symbol'] ?? '$') . number_format($order['total_amount_display'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info view-order" data-id="<?php echo $order['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Revenue (<?php echo addslashes($currentCurrency['currency_symbol'] ?? '$'); ?>)',
            data: <?php echo json_encode($sales); ?>,
            borderColor: '#4caf50',
            backgroundColor: 'rgba(76,175,80,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
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
                <?php echo intval($orderStats['pending_orders']    ?? 0); ?>,
                <?php echo intval($orderStats['processing_orders'] ?? 0); ?>,
                <?php echo intval($orderStats['completed_orders']  ?? 0); ?>,
                <?php echo intval($orderStats['cancelled_orders']  ?? 0); ?>
            ],
            backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } }
    }
});

// View order button handler
$('.view-order').click(function() {
    window.location.href = 'orders.php?view=' + $(this).data('id');
});
</script>

<?php require_once 'includes/footer.php'; ?>