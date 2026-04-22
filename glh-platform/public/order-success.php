<?php
session_start();
require_once '../classes/User.php';
require_once '../classes/Order.php';
require_once '../classes/ContentManager.php';
require_once '../classes/CurrencyManager.php';

$userObj        = new User();
$orderObj       = new Order();
$contentManager = new ContentManager();
$currencyManager = new CurrencyManager();

// Redirect if not logged in
if (!$userObj->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get order ID from URL
$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

// Get order details
$order = $orderObj->getOrderById($orderId);

// Verify order belongs to the logged-in user
if (!$order || (int) $order['user_id'] !== (int) $_SESSION['user_id']) {
    header('Location: index.php');
    exit;
}

// Get order items
$orderItems = $orderObj->getOrderItems($orderId);

// Get current currency
$currentCurrency = $currencyManager->getCurrentCurrency();
$symbol          = $currentCurrency['currency_symbol'] ?? '$';

// FIX: getOrderItems() returns 'unit_price_display' (aliased from price_at_time),
// NOT 'price_at_time_display'. Normalise every item to a single 'unit_price' key.
foreach ($orderItems as &$item) {
    $item['unit_price'] = (float) (
        $item['unit_price_display']    // alias set in getOrderItems()
        ?? $item['price_at_time']      // raw column fallback
        ?? 0
    );
}
unset($item);

// Calculate subtotal from normalised unit_price
$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['unit_price'] * (int) $item['quantity'];
}

$shippingCost = 5.00;
$taxAmount    = $subtotal * 0.08;

// FIX: use total_amount_display if available, else fall back to total_amount
$totalAmount = (float) (
    $order['total_amount_display']
    ?? $order['total_amount']
    ?? ($subtotal + $shippingCost + $taxAmount)
);

// Loyalty points info
$loyaltyPointsUsed   = (int)   ($order['loyalty_points_used']   ?? 0);
$loyaltyPointsEarned = (int)   ($order['loyalty_points_earned'] ?? 0);
$loyaltyDiscount     = $orderObj->calculatePointsDiscount($loyaltyPointsUsed);

// Estimated delivery (3–5 business days)
$orderDate       = new DateTime($order['created_at']);
$deliveryStart   = clone $orderDate; $deliveryStart->modify('+3 days');
$deliveryEnd     = clone $orderDate; $deliveryEnd->modify('+5 days');

$siteTitle = $contentManager->get('site_title', 'Greenfield Local Hub');
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | <?php echo htmlspecialchars($siteTitle); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* ── Design tokens (identical to checkout.php) ── */
        :root, [data-theme="default"] {
            --bg-primary:#0d1f0f; --bg-secondary:#122614; --bg-card:#172d1a;
            --bg-nav:#0a1a0c; --accent-primary:#4caf50; --accent-bright:#81c784;
            --accent-muted:#2e7d32; --accent-gold:#ffd54f; --text-primary:#e8f5e9;
            --text-secondary:#a5d6a7; --text-muted:#66bb6a; --text-inverse:#0d1f0f;
            --border-color:#1e3d20; --border-subtle:#1a3320;
            --shadow-card:0 8px 32px rgba(0,0,0,.45);
            --promo-bg:#ffd54f; --focus-ring:0 0 0 3px rgba(76,175,80,.45);
            --input-bg:#1a2e1c; --input-border:#2e5e30;
        }
        [data-theme="dark"] {
            --bg-primary:#060e07; --bg-secondary:#0a140b; --bg-card:#0e1f10;
            --bg-nav:#040a05; --accent-primary:#66bb6a; --accent-bright:#a5d6a7;
            --accent-muted:#388e3c; --text-primary:#f1f8e9; --text-secondary:#c8e6c9;
            --input-bg:#111d12; --input-border:#244826;
        }
        [data-theme="high-contrast"] {
            --bg-primary:#000; --accent-primary:#00ff44; --accent-bright:#00ff44;
            --text-primary:#fff; --text-secondary:#00ff44; --border-color:#00ff44;
        }
        [data-font-size="large"] { font-size:120% !important; }

        *, *::before, *::after { box-sizing:border-box; }
        body {
            font-family:'DM Sans',sans-serif;
            background-color:var(--bg-primary);
            color:var(--text-primary);
            transition:background-color .3s;
        }
        .skip-link {
            position:absolute; top:-60px; left:1rem;
            background:var(--accent-primary); color:var(--text-inverse);
            padding:.5rem 1rem; border-radius:0 0 8px 8px;
            font-weight:600; z-index:9999; text-decoration:none;
        }
        .skip-link:focus { top:0; }

        /* ── a11y toolbar ── */
        .a11y-toolbar { background:var(--bg-nav); border-bottom:1px solid var(--border-color); padding:6px 0; }
        .a11y-toolbar .container { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
        .a11y-label { font-size:.72rem; text-transform:uppercase; color:var(--text-muted); font-weight:600; }
        .a11y-btn {
            background:var(--bg-card); color:var(--text-secondary);
            border:1px solid var(--border-color); border-radius:6px;
            padding:4px 10px; font-size:.75rem; font-weight:600;
            cursor:pointer; display:inline-flex; align-items:center; gap:5px;
        }
        .a11y-btn:hover, .a11y-btn.active { background:var(--accent-primary); color:var(--text-inverse); }
        .a11y-divider { width:1px; height:20px; background:var(--border-color); margin:0 4px; }

        /* ── Navbar ── */
        .navbar {
            background:var(--bg-nav) !important; border-bottom:1px solid var(--border-color);
            padding:.9rem 0; position:sticky; top:0; z-index:1000; backdrop-filter:blur(12px);
        }
        .navbar-brand {
            font-family:'Playfair Display',serif; font-size:1.4rem; font-weight:700;
            color:var(--accent-bright) !important;
            display:flex; align-items:center; gap:8px; text-decoration:none;
        }
        .navbar-brand .brand-leaf {
            width:32px; height:32px; background:var(--accent-muted);
            border-radius:50% 50% 50% 0; transform:rotate(-45deg);
            display:flex; align-items:center; justify-content:center;
        }
        .navbar-brand .brand-leaf i { transform:rotate(45deg); font-size:14px; color:#fff; }
        .nav-link { color:var(--text-secondary) !important; font-size:.875rem; font-weight:500; padding:.4rem .75rem !important; border-radius:8px; }
        .nav-link:hover { color:var(--accent-bright) !important; background:rgba(76,175,80,.1); }
        .dropdown-menu { background:var(--bg-card); border:1px solid var(--border-color); border-radius:10px; }
        .dropdown-item { color:var(--text-secondary); }
        .dropdown-item:hover { background:rgba(76,175,80,.12); color:var(--accent-bright); }

        /* ── Breadcrumb ── */
        .breadcrumb-wrapper { background:var(--bg-secondary); padding:.75rem 0; border-bottom:1px solid var(--border-subtle); }
        .breadcrumb { margin:0; background:transparent; }
        .breadcrumb-item { color:var(--text-muted); font-size:.8rem; }
        .breadcrumb-item a { color:var(--text-secondary); text-decoration:none; }
        .breadcrumb-item a:hover { color:var(--accent-bright); }
        .breadcrumb-item.active { color:var(--accent-bright); }
        .breadcrumb-item + .breadcrumb-item::before { color:var(--text-muted); content:"›"; }

        /* ── Page layout ── */
        .success-section { padding:3rem 0 5rem; }

        /* ── Success hero ── */
        .success-hero {
            text-align:center; padding:2.5rem 1rem 2rem;
            border-bottom:1px solid var(--border-subtle); margin-bottom:2rem;
        }
        .success-icon-ring {
            width:100px; height:100px; margin:0 auto 1.25rem;
            border-radius:50%;
            background:linear-gradient(135deg,var(--accent-primary),var(--accent-bright));
            display:flex; align-items:center; justify-content:center;
            box-shadow:0 0 0 12px rgba(76,175,80,.12);
            animation:popIn .55s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes popIn {
            from { transform:scale(0); opacity:0; }
            to   { transform:scale(1); opacity:1; }
        }
        .success-icon-ring i { font-size:2.8rem; color:#fff; }
        .success-hero h1 {
            font-family:'Playfair Display',serif;
            font-size:clamp(1.8rem,3.5vw,2.4rem); font-weight:700;
            margin-bottom:.5rem;
        }
        .order-badge {
            display:inline-flex; align-items:center; gap:6px;
            background:rgba(76,175,80,.12); border:1px solid var(--accent-primary);
            border-radius:50px; padding:.45rem 1.25rem; font-family:monospace;
            font-size:1rem; color:var(--accent-bright); margin-top:.75rem;
        }

        /* ── Cards (same as checkout form-card) ── */
        .info-card {
            background:var(--bg-card); border:1px solid var(--border-color);
            border-radius:20px; padding:1.5rem;
        }
        .info-card-title {
            font-family:'Playfair Display',serif; font-size:1.1rem; font-weight:700;
            color:var(--text-primary); margin-bottom:1.1rem;
            padding-bottom:.7rem; border-bottom:1px solid var(--border-subtle);
            display:flex; align-items:center; gap:8px;
        }
        .info-card-title i { color:var(--accent-bright); font-size:1rem; }

        /* ── Stat pills (order date / delivery / payment) ── */
        .stat-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1.5rem; }
        @media(max-width:767px) { .stat-grid { grid-template-columns:1fr; } }
        .stat-pill {
            background:var(--bg-secondary); border:1px solid var(--border-subtle);
            border-radius:16px; padding:1.1rem 1.25rem;
            display:flex; align-items:center; gap:.9rem;
        }
        .stat-pill-icon {
            width:42px; height:42px; flex-shrink:0;
            background:rgba(76,175,80,.1); border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.2rem; color:var(--accent-bright);
        }
        .stat-pill-label { font-size:.72rem; text-transform:uppercase; color:var(--text-muted); font-weight:600; margin-bottom:2px; }
        .stat-pill-value { font-size:.9rem; font-weight:600; color:var(--text-primary); }

        /* ── Order items list ── */
        .order-item {
            display:flex; align-items:center; gap:1rem;
            padding:.85rem 0; border-bottom:1px solid var(--border-subtle);
        }
        .order-item:last-child { border-bottom:none; }
        .order-item-img {
            width:54px; height:54px; object-fit:cover; border-radius:10px;
            background:var(--bg-secondary); flex-shrink:0;
        }
        .order-item-placeholder {
            width:54px; height:54px; border-radius:10px; flex-shrink:0;
            background:var(--bg-secondary);
            display:flex; align-items:center; justify-content:center;
            color:var(--text-muted); font-size:1.3rem;
        }
        .order-item-details { flex:1; min-width:0; }
        .order-item-name { font-weight:600; font-size:.9rem; }
        .order-item-meta { font-size:.75rem; color:var(--text-muted); margin-top:2px; }
        .order-item-price { font-weight:700; color:var(--accent-bright); white-space:nowrap; }

        /* ── Summary rows ── */
        .summary-row {
            display:flex; justify-content:space-between;
            padding:.7rem 0; border-bottom:1px solid var(--border-subtle);
            font-size:.9rem;
        }
        .summary-row:last-of-type { border-bottom:none; }
        .summary-total {
            display:flex; justify-content:space-between;
            font-size:1.15rem; font-weight:700; color:var(--accent-bright);
            margin-top:.75rem; padding-top:.75rem;
            border-top:2px solid var(--accent-primary);
        }
        .discount-row { color:var(--accent-gold); }

        /* ── Loyalty earned banner ── */
        .loyalty-earned {
            background:rgba(255,213,79,.08); border:1px solid rgba(255,213,79,.3);
            border-radius:14px; padding:1rem 1.25rem;
            display:flex; align-items:center; gap:12px; margin-top:1.25rem;
        }
        .loyalty-earned i { font-size:1.6rem; color:var(--accent-gold); }
        .loyalty-earned-text strong { color:var(--accent-gold); }
        .loyalty-earned-text small { display:block; color:var(--text-muted); font-size:.78rem; margin-top:1px; }

        /* ── Address block ── */
        .address-block {
            background:var(--bg-secondary); border:1px solid var(--border-subtle);
            border-radius:12px; padding:1rem 1.25rem;
            font-size:.9rem; color:var(--text-secondary); line-height:1.6;
        }

        /* ── Action buttons ── */
        .btn-success-primary {
            display:inline-flex; align-items:center; gap:8px;
            background:var(--accent-primary); color:var(--text-inverse);
            border:none; border-radius:50px; padding:.8rem 1.8rem;
            font-size:.95rem; font-weight:700; text-decoration:none;
            transition:all .2s;
        }
        .btn-success-primary:hover { background:var(--accent-bright); transform:translateY(-2px); color:var(--text-inverse); }
        .btn-success-outline {
            display:inline-flex; align-items:center; gap:8px;
            background:transparent; color:var(--text-secondary);
            border:1px solid var(--border-color); border-radius:50px;
            padding:.8rem 1.8rem; font-size:.95rem; font-weight:600;
            text-decoration:none; transition:all .2s;
        }
        .btn-success-outline:hover { border-color:var(--accent-primary); color:var(--accent-bright); }

        /* ── Footer ── */
        footer { background:var(--bg-nav); border-top:1px solid var(--border-color); padding:3rem 0 2rem; }
        .footer-brand { font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:700; color:var(--accent-bright); display:flex; align-items:center; gap:8px; }
        .footer-desc { font-size:.85rem; color:var(--text-muted); }
        .footer-heading { font-size:.72rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); margin-bottom:1rem; }
        .footer-link { display:block; font-size:.85rem; color:var(--text-secondary); text-decoration:none; margin-bottom:.5rem; }
        .footer-link:hover { color:var(--accent-bright); }
        .footer-divider { border-top:1px solid var(--border-color); margin:2rem 0; }
        .footer-copy { font-size:.78rem; color:var(--text-muted); text-align:center; }

        /* ── Animations ── */
        .slide-up { animation:slideUp .5s ease-out both; }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .slide-up-1 { animation-delay:.1s; }
        .slide-up-2 { animation-delay:.2s; }
        .slide-up-3 { animation-delay:.3s; }

        *:focus { outline:none; }
        *:focus-visible { box-shadow:var(--focus-ring) !important; border-radius:4px; }
    </style>
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<?php if ($contentManager->get('promo_banner')): ?>
<div style="background:var(--promo-bg);color:var(--text-inverse);text-align:center;padding:10px 1rem;font-size:.9rem;font-weight:600;">
    <i class="fas fa-tag"></i> <?php echo $contentManager->render('promo_banner'); ?>
</div>
<?php endif; ?>

<!-- Accessibility toolbar -->
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

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <div class="brand-leaf"><i class="fas fa-seedling"></i></div>
            <?php echo htmlspecialchars($siteTitle); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="products.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="order-history.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <li class="nav-item dropdown">
                    <button class="nav-link dropdown-toggle btn btn-link border-0" id="currencyDropdown" data-bs-toggle="dropdown">
                        <?php echo htmlspecialchars($currentCurrency['currency_symbol']); ?>
                        <?php echo htmlspecialchars($currentCurrency['currency_code']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php foreach ($currencyManager->getAllCurrencies() as $currency): ?>
                        <li>
                            <button class="dropdown-item currency-option"
                                    data-currency="<?php echo htmlspecialchars($currency['currency_code']); ?>">
                                <?php echo htmlspecialchars($currency['currency_symbol']); ?>
                                <?php echo htmlspecialchars($currency['currency_code']); ?>
                            </button>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Breadcrumb -->
<div class="breadcrumb-wrapper">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="order-history.php">My Orders</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order Confirmed</li>
            </ol>
        </nav>
    </div>
</div>

<!-- ===== MAIN ===== -->
<main id="main-content">
<section class="success-section">
<div class="container">

    <!-- Success hero -->
    <div class="info-card slide-up">
        <div class="success-hero">
            <div class="success-icon-ring" role="img" aria-label="Order confirmed">
                <i class="fas fa-check"></i>
            </div>
            <h1>Thank You for Your Order!</h1>
            <p style="color:var(--text-muted);">Your order has been placed and is being prepared.</p>
            <div class="order-badge">
                <i class="fas fa-receipt"></i>
                <?php echo htmlspecialchars($order['order_number'] ?? ('ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT))); ?>
            </div>
        </div>

        <!-- Stat pills -->
        <div class="stat-grid">
            <div class="stat-pill">
                <div class="stat-pill-icon"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div class="stat-pill-label">Order Date</div>
                    <div class="stat-pill-value"><?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?></div>
                </div>
            </div>
            <div class="stat-pill">
                <div class="stat-pill-icon"><i class="fas fa-truck"></i></div>
                <div>
                    <div class="stat-pill-label">Estimated Delivery</div>
                    <div class="stat-pill-value"><?php echo $deliveryStart->format('M j') . ' – ' . $deliveryEnd->format('M j, Y'); ?></div>
                </div>
            </div>
            <div class="stat-pill">
                <div class="stat-pill-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div>
                    <div class="stat-pill-label">Payment</div>
                    <div class="stat-pill-value">
                        <?php
                        $pm = $order['payment_method'] ?? 'cod';
                        $pmLabels = ['cod' => 'Cash on Delivery', 'card' => 'Card', 'paypal' => 'PayPal'];
                        echo htmlspecialchars($pmLabels[$pm] ?? ucfirst($pm));
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">

        <!-- ===== LEFT: Items + address ===== -->
        <div class="col-lg-7">

            <!-- Order items -->
            <div class="info-card slide-up slide-up-1">
                <div class="info-card-title">
                    <i class="fas fa-box-open"></i> Order Items
                </div>
                <?php foreach ($orderItems as $item):
                    $hasImage = !empty($item['image_url']);
                    $imgSrc   = $hasImage
                        ? (strpos($item['image_url'], 'http') === 0
                            ? $item['image_url']
                            : '../' . ltrim($item['image_url'], '/'))
                        : null;
                    $lineTotal = $item['unit_price'] * (int) $item['quantity'];
                ?>
                <div class="order-item">
                    <?php if ($imgSrc): ?>
                        <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="order-item-img"
                             onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div class="order-item-placeholder" style="display:none;"><i class="fas fa-leaf"></i></div>
                    <?php else: ?>
                        <div class="order-item-placeholder"><i class="fas fa-leaf"></i></div>
                    <?php endif; ?>

                    <div class="order-item-details">
                        <div class="order-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="order-item-meta">
                            Qty: <?php echo (int) $item['quantity']; ?>
                            &nbsp;·&nbsp;
                            <?php echo htmlspecialchars($symbol) . number_format($item['unit_price'], 2); ?> each
                            <?php if (!empty($item['unit'])): ?>
                                &nbsp;·&nbsp;<?php echo htmlspecialchars($item['unit']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="order-item-price">
                        <?php echo htmlspecialchars($symbol) . number_format($lineTotal, 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Shipping address -->
            <div class="info-card mt-4 slide-up slide-up-2">
                <div class="info-card-title">
                    <i class="fas fa-map-marker-alt"></i> Shipping Address
                </div>
                <div class="address-block">
                    <?php echo nl2br(htmlspecialchars($order['delivery_address'] ?? 'Address not specified')); ?>
                </div>
            </div>

        </div><!-- /col-lg-7 -->

        <!-- ===== RIGHT: Summary ===== -->
        <div class="col-lg-5">
            <div class="info-card slide-up slide-up-2">
                <div class="info-card-title">
                    <i class="fas fa-receipt"></i> Order Summary
                </div>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?php echo htmlspecialchars($symbol) . number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span><?php echo htmlspecialchars($symbol) . number_format($shippingCost, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (8%)</span>
                    <span><?php echo htmlspecialchars($symbol) . number_format($taxAmount, 2); ?></span>
                </div>

                <?php if ($loyaltyPointsUsed > 0): ?>
                <div class="summary-row discount-row">
                    <span><i class="fas fa-star"></i> Loyalty Discount</span>
                    <span>-<?php echo htmlspecialchars($symbol) . number_format($loyaltyDiscount, 2); ?></span>
                </div>
                <?php endif; ?>

                <div class="summary-total">
                    <span>Total</span>
                    <span><?php echo htmlspecialchars($symbol) . number_format($totalAmount, 2); ?></span>
                </div>

                <!-- Loyalty earned notice -->
                <?php if ($loyaltyPointsEarned > 0): ?>
                <div class="loyalty-earned">
                    <i class="fas fa-star"></i>
                    <div class="loyalty-earned-text">
                        <strong>+<?php echo number_format($loyaltyPointsEarned); ?> loyalty points earned!</strong>
                        <small>Points have been added to your account and can be used on your next order.</small>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Order status badge -->
                <div class="text-center mt-3">
                    <span style="background:rgba(76,175,80,.12);border:1px solid var(--accent-primary);
                                 color:var(--accent-bright);border-radius:50px;padding:.35rem 1rem;
                                 font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">
                        <i class="fas fa-circle" style="font-size:.5rem;vertical-align:middle;"></i>
                        <?php echo ucfirst(htmlspecialchars($order['status'] ?? 'pending')); ?>
                    </span>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="info-card mt-4 slide-up slide-up-3" style="text-align:center;">
                <div class="info-card-title" style="justify-content:center;">
                    <i class="fas fa-arrow-right"></i> What's Next?
                </div>
                <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1.25rem;">
                    We'll prepare your order and deliver it within the estimated window. You can track it from your orders page.
                </p>
                <div class="d-flex flex-column gap-2">
                    <a href="order-history.php" class="btn-success-primary justify-content-center">
                        <i class="fas fa-list-ul"></i> View All Orders
                    </a>
                    <a href="products.php" class="btn-success-outline justify-content-center">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                </div>
                <p style="font-size:.75rem;color:var(--text-muted);margin-top:1.25rem;margin-bottom:0;">
                    <i class="fas fa-envelope"></i>
                    A confirmation has been sent to your registered email address.
                </p>
            </div>

        </div><!-- /col-lg-5 -->
    </div><!-- /row -->

</div><!-- /container -->
</section>
</main>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand"><i class="fas fa-seedling"></i> <?php echo htmlspecialchars($siteTitle); ?></div>
                <p class="footer-desc">Fresh from local farms to your table.</p>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <p class="footer-heading">Navigate</p>
                <a href="index.php" class="footer-link">Home</a>
                <a href="products.php" class="footer-link">Products</a>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <p class="footer-heading">Account</p>
                <a href="order-history.php" class="footer-link">Orders</a>
                <a href="cart.php" class="footer-link">Cart</a>
            </div>
            <div class="col-lg-4 col-md-6">
                <p class="footer-heading">Contact</p>
                <a href="mailto:support@greenfieldhub.com" class="footer-link">support@greenfieldhub.com</a>
            </div>
        </div>
        <hr class="footer-divider">
        <p class="footer-copy">&copy; 2024 <?php echo htmlspecialchars($siteTitle); ?>. All rights reserved.</p>
    </div>
</footer>

<!-- Confetti canvas -->
<canvas id="confettiCanvas" style="position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;"></canvas>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    // ── Theme / font persistence (identical to checkout.php) ──
    const html      = document.documentElement;
    const THEME_KEY = 'glh_theme';
    const FONT_KEY  = 'glh_font';

    function applyTheme(t) { html.setAttribute('data-theme', t || 'default'); localStorage.setItem(THEME_KEY, t || 'default'); }
    function applyFont(s)  { html.setAttribute('data-font-size', s || 'normal'); localStorage.setItem(FONT_KEY, s || 'normal'); }

    const savedTheme = localStorage.getItem(THEME_KEY);
    const savedFont  = localStorage.getItem(FONT_KEY);
    if (savedTheme) applyTheme(savedTheme);
    if (savedFont)  applyFont(savedFont);

    document.getElementById('btn-theme-dark')?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme') === 'dark' ? 'default' : 'dark'));
    document.getElementById('btn-theme-high')?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme') === 'high-contrast' ? 'default' : 'high-contrast'));
    document.getElementById('btn-theme-low')?.addEventListener('click',  () => applyTheme(html.getAttribute('data-theme') === 'low-contrast'  ? 'default' : 'low-contrast'));
    document.getElementById('btn-font-large')?.addEventListener('click', () => applyFont(html.getAttribute('data-font-size') === 'large' ? 'normal' : 'large'));
    document.getElementById('btn-reset')?.addEventListener('click', () => { applyTheme('default'); applyFont('normal'); });

    // ── Currency switcher ──
    document.querySelectorAll('.currency-option').forEach(el => {
        el.addEventListener('click', function () {
            fetch('set-currency.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'currency=' + encodeURIComponent(this.dataset.currency)
            })
            .then(r => r.json())
            .then(r => { if (r.success) location.reload(); });
        });
    });

    // ── Confetti ──
    const canvas = document.getElementById('confettiCanvas');
    const ctx    = canvas.getContext('2d');
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;

    const colors  = ['#4caf50','#81c784','#ffd54f','#a5d6a7','#ffffff','#66bb6a'];
    const pieces  = [];
    const COUNT   = 80;

    for (let i = 0; i < COUNT; i++) {
        pieces.push({
            x:     Math.random() * canvas.width,
            y:     Math.random() * -canvas.height,
            w:     6 + Math.random() * 8,
            h:     10 + Math.random() * 6,
            color: colors[Math.floor(Math.random() * colors.length)],
            speed: 2 + Math.random() * 3,
            angle: Math.random() * Math.PI * 2,
            spin:  (Math.random() - .5) * .12,
            drift: (Math.random() - .5) * 1.2,
            opacity: 1,
        });
    }

    let frame = 0;
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let alive = false;
        pieces.forEach(p => {
            p.y     += p.speed;
            p.x     += p.drift;
            p.angle += p.spin;
            if (p.y > canvas.height * .8) p.opacity -= .015;
            if (p.opacity <= 0) return;
            alive = true;
            ctx.save();
            ctx.globalAlpha = Math.max(0, p.opacity);
            ctx.translate(p.x, p.y);
            ctx.rotate(p.angle);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        });
        if (alive) requestAnimationFrame(draw);
        else canvas.remove();
    }
    requestAnimationFrame(draw);
    window.addEventListener('resize', () => {
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;
    });
})();
</script>
</body>
</html>