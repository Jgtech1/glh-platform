<?php
require_once 'includes/header.php';

// Get a direct DB connection
$conn = Database::getInstance()->getConnection();

// Get producer's products
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE producer_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$products = $stmt->fetchAll();
?>

<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5><i class="fas fa-box"></i> My Products</h5>
        <a href="add-product.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>
    
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
            <h5>No Products Yet</h5>
            <p>Start adding your products to sell on the platform.</p>
            <a href="add-product.php" class="btn btn-primary">Add Your First Product</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="../public/<?php echo $product['image_url']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo ($currentCurrency['currency_symbol'] ?? '$') . number_format($product['price'], 2); ?> / <?php echo htmlspecialchars($product['unit']); ?></td>
                        <td>
                            <?php if ($product['stock_quantity'] <= 10): ?>
                                <span class="text-warning">⚠️ <?php echo $product['stock_quantity']; ?></span>
                            <?php else: ?>
                                <?php echo $product['stock_quantity']; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo $product['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                        <td>
                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger delete-product" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
$('.delete-product').click(function() {
    const productId = $(this).data('id');
    
    Swal.fire({
        title: 'Delete Product?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete-product.php?id=' + productId;
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>