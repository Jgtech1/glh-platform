<?php
require_once 'includes/header.php';

$conn = Database::getInstance()->getConnection();

// Get orders for this producer
$stmt = $conn->prepare("
    SELECT DISTINCT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.user_id = u.id
    WHERE p.producer_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Get single order if view parameter exists
$viewOrder = null;
if (isset($_GET['view'])) {
    $orderId = intval($_GET['view']);
    $stmt = $conn->prepare("
        SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND EXISTS (
            SELECT 1 FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = o.id AND p.producer_id = ?
        )
    ");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $viewOrder = $stmt->fetch();
}
?>

<div class="table-container">
    <h5><i class="fas fa-shopping-cart"></i> Orders</h5>

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
                        <h6 class="mb-0">Order Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Order #:</strong> <?php echo str_pad($viewOrder['id'], 6, '0', STR_PAD_LEFT); ?></p>
                        <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($viewOrder['created_at'])); ?></p>
                        <p>
                            <strong>Status:</strong>
                            <select id="orderStatus" class="form-select d-inline-block w-auto ms-1">
                                <option value="pending"    <?php echo $viewOrder['status'] === 'pending'    ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $viewOrder['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed"  <?php echo $viewOrder['status'] === 'completed'  ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled"  <?php echo $viewOrder['status'] === 'cancelled'  ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button class="btn btn-sm btn-primary ms-1"
                                    onclick="updateStatus(<?php echo intval($viewOrder['id']); ?>)">
                                Update
                            </button>
                        </p>
                        <p><strong>Payment:</strong> <?php echo ucfirst($viewOrder['payment_status'] ?? 'N/A'); ?></p>
                        <?php if (!empty($viewOrder['order_notes'])): ?>
                            <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($viewOrder['order_notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong>    <?php echo htmlspecialchars($viewOrder['customer_name']); ?></p>
                        <p><strong>Email:</strong>   <?php echo htmlspecialchars($viewOrder['customer_email']); ?></p>
                        <p><strong>Phone:</strong>   <?php echo htmlspecialchars($viewOrder['customer_phone'] ?? 'N/A'); ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($viewOrder['delivery_address'] ?? 'N/A')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Order Items</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Unit</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $orderItems = $orderObj->getOrderItems($viewOrder['id']);
                            if (empty($orderItems)):
                            ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No items found for this order.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orderItems as $item): 
                                    $unitPrice = $item['price_at_time_display'] ?? $item['price_at_time'] ?? 0;
                                    $subtotal  = $unitPrice * $item['quantity'];
                                    $symbol    = $currentCurrency['currency_symbol'] ?? '$';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($item['image_url'])): ?>
                                                <img src="../../public/<?php echo htmlspecialchars($item['image_url']); ?>"
                                                     style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? '—'); ?></td>
                                    <td class="text-center"><?php echo intval($item['quantity']); ?></td>
                                    <td class="text-end"><?php echo $symbol . number_format($unitPrice, 2); ?></td>
                                    <td class="text-end"><?php echo $symbol . number_format($subtotal, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <?php if (!empty($viewOrder['loyalty_points_used'])): ?>
                            <tr>
                                <td colspan="4" class="text-end text-muted">Loyalty Discount:</td>
                                <td class="text-end text-danger">
                                    -<?php echo ($currentCurrency['currency_symbol'] ?? '$') . number_format($viewOrder['loyalty_points_used'] / 100, 2); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th class="text-end">
                                    <?php echo ($currentCurrency['currency_symbol'] ?? '$') . number_format($viewOrder['total_amount_display'], 2); ?>
                                </th>
                            </tr>
                            <?php if (!empty($viewOrder['loyalty_points_earned'])): ?>
                            <tr>
                                <td colspan="5" class="text-end text-success small">
                                    <i class="fas fa-star"></i>
                                    Customer earned <?php echo intval($viewOrder['loyalty_points_earned']); ?> loyalty points on this order.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <script>
        function updateStatus(orderId) {
            const status = $('#orderStatus').val();
            $('#loadingOverlay').css('display', 'flex');

            $.ajax({
                url: 'ajax/update-order-status.php',
                method: 'POST',
                data: { order_id: orderId, status: status },
                dataType: 'json',
                success: function(response) {
                    $('#loadingOverlay').hide();
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Order status has been updated.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to update status.', 'error');
                    }
                },
                error: function() {
                    $('#loadingOverlay').hide();
                    Swal.fire('Error!', 'Server error. Please try again.', 'error');
                }
            });
        }
        </script>

    <?php else: ?>
        <!-- Orders List View -->
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5>No Orders Yet</h5>
                <p class="text-muted">Orders for your products will appear here.</p>
            </div>
        <?php else: ?>
            <!-- Quick stats -->
            <?php
            $pending    = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
            $processing = count(array_filter($orders, fn($o) => $o['status'] === 'processing'));
            $completed  = count(array_filter($orders, fn($o) => $o['status'] === 'completed'));
            $cancelled  = count(array_filter($orders, fn($o) => $o['status'] === 'cancelled'));
            ?>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded text-center" style="background:#fff8e1;">
                        <div class="fw-bold fs-4"><?php echo $pending; ?></div>
                        <div class="text-muted small">Pending</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded text-center" style="background:#e3f2fd;">
                        <div class="fw-bold fs-4"><?php echo $processing; ?></div>
                        <div class="text-muted small">Processing</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded text-center" style="background:#e8f5e9;">
                        <div class="fw-bold fs-4"><?php echo $completed; ?></div>
                        <div class="text-muted small">Completed</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded text-center" style="background:#fce4ec;">
                        <div class="fw-bold fs-4"><?php echo $cancelled; ?></div>
                        <div class="text-muted small">Cancelled</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="fw-semibold">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                            </td>
                            <td><?php echo ($currentCurrency['currency_symbol'] ?? '$') . number_format($order['total_amount_display'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="?view=<?php echo intval($order['id']); ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>