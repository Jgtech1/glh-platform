<?php
require_once 'includes/header.php';

// Get all orders
$stmt = $conn->prepare("
    SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->fetchAll();

// Get single order if view parameter exists
$viewOrder = null;
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("
        SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$_GET['view']]);
    $viewOrder = $stmt->fetch();
}
?>

<div class="table-container">
    <?php if ($viewOrder): ?>
        <!-- Order Details View -->
        <div class="mb-3">
            <a href="orders.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6>Order Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Order #:</strong> <?php echo str_pad($viewOrder['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($viewOrder['created_at'])); ?></p>
                        <p><strong>Status:</strong> 
                            <select id="orderStatus" class="form-select" style="width: auto; display: inline-block;">
                                <option value="pending" <?php echo $viewOrder['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $viewOrder['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $viewOrder['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $viewOrder['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button class="btn btn-sm btn-primary" onclick="updateStatus(<?php echo $viewOrder['id']; ?>)">Update</button>
                        </p>
                        <p><strong>Payment:</strong> <?php echo ucfirst($viewOrder['payment_status']); ?></p>
                        <p><strong>Delivery Type:</strong> <?php echo ucfirst($viewOrder['delivery_type']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6>Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($viewOrder['customer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($viewOrder['customer_email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($viewOrder['customer_phone'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($viewOrder['delivery_address'] ?? 'N/A')); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6>Order Items</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $orderItems = $orderObj->getOrderItems($viewOrder['id']);
                            foreach ($orderItems as $item):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>$<?php echo number_format($item['price_at_time_display'], 2); ?></td>
                                <td>$<?php echo number_format($item['price_at_time_display'] * $item['quantity'], 2); ?></td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><th colspan="3" class="text-end">Total:</th><th>$<?php echo number_format($viewOrder['total_amount_display'], 2); ?></th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        function updateStatus(orderId) {
            const status = $('#orderStatus').val();
            $.ajax({
                url: 'ajax/update-order-status.php',
                method: 'POST',
                data: {order_id: orderId, status: status},
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', 'Order status updated!', 'success');
                    } else {
                        Swal.fire('Error!', 'Failed to update status', 'error');
                    }
                }
            });
        }
        </script>
        
    <?php else: ?>
        <!-- Orders List View -->
        <h5><i class="fas fa-shopping-cart"></i> All Orders</h5>
        
        <div class="table-responsive">
            <table id="ordersTable" class="table table-striped">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td>$<?php echo number_format($order['total_amount_display'], 2); ?></td>
                        <td><span class="badge badge-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                        <td><span class="badge bg-info"><?php echo ucfirst($order['payment_status']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td>
                            <a href="?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                         </td>
                     </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    if ($('#ordersTable').length) {
        $('#ordersTable').DataTable({
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>