<?php
require_once '../classes/User.php';
require_once '../classes/Order.php';
require_once '../classes/ContentManager.php';
require_once '../classes/CurrencyManager.php';

$userObj = new User();
$orderObj = new Order();
$contentManager = new ContentManager();
$currencyManager = new CurrencyManager();

// Redirect if not logged in
if (!$userObj->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentCurrency = $currencyManager->getCurrentCurrency();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get orders for the logged in user
$orders = $orderObj->getOrdersByUser($_SESSION['user_id'], $limit, $offset);
$totalOrders = $orderObj->getOrderCountByUser($_SESSION['user_id']);
$totalPages = ceil($totalOrders / $limit);

// Order status colors - FIXED: Added 'completed' status
$statusColors = [
    'pending' => '#ffc107',
    'confirmed' => '#2196f3',
    'processing' => '#ff9800',
    'shipped' => '#9c27b0',
    'delivered' => '#4caf50',
    'completed' => '#4caf50',  // Added completed status
    'cancelled' => '#f44336'
];

$statusIcons = [
    'pending' => 'fa-clock',
    'confirmed' => 'fa-check-circle',
    'processing' => 'fa-box',
    'shipped' => 'fa-truck',
    'delivered' => 'fa-home',
    'completed' => 'fa-check-double',  // Added completed status icon
    'cancelled' => 'fa-times-circle'
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | <?php echo $contentManager->get('site_title', 'Greenfield Local Hub'); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root, [data-theme="default"] {
            --bg-primary: #0d1f0f; --bg-secondary: #122614; --bg-card: #172d1a;
            --bg-nav: #0a1a0c; --accent-primary: #4caf50; --accent-bright: #81c784;
            --accent-muted: #2e7d32; --accent-gold: #ffd54f; --text-primary: #e8f5e9;
            --text-secondary: #a5d6a7; --text-muted: #66bb6a; --text-inverse: #0d1f0f;
            --border-color: #1e3d20; --border-subtle: #1a3320; --shadow-card: 0 8px 32px rgba(0,0,0,0.45);
            --promo-bg: #ffd54f; --badge-bg: #e53935; --focus-ring: 0 0 0 3px rgba(76,175,80,0.45);
        }
        [data-theme="dark"] {
            --bg-primary: #060e07; --bg-secondary: #0a140b; --bg-card: #0e1f10;
            --bg-nav: #040a05; --accent-primary: #66bb6a; --accent-bright: #a5d6a7;
            --accent-muted: #388e3c; --text-primary: #f1f8e9; --text-secondary: #c8e6c9;
            --text-muted: #81c784; --border-color: #152617;
        }
        [data-theme="high-contrast"] {
            --bg-primary: #000000; --bg-secondary: #0a0a0a; --bg-card: #0f0f0f;
            --accent-primary: #00ff44; --accent-bright: #00ff44; --accent-muted: #00cc33;
            --text-primary: #ffffff; --text-secondary: #00ff44; --border-color: #00ff44;
        }
        [data-theme="low-contrast"] {
            --bg-primary: #1a2e1c; --bg-secondary: #1e3520; --bg-card: #223824;
            --accent-primary: #6bab6e; --accent-bright: #8dc490; --text-primary: #c5dbc6;
        }
        [data-font-size="large"] { font-size: 120% !important; }
        
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            transition: background-color 0.3s, color 0.3s;
        }
        .skip-link {
            position: absolute; top: -60px; left: 1rem;
            background: var(--accent-primary); color: var(--text-inverse);
            padding: 0.5rem 1rem; border-radius: 0 0 8px 8px;
            font-weight: 600; z-index: 9999; text-decoration: none;
        }
        .skip-link:focus { top: 0; }
        .promo-banner {
            background: var(--promo-bg); color: var(--text-inverse);
            text-align: center; padding: 10px 1rem; font-size: 0.9rem; font-weight: 600;
        }
        .a11y-toolbar {
            background: var(--bg-nav); border-bottom: 1px solid var(--border-color);
            padding: 6px 0;
        }
        .a11y-toolbar .container {
            display: flex; align-items: center; gap: 8px;
            flex-wrap: wrap; justify-content: flex-end;
        }
        .a11y-label {
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--text-muted); font-weight: 600;
        }
        .a11y-btn {
            background: var(--bg-card); color: var(--text-secondary);
            border: 1px solid var(--border-color); border-radius: 6px;
            padding: 4px 10px; font-size: 0.75rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s; display: inline-flex;
            align-items: center; gap: 5px;
        }
        .a11y-btn:hover, .a11y-btn.active {
            background: var(--accent-primary); color: var(--text-inverse);
            border-color: var(--accent-primary);
        }
        .a11y-divider {
            width: 1px; height: 20px; background: var(--border-color); margin: 0 4px;
        }
        .navbar {
            background: var(--bg-nav) !important;
            border-bottom: 1px solid var(--border-color);
            padding: 0.9rem 0; position: sticky; top: 0; z-index: 1000;
            backdrop-filter: blur(12px);
        }
        .navbar-brand {
            font-family: 'Playfair Display', serif; font-size: 1.4rem;
            font-weight: 700; color: var(--accent-bright) !important;
            display: flex; align-items: center; gap: 8px; text-decoration: none;
        }
        .navbar-brand .brand-leaf {
            width: 32px; height: 32px; background: var(--accent-muted);
            border-radius: 50% 50% 50% 0; transform: rotate(-45deg);
            display: flex; align-items: center; justify-content: center;
        }
        .navbar-brand .brand-leaf i { transform: rotate(45deg); font-size: 14px; color: white; }
        .nav-link {
            color: var(--text-secondary) !important; font-size: 0.875rem;
            font-weight: 500; padding: 0.4rem 0.75rem !important;
            border-radius: 8px; transition: all 0.2s;
        }
        .nav-link:hover { color: var(--accent-bright) !important; background: rgba(76,175,80,0.1); }
        .dropdown-menu {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 10px; box-shadow: var(--shadow-card);
        }
        .dropdown-item {
            color: var(--text-secondary); border-radius: 7px;
        }
        .dropdown-item:hover { background: rgba(76,175,80,0.12); color: var(--accent-bright); }
        
        .breadcrumb-wrapper {
            background: var(--bg-secondary); padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-subtle);
        }
        .breadcrumb { margin: 0; background: transparent; }
        .breadcrumb-item { color: var(--text-muted); font-size: 0.8rem; }
        .breadcrumb-item a { color: var(--text-secondary); text-decoration: none; }
        .breadcrumb-item a:hover { color: var(--accent-bright); }
        .breadcrumb-item.active { color: var(--accent-bright); }
        .breadcrumb-item + .breadcrumb-item::before { color: var(--text-muted); content: "›"; }

        .orders-section { padding: 3rem 0 5rem; background: var(--bg-primary); }
        .section-header { text-align: center; margin-bottom: 2.5rem; }
        .section-header .eyebrow {
            text-transform: uppercase; letter-spacing: 0.12em;
            font-size: 0.72rem; font-weight: 700; color: var(--accent-gold);
        }
        .section-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.8rem, 3vw, 2.5rem);
            font-weight: 700; color: var(--text-primary);
        }
        .section-divider {
            width: 48px; height: 3px; background: var(--accent-primary);
            margin: 1rem auto 0; border-radius: 2px;
        }

        .order-card {
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 16px; margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        .order-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-card); }
        .order-header {
            padding: 1rem 1.25rem; background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-subtle);
            display: flex; flex-wrap: wrap; justify-content: space-between;
            align-items: center; gap: 1rem;
        }
        .order-number {
            font-family: 'Playfair Display', serif;
            font-size: 1rem; font-weight: 700; color: var(--accent-bright);
        }
        .order-date { font-size: 0.75rem; color: var(--text-muted); }
        .status-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 50px;
            font-size: 0.7rem; font-weight: 600;
        }
        .order-body { padding: 1rem 1.25rem; }
        .order-items { margin-bottom: 1rem; }
        .order-item {
            display: flex; align-items: center; gap: 1rem;
            padding: 0.75rem 0; border-bottom: 1px solid var(--border-subtle);
        }
        .order-item:last-child { border-bottom: none; }
        .item-img {
            width: 50px; height: 50px; object-fit: cover;
            border-radius: 8px;
        }
        .item-details { flex: 1; }
        .item-name { font-weight: 600; color: var(--text-primary); font-size: 0.9rem; }
        .item-meta { font-size: 0.7rem; color: var(--text-muted); }
        .item-price { font-weight: 600; color: var(--accent-bright); font-size: 0.9rem; }
        .order-footer {
            padding: 0.75rem 1.25rem; background: var(--bg-secondary);
            border-top: 1px solid var(--border-subtle);
            display: flex; flex-wrap: wrap; justify-content: space-between;
            align-items: center; gap: 1rem;
        }
        .total-amount {
            font-size: 1rem; font-weight: 700; color: var(--accent-bright);
        }
        .btn-outline-order {
            background: transparent; color: var(--text-secondary);
            border: 1px solid var(--border-color); border-radius: 8px;
            padding: 0.4rem 1rem; font-size: 0.75rem; font-weight: 600;
            text-decoration: none; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-outline-order:hover {
            border-color: var(--accent-primary); color: var(--accent-bright);
        }
        .btn-primary-order {
            background: var(--accent-primary); color: var(--text-inverse);
            border: none; border-radius: 8px; padding: 0.4rem 1rem;
            font-size: 0.75rem; font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s;
        }
        .btn-primary-order:hover { background: var(--accent-bright); transform: translateY(-1px); }

        .empty-orders {
            text-align: center; padding: 4rem 2rem;
            background: var(--bg-card); border-radius: 20px;
            border: 1px dashed var(--border-color);
        }
        .empty-orders i { font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem; }
        .empty-orders h3 { font-family: 'Playfair Display', serif; margin-bottom: 1rem; }
        
        .pagination-container { margin-top: 2rem; }
        .pagination { justify-content: center; gap: 5px; }
        .page-link {
            background: var(--bg-card); color: var(--text-secondary);
            border: 1px solid var(--border-color); border-radius: 8px;
            padding: 0.5rem 1rem; transition: all 0.2s;
        }
        .page-link:hover { background: var(--accent-primary); color: var(--text-inverse); border-color: var(--accent-primary); }
        .page-item.active .page-link {
            background: var(--accent-primary); color: var(--text-inverse);
            border-color: var(--accent-primary);
        }
        .page-item.disabled .page-link {
            background: var(--bg-card); opacity: 0.5;
        }

        footer {
            background: var(--bg-nav); border-top: 1px solid var(--border-color);
            padding: 3rem 0 2rem;
        }
        .footer-brand {
            font-family: 'Playfair Display', serif; font-size: 1.3rem;
            font-weight: 700; color: var(--accent-bright);
            display: flex; align-items: center; gap: 8px; margin-bottom: 0.5rem;
        }
        .footer-desc { font-size: 0.85rem; color: var(--text-muted); line-height: 1.7; }
        .footer-heading {
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.1em;
            font-weight: 700; color: var(--text-muted); margin-bottom: 1rem;
        }
        .footer-link {
            display: block; font-size: 0.85rem; color: var(--text-secondary);
            text-decoration: none; margin-bottom: 0.5rem; transition: color 0.2s;
        }
        .footer-link:hover { color: var(--accent-bright); }
        .footer-divider { border: none; border-top: 1px solid var(--border-color); margin: 2rem 0 1.5rem; }
        .footer-copy { font-size: 0.78rem; color: var(--text-muted); text-align: center; }

        @media (max-width: 767px) {
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-footer { flex-direction: column; align-items: stretch; }
            .btn-outline-order, .btn-primary-order { text-align: center; justify-content: center; }
        }
        *:focus { outline: none; }
        *:focus-visible { box-shadow: var(--focus-ring) !important; border-radius: 4px; }
    </style>
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<?php if ($contentManager->get('promo_banner')): ?>
<div class="promo-banner"><i class="fas fa-tag"></i> <?php echo $contentManager->render('promo_banner'); ?></div>
<?php endif; ?>

<div class="a11y-toolbar">
    <div class="container">
        <span class="a11y-label"><i class="fas fa-universal-access"></i> Accessibility:</span>
        <button class="a11y-btn" id="btn-theme-dark"><i class="fas fa-moon"></i> Dark</button>
        <div class="a11y-divider"></div>
        <button class="a11y-btn" id="btn-theme-high"><i class="fas fa-adjust"></i> High Contrast</button>
        <button class="a11y-btn" id="btn-theme-low"><i class="fas fa-circle-half-stroke"></i> Low Contrast</button>
        <div class="a11y-divider"></div>
        <button class="a11y-btn" id="btn-font-large"><i class="fas fa-text-height"></i> Large Text</button>
        <button class="a11y-btn" id="btn-reset"><i class="fas fa-rotate-left"></i> Reset</button>
    </div>
</div>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <div class="brand-leaf"><i class="fas fa-seedling"></i></div>
            <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                <?php if ($userObj->isLoggedIn()): ?>
                    <?php if ($_SESSION['role'] == 'customer'): ?>
                        <li class="nav-item"><a class="nav-link" href="order-history.php">My Orders</a></li>
                        <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                    <?php elseif ($_SESSION['role'] == 'producer'): ?>
                        <li class="nav-item"><a class="nav-link" href="../producer/dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout <span class="text-accent">(<?php echo htmlspecialchars($_SESSION['username']); ?>)</span></a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <button class="nav-link dropdown-toggle btn btn-link border-0" id="currencyDropdown" data-bs-toggle="dropdown">
                        <?php echo htmlspecialchars($currentCurrency['currency_symbol']); ?> <?php echo htmlspecialchars($currentCurrency['currency_code']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($currencyManager->getAllCurrencies() as $currency): ?>
                            <li><button class="dropdown-item currency-option" data-currency="<?php echo htmlspecialchars($currency['currency_code']); ?>"><?php echo htmlspecialchars($currency['currency_symbol']); ?> <?php echo htmlspecialchars($currency['currency_code']); ?></button></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="breadcrumb-wrapper">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Orders</li>
            </ol>
        </nav>
    </div>
</div>

<main id="main-content">
<section class="orders-section">
    <div class="container">
        <div class="section-header">
            <p class="eyebrow"><i class="fas fa-box"></i> Your purchase history</p>
            <h1>My Orders</h1>
            <div class="section-divider"></div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>No orders yet</h3>
                <p class="text-muted">You haven't placed any orders yet. Start shopping to see your orders here!</p>
                <a href="products.php" class="btn-primary-order" style="display: inline-flex; padding: 0.75rem 1.5rem; margin-top: 1rem;">
                    <i class="fas fa-store"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): 
                $orderItems = $orderObj->getOrderItems($order['id']);
                $orderTotal = $order['total_amount_display'] ?? $order['total_amount'] ?? 0;
                
                // FIXED: Safely get status color and icon with fallback for unknown statuses
                $statusColor = isset($statusColors[$order['status']]) ? $statusColors[$order['status']] : '#6c757d';
                $statusIcon = isset($statusIcons[$order['status']]) ? $statusIcons[$order['status']] : 'fa-question-circle';
                $statusDisplay = ucfirst(str_replace('_', ' ', $order['status']));
            ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-number">Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></div>
                        <div class="order-date">Placed on <?php echo date('F j, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div>
                        <!-- FIXED: Using safe variables with fallbacks -->
                        <span class="status-badge" style="background: <?php echo $statusColor; ?>20; color: <?php echo $statusColor; ?>;">
                            <i class="fas <?php echo $statusIcon; ?>"></i>
                            <?php echo $statusDisplay; ?>
                        </span>
                    </div>
                </div>
                <div class="order-body">
                    <div class="order-items">
                        <?php 
                        $displayItems = array_slice($orderItems, 0, 2);
                        $remainingCount = count($orderItems) - 2;
                        foreach ($displayItems as $item):
                            $itemImage = !empty($item['image_url']) ? '' . $item['image_url'] : 'assets/images/default-product.jpg';
                            $itemPrice = isset($item['unit_price_display']) ? $item['unit_price_display'] : (isset($item['price_at_time']) ? $item['price_at_time'] : 0);
                        ?>
                        <div class="order-item">
                            <img src="<?php echo htmlspecialchars($itemImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-img" onerror="this.src='assets/images/default-product.jpg'">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-meta">Qty: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div class="item-price"><?php echo $currentCurrency['currency_symbol'] . number_format($itemPrice * $item['quantity'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if ($remainingCount > 0): ?>
                            <div class="order-item">
                                <div class="item-details text-muted" style="text-align: center; padding: 0.5rem;">
                                    <i class="fas fa-ellipsis-h"></i> and <?php echo $remainingCount; ?> more item(s)
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="order-footer">
                    <div>
                        <span class="total-amount">Total: <?php echo $currentCurrency['currency_symbol'] . number_format($orderTotal, 2); ?></span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="track-order.php?id=<?php echo $order['id']; ?>" class="btn-outline-order">
                            <i class="fas fa-map-marker-alt"></i> Track Order
                        </a>
                        <?php if ($order['status'] == 'delivered' || $order['status'] == 'completed'): ?>
                            <a href="write-review.php?order_id=<?php echo $order['id']; ?>" class="btn-outline-order">
                                <i class="fas fa-star"></i> Write Review
                            </a>
                        <?php endif; ?>
                        <?php if ($order['status'] == 'pending'): ?>
                            <button class="btn-outline-order cancel-order-btn" data-order-id="<?php echo $order['id']; ?>" style="border-color: #f44336; color: #f44336;">
                                <i class="fas fa-ban"></i> Cancel
                            </button>
                        <?php endif; ?>
                        <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-primary-order">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <nav aria-label="Order pagination">
                    <ul class="pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
</main>

<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand"><i class="fas fa-seedling"></i> <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?></div>
                <p class="footer-desc"><?php echo htmlspecialchars($contentManager->get('footer_text', 'Connecting local farmers with communities for fresher, fairer food.')); ?></p>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <p class="footer-heading">Navigate</p>
                <a href="index.php" class="footer-link">Home</a>
                <a href="products.php" class="footer-link">Products</a>
                <a href="order-history.php" class="footer-link">My Orders</a>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <p class="footer-heading">Account</p>
                <a href="cart.php" class="footer-link">Cart</a>
                <a href="profile.php" class="footer-link">Profile</a>
                <a href="logout.php" class="footer-link">Logout</a>
            </div>
            <div class="col-lg-4 col-md-6">
                <p class="footer-heading">Contact</p>
                <a href="mailto:<?php echo htmlspecialchars($contentManager->get('contact_email','support@greenfieldhub.com')); ?>" class="footer-link"><?php echo htmlspecialchars($contentManager->get('contact_email','support@greenfieldhub.com')); ?></a>
            </div>
        </div>
        <hr class="footer-divider">
        <p class="footer-copy">&copy; 2024 <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?>. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    const html = document.documentElement;
    const THEME_KEY = 'glh_theme';
    const FONT_KEY = 'glh_font';

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme || 'default');
        localStorage.setItem(THEME_KEY, theme || 'default');
    }
    function applyFontSize(size) {
        html.setAttribute('data-font-size', size || 'normal');
        localStorage.setItem(FONT_KEY, size || 'normal');
    }
    const savedTheme = localStorage.getItem(THEME_KEY);
    const savedFont = localStorage.getItem(FONT_KEY);
    if (savedTheme) applyTheme(savedTheme);
    if (savedFont) applyFontSize(savedFont);

    document.getElementById('btn-theme-dark')?.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        applyTheme(current === 'dark' ? 'default' : 'dark');
    });
    document.getElementById('btn-theme-high')?.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        applyTheme(current === 'high-contrast' ? 'default' : 'high-contrast');
    });
    document.getElementById('btn-theme-low')?.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        applyTheme(current === 'low-contrast' ? 'default' : 'low-contrast');
    });
    document.getElementById('btn-font-large')?.addEventListener('click', () => {
        const current = html.getAttribute('data-font-size');
        applyFontSize(current === 'large' ? 'normal' : 'large');
    });
    document.getElementById('btn-reset')?.addEventListener('click', () => {
        applyTheme('default');
        applyFontSize('normal');
    });

    document.querySelectorAll('.currency-option').forEach(el => {
        el.addEventListener('click', function() {
            $.ajax({
                url: 'set-currency.php',
                method: 'POST',
                data: { currency: this.dataset.currency },
                dataType: 'json',
                success: function(response) {
                    if (response.success) location.reload();
                    else Swal.fire({ title: 'Error', text: 'Failed to change currency', icon: 'error', background: '#172d1a', color: '#e8f5e9' });
                }
            });
        });
    });

    document.querySelectorAll('.cancel-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            Swal.fire({
                title: 'Cancel Order?',
                text: 'Are you sure you want to cancel this order?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#2e7d32',
                confirmButtonText: 'Yes, cancel',
                background: '#172d1a',
                color: '#e8f5e9'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../ajax/cancel-order.php',
                        method: 'POST',
                        data: { order_id: orderId },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({ title: 'Cancelled', text: 'Order cancelled successfully.', icon: 'success', background: '#172d1a', color: '#e8f5e9' }).then(() => location.reload());
                            } else {
                                Swal.fire({ title: 'Error', text: response.message || 'Failed to cancel', icon: 'error', background: '#172d1a', color: '#e8f5e9' });
                            }
                        }
                    });
                }
            });
        });
    });
})();
</script>
</body>
</html>