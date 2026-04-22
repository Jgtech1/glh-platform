<?php
require_once 'includes/header.php';

// Get date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Sales Report - Using correct column names from your schema
$stmt = $conn->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as order_count,
        SUM(total_amount) as daily_revenue
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$stmt->execute([$start_date, $end_date]);
$dailySales = $stmt->fetchAll();

// Top Products - Using price_at_time (correct column name)
$stmt = $conn->prepare("
    SELECT 
        p.name,
        SUM(oi.quantity) as total_sold,
        SUM(oi.quantity * oi.price_at_time) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
");
$stmt->execute();
$topProducts = $stmt->fetchAll();

// Top Customers
$stmt = $conn->prepare("
    SELECT 
        u.full_name,
        u.email,
        COUNT(o.id) as order_count,
        SUM(o.total_amount) as total_spent
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.status = 'completed'
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 10
");
$stmt->execute();
$topCustomers = $stmt->fetchAll();

// Category Performance - Using price_at_time
$stmt = $conn->prepare("
    SELECT 
        COALESCE(p.category, 'Uncategorized') as category_name,
        SUM(oi.quantity) as items_sold,
        SUM(oi.quantity * oi.price_at_time) as revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed'
    GROUP BY p.category
    ORDER BY revenue DESC
");
$stmt->execute();
$categoryPerformance = $stmt->fetchAll();

// Monthly Summary
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue,
        AVG(total_amount) as avg_order_value
    FROM orders
    WHERE status = 'completed'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute();
$monthlySummary = $stmt->fetchAll();

// User Growth
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_users,
        SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as new_customers,
        SUM(CASE WHEN role = 'producer' THEN 1 ELSE 0 END) as new_producers
    FROM users
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute();
$userGrowth = $stmt->fetchAll();

// Calculate totals
$totalRevenue = array_sum(array_column($dailySales, 'daily_revenue'));
$totalOrders = array_sum(array_column($dailySales, 'order_count'));
$avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
?>

<!-- Date Filter -->
<div class="table-container mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
        </div>
        <div class="col-md-4">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="stat-card">
            <h3>$<?php echo number_format($totalRevenue, 2); ?></h3>
            <p class="text-muted">Total Revenue</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h3><?php echo $totalOrders; ?></h3>
            <p class="text-muted">Total Orders</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h3>$<?php echo number_format($avgOrderValue, 2); ?></h3>
            <p class="text-muted">Average Order Value</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <h3><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></h3>
            <p class="text-muted">Period</p>
        </div>
    </div>
</div>

<!-- Daily Sales Chart -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="table-container">
            <h5><i class="fas fa-chart-line"></i> Daily Sales</h5>
            <canvas id="salesChart" height="300"></canvas>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Top Products -->
    <div class="col-md-6">
        <div class="table-container">
            <h5><i class="fas fa-trophy"></i> Top Selling Products</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topProducts)): ?>
                            <tr><td colspan="3" class="text-center">No data available</td></tr>
                        <?php else: ?>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['total_sold']; ?></td>
                                <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Top Customers -->
    <div class="col-md-6">
        <div class="table-container">
            <h5><i class="fas fa-crown"></i> Top Customers</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topCustomers)): ?>
                            <tr><td colspan="3" class="text-center">No data available</td></tr>
                        <?php else: ?>
                            <?php foreach ($topCustomers as $customer): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($customer['full_name']); ?><br>
                                    <small class="text-muted"><?php echo $customer['email']; ?></small>
                                </td>
                                <td><?php echo $customer['order_count']; ?></td>
                                <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Category Performance & Monthly Summary -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="table-container">
            <h5><i class="fas fa-chart-pie"></i> Category Performance</h5>
            <canvas id="categoryChart" height="250"></canvas>
            <div class="table-responsive mt-3">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Items Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categoryPerformance)): ?>
                            <tr><td colspan="3" class="text-center">No data available</td></tr>
                        <?php else: ?>
                            <?php foreach ($categoryPerformance as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                <td><?php echo $category['items_sold'] ?? 0; ?></td>
                                <td>$<?php echo number_format($category['revenue'] ?? 0, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="table-container">
            <h5><i class="fas fa-chart-line"></i> Monthly Summary</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Avg Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthlySummary)): ?>
                            <tr><td colspan="4" class="text-center">No data available</td></tr>
                        <?php else: ?>
                            <?php foreach ($monthlySummary as $month): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                <td><?php echo $month['order_count']; ?></td>
                                <td>$<?php echo number_format($month['revenue'], 2); ?></td>
                                <td>$<?php echo number_format($month['avg_order_value'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User Growth -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="table-container">
            <h5><i class="fas fa-users"></i> User Growth</h5>
            <canvas id="userGrowthChart" height="300"></canvas>
            <div class="table-responsive mt-3">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>New Customers</th>
                            <th>New Producers</th>
                            <th>Total New Users</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($userGrowth)): ?>
                            <tr><td colspan="4" class="text-center">No data available</td></tr>
                        <?php else: ?>
                            <?php foreach (array_reverse($userGrowth) as $growth): ?>
                            <tr>
                                <td><?php echo date('F Y', strtotime($growth['month'] . '-01')); ?></td>
                                <td><?php echo $growth['new_customers']; ?></td>
                                <td><?php echo $growth['new_producers']; ?></td>
                                <td><?php echo $growth['new_users']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Daily Sales Chart
const dailySalesData = <?php echo json_encode($dailySales); ?>;

if (dailySalesData.length > 0) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailySalesData.map(item => item.date),
            datasets: [{
                label: 'Daily Revenue ($)',
                data: dailySalesData.map(item => parseFloat(item.daily_revenue)),
                borderColor: '#2c7da0',
                backgroundColor: 'rgba(44,125,160,0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Order Count',
                data: dailySalesData.map(item => parseInt(item.order_count)),
                borderColor: '#f4a261',
                backgroundColor: 'rgba(244,162,97,0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { 
                    title: { display: true, text: 'Revenue ($)' },
                    beginAtZero: true
                },
                y1: { 
                    position: 'right', 
                    title: { display: true, text: 'Order Count' },
                    beginAtZero: true
                }
            }
        }
    });
} else {
    document.getElementById('salesChart').parentElement.innerHTML = '<div class="text-center p-5">No sales data available for the selected period</div>';
}

// Category Chart
const categoryData = <?php echo json_encode($categoryPerformance); ?>;
if (categoryData.length > 0) {
    const ctx2 = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: categoryData.map(item => item.category_name),
            datasets: [{
                data: categoryData.map(item => parseFloat(item.revenue)),
                backgroundColor: ['#2c7da0', '#61a5c2', '#2a9d8f', '#f4a261', '#e63946', '#219ebc', '#8338ec', '#ff006e']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
} else {
    document.getElementById('categoryChart').parentElement.innerHTML = '<div class="text-center p-5">No category data available</div>';
}

// User Growth Chart
const userGrowthData = <?php echo json_encode(array_reverse($userGrowth)); ?>;
if (userGrowthData.length > 0) {
    const ctx3 = document.getElementById('userGrowthChart').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: userGrowthData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleString('default', { month: 'short', year: 'numeric' });
            }),
            datasets: [
                {
                    label: 'New Customers',
                    data: userGrowthData.map(item => parseInt(item.new_customers)),
                    backgroundColor: '#2c7da0'
                },
                {
                    label: 'New Producers',
                    data: userGrowthData.map(item => parseInt(item.new_producers)),
                    backgroundColor: '#f4a261'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { 
                x: { stacked: true },
                y: { 
                    stacked: true, 
                    title: { display: true, text: 'New Users' },
                    beginAtZero: true
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw;
                        }
                    }
                }
            }
        }
    });
} else {
    document.getElementById('userGrowthChart').parentElement.innerHTML = '<div class="text-center p-5">No user growth data available</div>';
}
</script>

<?php require_once 'includes/footer.php'; ?>