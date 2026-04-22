<?php
require_once 'includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

$error = '';
$success = '';

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Fetch categories from database with hierarchy
$categories = [];
try {
    // Get all active categories
    $stmt = $conn->prepare("
        SELECT id, name, description, parent_id, icon, status 
        FROM categories 
        WHERE status = 'active' 
        ORDER BY parent_id, name
    ");
    $stmt->execute();
    $allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build hierarchical dropdown options
    function buildCategoryOptions($categories, $parentId = 0, $prefix = '') {
        $options = '';
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $options .= '<option value="' . $category['id'] . '">' . $prefix . htmlspecialchars($category['name']) . '</option>';
                // Add subcategories with extra prefix
                $options .= buildCategoryOptions($categories, $category['id'], $prefix . '&nbsp;&nbsp;&nbsp;├─ ');
            }
        }
        return $options;
    }
    
    $categoryOptions = buildCategoryOptions($allCategories);
    
} catch (PDOException $e) {
    $error = 'Failed to load categories: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $unit = trim($_POST['unit'] ?? '');
    
    if (empty($name) || $price <= 0 || $stock_quantity < 0) {
        $error = 'Please fill in all required fields correctly.';
    } else {
        // Handle image upload
        $image_url = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $target_dir = "../public/assets/uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $file_name = time() . '_' . uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $file_name;
            
            // Validate image
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['product_image']['tmp_name']);
            finfo_close($finfo);
            
            if (in_array($mime_type, $allowed_types)) {
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    $image_url = 'assets/uploads/' . $file_name;
                }
            }
        }
        
        try {
            // Insert product with category_id
            $stmt = $conn->prepare("
                INSERT INTO products (producer_id, name, description, price, stock_quantity, image_url, category_id, unit, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            
            if ($stmt->execute([$_SESSION['user_id'], $name, $description, $price, $stock_quantity, $image_url, $category_id, $unit])) {
                $success = 'Product added successfully!';
                echo '<script>
                    Swal.fire({
                        title: "Success!",
                        text: "Product added successfully!",
                        icon: "success",
                        confirmButtonText: "OK"
                    }).then(() => {
                        window.location.href = "products.php";
                    });
                </script>';
            } else {
                $error = 'Failed to add product. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="table-container">
    <h5><i class="fas fa-plus"></i> Add New Product</h5>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data" id="productForm">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label for="name">Product Name *</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="5"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="price">Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="price" id="price" step="0.01" class="form-control" required min="0.01">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="unit">Unit *</label>
                            <select name="unit" id="unit" class="form-control" required>
                                <option value="">Select unit</option>
                                <option value="kg">Kilogram (kg)</option>
                                <option value="g">Gram (g)</option>
                                <option value="piece">Piece</option>
                                <option value="dozen">Dozen</option>
                                <option value="bundle">Bundle</option>
                                <option value="liter">Liter (L)</option>
                                <option value="ml">Milliliter (ml)</option>
                                <option value="box">Box</option>
                                <option value="pack">Pack</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="stock_quantity">Stock Quantity *</label>
                            <input type="number" name="stock_quantity" id="stock_quantity" class="form-control" required min="0">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_id">Category *</label>
                            <select name="category_id" id="category_id" class="form-control" required>
                                <option value="">Select a category</option>
                                <?php echo $categoryOptions; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="product_image">Product Image</label>
                            <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Allowed formats: JPG, PNG, GIF, WEBP. Max size: 5MB</small>
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <img id="preview" src="#" alt="Preview" style="max-width: 200px; max-height: 200px;" class="img-thumbnail">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-lightbulb"></i> Tips for better sales:</h6>
                        <ul class="small mb-0">
                            <li>Use high-quality images</li>
                            <li>Write detailed descriptions</li>
                            <li>Set competitive prices</li>
                            <li>Keep stock updated</li>
                            <li>Choose the right category</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle"></i> Category Info:</h6>
                        <div id="categoryInfo" class="small text-muted">
                            Select a category to see details
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Add Product
            </button>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<script>
// Image preview function
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('preview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

// Fetch category info when category changes
document.getElementById('category_id').addEventListener('change', function() {
    const categoryId = this.value;
    const categoryInfo = document.getElementById('categoryInfo');
    
    if (categoryId) {
        const selectedOption = this.options[this.selectedIndex];
        categoryInfo.innerHTML = `<strong>${selectedOption.text}</strong><br>Category selected for this product`;
    } else {
        categoryInfo.innerHTML = 'Select a category to see details';
    }
});

// Form validation
document.getElementById('productForm').addEventListener('submit', function(e) {
    const price = document.getElementById('price').value;
    const stock = document.getElementById('stock_quantity').value;
    const category = document.getElementById('category_id').value;
    const name = document.getElementById('name').value;
    
    if (!name.trim()) {
        e.preventDefault();
        Swal.fire('Error', 'Product name is required', 'error');
        return false;
    }
    
    if (parseFloat(price) <= 0) {
        e.preventDefault();
        Swal.fire('Error', 'Price must be greater than 0', 'error');
        return false;
    }
    
    if (parseInt(stock) < 0) {
        e.preventDefault();
        Swal.fire('Error', 'Stock quantity cannot be negative', 'error');
        return false;
    }
    
    if (!category) {
        e.preventDefault();
        Swal.fire('Error', 'Please select a category', 'error');
        return false;
    }
    
    const unit = document.getElementById('unit').value;
    if (!unit) {
        e.preventDefault();
        Swal.fire('Error', 'Please select a unit', 'error');
        return false;
    }
    
    return true;
});
</script>

<style>
.table-container {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}
.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}
.card {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?php require_once 'includes/footer.php'; ?>