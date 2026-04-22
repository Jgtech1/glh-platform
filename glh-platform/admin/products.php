<?php
// Start output buffering to prevent header issues
ob_start();
session_start(); // <-- FIXED: Uncommented this line!

// Fix the include paths - determine the correct base path
define('BASE_PATH', dirname(__DIR__)); // This goes up one level from admin to root
define('CLASSES_PATH', BASE_PATH . '/classes/');

// Include required classes with correct paths
require_once CLASSES_PATH . 'User.php';
require_once CLASSES_PATH . 'Product.php';
require_once CLASSES_PATH . 'Order.php';
require_once CLASSES_PATH . 'ContentManager.php';
require_once CLASSES_PATH . 'CurrencyManager.php';
require_once CLASSES_PATH . 'Database.php';

$userObj = new User();
$productObj = new Product();
$orderObj = new Order();
$contentManager = new ContentManager();
$currencyManager = new CurrencyManager();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$currentUser = $userObj->getCurrentUser();
$currentCurrency = $currencyManager->getCurrentCurrency();

// Get statistics for sidebar
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM products");
$totalProducts = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$pendingOrders = $stmt->fetch()['count'];

// Handle AJAX requests first (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    // Handle toggle status via AJAX
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['product_id'])) {
        $productId = (int)$_POST['product_id'];
        try {
            $stmt = $conn->prepare("UPDATE products SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
            $result = $stmt->execute([$productId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
    
    // Handle delete via AJAX
    if (isset($_POST['action']) && $_POST['action'] === 'delete_product' && isset($_POST['product_id'])) {
        $productId = (int)$_POST['product_id'];
        try {
            $result = $productObj->deleteProduct($productId);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

// Handle regular GET requests (non-AJAX) - but redirect to clean URL
if (isset($_GET['delete']) || isset($_GET['toggle'])) {
    // Store the action in session to show message after redirect
    if (isset($_GET['delete'])) {
        $productId = (int)$_GET['delete'];
        $productObj->deleteProduct($productId);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Product deleted successfully'];
    }
    
    if (isset($_GET['toggle'])) {
        $productId = (int)$_GET['toggle'];
        $stmt = $conn->prepare("UPDATE products SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
        $stmt->execute([$productId]);
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Product status updated successfully'];
    }
    
    // Redirect to clean URL
    header('Location: products.php');
    exit();
}

// Get all products with producer info
$stmt = $conn->prepare("
    SELECT p.*, u.full_name as producer_name 
    FROM products p 
    JOIN users u ON p.producer_id = u.id 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$products = $stmt->fetchAll();

// Start the HTML output
require_once 'includes/header.php';

// Show flash message if exists
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "' . ($flash['type'] === 'success' ? 'Success!' : 'Error!') . '",
                text: "' . addslashes($flash['text']) . '",
                icon: "' . $flash['type'] . '",
                confirmButtonText: "OK"
            });
        });
    </script>';
    unset($_SESSION['flash_message']);
}
?>

<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5><i class="fas fa-box"></i> All Products</h5>
        <button class="btn btn-primary" onclick="openAddProductModal()">
            <i class="fas fa-plus"></i> Add New Product
        </button>
    </div>
    
    <div class="table-responsive">
        <table id="productsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Producer</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr id="product-row-<?php echo $product['id']; ?>">
                    <td><?php echo $product['id']; ?></td>
                    <td>
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="../public/<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;" 
                                 onerror="this.src='assets/images/no-image.png'">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['producer_name']); ?></td>
                    <td>
                        <?php echo $currentCurrency['symbol'] ?? '$'; ?><?php echo number_format($product['price'], 2); ?> / <?php echo htmlspecialchars($product['unit']); ?>
                    </td>
                    <td>
                        <?php if ($product['stock_quantity'] <= 10 && $product['stock_quantity'] > 0): ?>
                            <span class="badge bg-warning text-dark">⚠️ Low Stock: <?php echo $product['stock_quantity']; ?></span>
                        <?php elseif ($product['stock_quantity'] <= 0): ?>
                            <span class="badge bg-danger">Out of Stock</span>
                        <?php else: ?>
                            <span class="badge bg-success"><?php echo $product['stock_quantity']; ?> in stock</span>
                        <?php endif; ?>
                     </td>
                    <td>
                        <span class="badge <?php echo $product['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?> status-badge-<?php echo $product['id']; ?>">
                            <?php echo ucfirst($product['status']); ?>
                        </span>
                     </td>
                    <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick="toggleStatus(<?php echo $product['id']; ?>)">
                            <i class="fas fa-toggle-<?php echo $product['status'] == 'active' ? 'off' : 'on'; ?>"></i>
                        </button>
                        <button class="btn btn-sm btn-info" onclick="editProduct(<?php echo $product['id']; ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $product['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                     </td>
                 </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#productsTable').DataTable({
        pageLength: 25,
        order: [[0, 'desc']],
        language: {
            search: "Search products:",
            lengthMenu: "Show _MENU_ products per page",
            info: "Showing _START_ to _END_ of _TOTAL_ products"
        }
    });
});

// Toggle product status using AJAX
function toggleStatus(productId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to change this product's status?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: 'products.php',
                method: 'POST',
                data: {
                    action: 'toggle_status',
                    product_id: productId
                },
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Updated!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to update status.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Confirm and delete product
function confirmDelete(productId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: 'products.php',
                method: 'POST',
                data: {
                    action: 'delete_product',
                    product_id: productId
                },
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to delete product.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Edit product (implement as needed)
function editProduct(productId) {
    // Redirect to edit page or open modal
    window.location.href = 'edit_product.php?id=' + productId;
}

// Add new product
function openAddProductModal() {
    window.location.href = 'add_product.php';
}
</script>

<?php 
require_once 'includes/footer.php';
// Flush the output buffer
ob_end_flush();
?>