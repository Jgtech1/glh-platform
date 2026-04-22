<?php
require_once 'includes/header.php';

$conn = Database::getInstance()->getConnection();

// Get product ID from URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$productId) {
    header('Location: products.php');
    exit();
}

// Fetch the product and make sure it belongs to this producer
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE id = ? AND producer_id = ?
");
$stmt->execute([$productId, $_SESSION['user_id']]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit();
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'           => trim($_POST['name'] ?? ''),
        'description'    => trim($_POST['description'] ?? ''),
        'price'          => $_POST['price'] ?? '',
        'stock_quantity' => $_POST['stock_quantity'] ?? '',
        'category'       => trim($_POST['category'] ?? ''),
        'unit'           => trim($_POST['unit'] ?? ''),
        'status'         => $_POST['status'] ?? 'active',
    ];

    // Basic validation
    if (empty($data['name'])) {
        $errors[] = 'Product name is required.';
    }
    if (!is_numeric($data['price']) || $data['price'] < 0) {
        $errors[] = 'Please enter a valid price.';
    }
    if (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0) {
        $errors[] = 'Please enter a valid stock quantity.';
    }

    if (empty($errors)) {
        $result = $productObj->updateProduct($productId, $data, $_FILES['image'] ?? null);

        if ($result['success']) {
            $success = 'Product updated successfully!';
            // Refresh product data
            $stmt->execute([$productId, $_SESSION['user_id']]);
            $product = $stmt->fetch();
        } else {
            $errors = $result['errors'];
        }
    }
}

// Category options
$categories = ['Vegetables', 'Fruits', 'Grains', 'Dairy', 'Meat', 'Poultry', 'Seafood', 'Herbs & Spices', 'Other'];

// Unit options
$units = ['kg', 'g', 'lb', 'oz', 'liter', 'ml', 'piece', 'dozen', 'pack', 'bag', 'box', 'crate'];
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="table-container">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Product</h5>
                <a href="products.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <!-- Product Name -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>

                <!-- Price & Stock -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">
                            Price (<?php echo htmlspecialchars($currentCurrency['currency_symbol'] ?? '$'); ?>) 
                            <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($product['price']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="stock_quantity" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($product['stock_quantity']); ?>" required>
                    </div>
                </div>

                <!-- Category & Unit -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"
                                    <?php echo ($product['category'] ?? '') === $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Unit</label>
                        <select name="unit" class="form-select">
                            <option value="">-- Select Unit --</option>
                            <?php foreach ($units as $unit): ?>
                                <option value="<?php echo $unit; ?>"
                                    <?php echo ($product['unit'] ?? '') === $unit ? 'selected' : ''; ?>>
                                    <?php echo $unit; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?php echo ($product['status'] ?? '') === 'active'   ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>

                <!-- Current Image -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Current Image</label><br>
                    <?php if (!empty($product['image_url'])): ?>
                        <img src="../public/<?php echo htmlspecialchars($product['image_url']); ?>"
                             alt="Product Image"
                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 10px; border: 1px solid #dee2e6;">
                    <?php else: ?>
                        <div style="width: 120px; height: 120px; background: #f0f0f0; border-radius: 10px;
                                    display: flex; align-items: center; justify-content: center; border: 1px solid #dee2e6;">
                            <i class="fas fa-image fa-2x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Replace Image -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">Replace Image <span class="text-muted">(optional)</span></label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div class="form-text">Accepted formats: JPG, PNG, GIF, WEBP. Leave empty to keep the current image.</div>
                    <!-- Preview -->
                    <div class="mt-2" id="previewWrapper" style="display:none;">
                        <p class="form-text mb-1">New image preview:</p>
                        <img id="imagePreview" src="#" alt="Preview"
                             style="width: 120px; height: 120px; object-fit: cover; border-radius: 10px; border: 1px solid #dee2e6;">
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="products.php" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// Live image preview
document.querySelector('input[name="image"]').addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('previewWrapper').style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('previewWrapper').style.display = 'none';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>