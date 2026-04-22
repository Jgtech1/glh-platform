<?php
session_start();
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Product.php';
require_once __DIR__ . '/../../classes/Order.php';
require_once __DIR__ . '/../../classes/ContentManager.php';
require_once __DIR__ . '/../../classes/CurrencyManager.php';

$userObj = new User();
$productObj = new Product();
$orderObj = new Order();
$contentManager = new ContentManager();
$currencyManager = new CurrencyManager();

// Check if user is logged in and is producer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'producer') {
    header('Location: ../../public/login.php');
    exit();
}

$currentUser = $userObj->getCurrentUser();
$currentCurrency = $currencyManager->getCurrentCurrency();

// Get statistics for sidebar badge
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM products WHERE producer_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$productCount = $stmt->fetch()['total'];

$stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE p.producer_id = ? AND o.status = 'pending'
");
$stmt->execute([$_SESSION['user_id']]);
$pendingOrders = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producer Dashboard - <?php echo $contentManager->get('site_title', 'GLH'); ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100%;
            background: linear-gradient(135deg, #1a472a 0%, #0d2818 100%);
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
        }
        
        .sidebar-header p {
            font-size: 0.75rem;
            opacity: 0.7;
            margin: 5px 0 0;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu .menu-item:hover,
        .sidebar-menu .menu-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #4caf50;
        }
        
        .sidebar-menu .menu-item i {
            width: 24px;
        }
        
        .badge-notification {
            background: #ff4757;
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 0.7rem;
            margin-left: auto;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            transition: all 0.3s;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .page-title h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: #1a472a;
        }
        
        .page-title p {
            font-size: 0.8rem;
            color: #6c757d;
            margin: 5px 0 0;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
        }
        
        /* Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        /* Tables */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        /* Status Badges */
        .badge-pending { background: #ffc107; color: #856404; }
        .badge-processing { background: #17a2b8; color: white; }
        .badge-completed { background: #28a745; color: white; }
        .badge-cancelled { background: #dc3545; color: white; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -280px;
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.active {
                left: 0;
            }
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4caf50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-tractor"></i> Producer Hub</h3>
        <p><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Producer'); ?></p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>My Products</span>
            <span class="badge-notification"><?php echo $productCount; ?></span>
        </a>
        <a href="add-product.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'add-product.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus"></i>
            <span>Add Product</span>
        </a>
        <a href="orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>Orders</span>
            <?php if ($pendingOrders > 0): ?>
                <span class="badge-notification"><?php echo $pendingOrders; ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>
        <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar">
        <div class="page-title">
            <h2>
                <?php
                $pageTitles = [
                    'dashboard.php' => 'Dashboard',
                    'products.php' => 'My Products',
                    'add-product.php' => 'Add New Product',
                    'edit-product.php' => 'Edit Product',
                    'orders.php' => 'Orders Management',
                    'profile.php' => 'My Profile',
                    'settings.php' => 'Settings'
                ];
                $currentPage = basename($_SERVER['PHP_SELF']);
                echo $pageTitles[$currentPage] ?? 'Dashboard';
                ?>
            </h2>
            <p>Welcome back, <?php echo htmlspecialchars($currentUser['full_name'] ?? 'Producer'); ?>!</p>
        </div>
        <div class="user-dropdown">
            <div class="user-avatar">
                <?php echo strtoupper(substr($currentUser['full_name'] ?? 'P', 0, 1)); ?>
            </div>
        </div>
    </div>
    
    <div class="content-area">