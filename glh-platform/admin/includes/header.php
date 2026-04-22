<?php
//session_start();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $contentManager->get('site_title', 'GLH'); ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- jQuery & SweetAlert -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <style>
        :root {
            --primary-color: #2c7da0;
            --secondary-color: #61a5c2;
            --success-color: #2a9d8f;
            --danger-color: #e63946;
            --warning-color: #f4a261;
            --info-color: #219ebc;
            --dark-color: #1e2a3e;
            --light-color: #f8f9fa;
            --sidebar-width: 280px;
        }
        
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
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--dark-color) 0%, #0f172a 100%);
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
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu .menu-item i {
            width: 24px;
            font-size: 1.1rem;
        }
        
        .sidebar-menu .menu-item span {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .badge-notification {
            background: var(--danger-color);
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 0.7rem;
            margin-left: auto;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
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
        
        .navbar-title h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: var(--dark-color);
        }
        
        .navbar-title p {
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
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .user-role {
            font-size: 0.7rem;
            color: #6c757d;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(44,125,160,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary-color);
        }
        
        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--dark-color);
        }
        
        .stat-info p {
            margin: 0;
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        /* Tables */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .dataTables_wrapper {
            padding: 20px;
        }
        
        table.dataTable {
            margin-top: 20px !important;
        }
        
        .btn-action {
            padding: 5px 10px;
            margin: 0 3px;
            border-radius: 6px;
            font-size: 0.8rem;
        }
        
        /* Status Badges */
        .badge-active {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-processing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Modal Styles */
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        /* Form Styles */
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            padding: 10px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(44,125,160,0.25);
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
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
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
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
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-crown"></i> Admin Panel</h3>
        <p><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Administrator'); ?></p>
    </div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
        </a>
        <a href="users.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Users Management</span>
            <span class="badge-notification"><?php echo $totalUsers; ?></span>
        </a>
        <a href="products.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Products</span>
            <span class="badge-notification"><?php echo $totalProducts; ?></span>
        </a>
        <a href="orders.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>Orders</span>
            <?php if ($pendingOrders > 0): ?>
                <span class="badge-notification"><?php echo $pendingOrders; ?></span>
            <?php endif; ?>
        </a>
        <a href="categories.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>
        <a href="content.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'content.php' ? 'active' : ''; ?>">
            <i class="fas fa-edit"></i>
            <span>Content Manager</span>
        </a>
        <a href="currency.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'currency.php' ? 'active' : ''; ?>">
            <i class="fas fa-dollar-sign"></i>
            <span>Currency Settings</span>
        </a>
        <a href="reports.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <a href="settings.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>System Settings</span>
        </a>
        <a href="profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i>
            <span>My Profile</span>
        </a>
        <a href="../public/logout.php" class="menu-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="top-navbar">
        <div class="navbar-title">
            <h2>
                <?php
                $pageTitles = [
                    'dashboard.php' => 'Dashboard',
                    'users.php' => 'Users Management',
                    'products.php' => 'Products Management',
                    'orders.php' => 'Orders Management',
                    'categories.php' => 'Categories',
                    'content.php' => 'Content Manager',
                    'currency.php' => 'Currency Settings',
                    'reports.php' => 'Reports & Analytics',
                    'settings.php' => 'System Settings',
                    'profile.php' => 'My Profile'
                ];
                $currentPage = basename($_SERVER['PHP_SELF']);
                echo $pageTitles[$currentPage] ?? 'Dashboard';
                ?>
            </h2>
            <p>Welcome back, <?php echo htmlspecialchars($currentUser['full_name'] ?? 'Admin'); ?></p>
        </div>
        <div class="user-dropdown">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Administrator'); ?></div>
                <div class="user-role">Super Administrator</div>
            </div>
            <div class="user-avatar">
                <?php echo strtoupper(substr($currentUser['full_name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </div>
    
    <div class="content-area">