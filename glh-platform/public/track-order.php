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

// Get order ID from URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get order details
$order = $orderObj->getOrderById($orderId);

// Verify order belongs to logged in user or user is admin
if (!$order || ($order['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin')) {
    header('Location: order-history.php');
    exit;
}

// Get order items
$orderItems = $orderObj->getOrderItems($orderId);

// Get order timeline
$orderTimeline = $orderObj->getOrderTimeline($orderId);

// Calculate estimated delivery date (order date + 3-5 days for demo)
$orderDate = new DateTime($order['created_at']);
$estimatedDate = clone $orderDate;
$estimatedDate->modify('+3 days');
$deliveryDate = clone $orderDate;
$deliveryDate->modify('+5 days');

// Order status configuration
$orderStatuses = [
    'pending' => [
        'label' => 'Order Placed',
        'icon' => 'fa-clock',
        'color' => '#ffc107',
        'step' => 1
    ],
    'confirmed' => [
        'label' => 'Confirmed',
        'icon' => 'fa-check-circle',
        'color' => '#2196f3',
        'step' => 2
    ],
    'processing' => [
        'label' => 'Processing',
        'icon' => 'fa-box',
        'color' => '#ff9800',
        'step' => 3
    ],
    'shipped' => [
        'label' => 'Shipped',
        'icon' => 'fa-truck',
        'color' => '#9c27b0',
        'step' => 4
    ],
    'delivered' => [
        'label' => 'Delivered',
        'icon' => 'fa-home',
        'color' => '#4caf50',
        'step' => 5
    ],
    'cancelled' => [
        'label' => 'Cancelled',
        'icon' => 'fa-times-circle',
        'color' => '#f44336',
        'step' => 0
    ]
];

$currentStatus = $order['status'];
$currentStep = isset($orderStatuses[$currentStatus]) ? $orderStatuses[$currentStatus]['step'] : 0;

// Format address - use user's name if shipping_name not available
$shippingName = $order['shipping_name'] ?? $order['customer_name'] ?? $_SESSION['username'] ?? 'Customer';
$shippingPhone = $order['shipping_phone'] ?? $order['customer_phone'] ?? '';
$shippingEmail = $order['shipping_email'] ?? $order['customer_email'] ?? '';

// Format address
$shippingAddress = $order['shipping_address'] ?? '';
if (!empty($order['shipping_city']) || !empty($order['shipping_zip'])) {
    $shippingAddress .= ', ' . ($order['shipping_city'] ?? '') . ' ' . ($order['shipping_zip'] ?? '');
}
if (!empty($order['shipping_country'])) {
    $shippingAddress .= ', ' . $order['shipping_country'];
}

// Get payment method with fallback
$paymentMethod = $order['payment_method'] ?? 'Not specified';
$paymentStatus = $order['payment_status'] ?? 'pending';
$transactionId = $order['transaction_id'] ?? '';

// Get cost breakdown with defaults
$shippingCost = isset($order['shipping_cost']) ? (float)$order['shipping_cost'] : 0;
$taxAmount = isset($order['tax_amount']) ? (float)$order['tax_amount'] : 0;
$discountAmount = isset($order['discount_amount']) ? (float)$order['discount_amount'] : 0;
$totalAmount = isset($order['total_amount']) ? (float)$order['total_amount'] : (isset($order['total_amount_display']) ? (float)$order['total_amount_display'] : 0);
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $orderId; ?> | <?php echo $contentManager->get('site_title', 'Greenfield Local Hub'); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Libs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* =============================================
           CSS CUSTOM PROPERTIES — THEME ENGINE (same as index)
        ============================================= */
        :root,
        [data-theme="default"] {
            --bg-primary:        #0d1f0f;
            --bg-secondary:      #122614;
            --bg-card:           #172d1a;
            --bg-nav:            #0a1a0c;
            --bg-hero:           #0d1f0f;

            --accent-primary:    #4caf50;
            --accent-bright:     #81c784;
            --accent-muted:      #2e7d32;
            --accent-gold:       #ffd54f;
            --accent-gold-dark:  #f9a825;

            --text-primary:      #e8f5e9;
            --text-secondary:    #a5d6a7;
            --text-muted:        #66bb6a;
            --text-inverse:      #0d1f0f;

            --border-color:      #1e3d20;
            --border-subtle:     #1a3320;
            --shadow-card:       0 8px 32px rgba(0,0,0,0.45);
            --shadow-hover:      0 16px 48px rgba(0,0,0,0.6);

            --promo-bg:          #ffd54f;
            --promo-text:        #0d1f0f;
            --badge-bg:          #e53935;
            --badge-text:        #fff;
            --input-bg:          #1a2e1c;
            --input-border:      #2e5e30;
            --focus-ring:        0 0 0 3px rgba(76,175,80,0.45);
        }

        [data-theme="dark"] {
            --bg-primary:        #060e07;
            --bg-secondary:      #0a140b;
            --bg-card:           #0e1f10;
            --bg-nav:            #040a05;
            --bg-hero:           #060e07;
            --accent-primary:    #66bb6a;
            --accent-bright:     #a5d6a7;
            --accent-muted:      #388e3c;
            --text-primary:      #f1f8e9;
            --text-secondary:    #c8e6c9;
            --text-muted:        #81c784;
            --border-color:      #152617;
            --border-subtle:     #122014;
            --input-bg:          #111d12;
            --input-border:      #244826;
        }

        [data-theme="high-contrast"] {
            --bg-primary:        #000000;
            --bg-secondary:      #0a0a0a;
            --bg-card:           #0f0f0f;
            --bg-nav:            #000000;
            --accent-primary:    #00ff44;
            --accent-bright:     #00ff44;
            --accent-muted:      #00cc33;
            --accent-gold:       #ffff00;
            --text-primary:      #ffffff;
            --text-secondary:    #00ff44;
            --text-muted:        #00cc33;
            --border-color:      #00ff44;
            --border-subtle:     #00aa22;
            --shadow-card:       0 0 0 2px #00ff44;
            --focus-ring:        0 0 0 4px #00ff44;
        }

        [data-theme="low-contrast"] {
            --bg-primary:        #1a2e1c;
            --bg-secondary:      #1e3520;
            --bg-card:           #223824;
            --bg-nav:            #182a1a;
            --accent-primary:    #6bab6e;
            --accent-bright:     #8dc490;
            --accent-muted:      #4a8a4d;
            --text-primary:      #c5dbc6;
            --text-secondary:    #9ec4a0;
            --text-muted:        #7aaa7d;
            --border-color:      #2e4e30;
            --border-subtle:     #284530;
            --input-bg:          #243c26;
            --input-border:      #3d6640;
        }

        [data-font-size="large"] {
            font-size: 120% !important;
        }
        [data-font-size="large"] p,
        [data-font-size="large"] .nav-link,
        [data-font-size="large"] .card-text {
            font-size: 1.1rem !important;
        }
        [data-font-size="large"] h1 { font-size: 3.5rem !important; }
        [data-font-size="large"] h2 { font-size: 2.5rem !important; }
        [data-font-size="large"] h5 { font-size: 1.3rem !important; }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            transition: background-color 0.3s, color 0.3s;
        }

        .skip-link {
            position: absolute;
            top: -60px;
            left: 1rem;
            background: var(--accent-primary);
            color: var(--text-inverse);
            padding: 0.5rem 1rem;
            border-radius: 0 0 8px 8px;
            font-weight: 600;
            z-index: 9999;
            transition: top 0.2s;
            text-decoration: none;
        }
        .skip-link:focus { top: 0; }

        .promo-banner {
            background: var(--promo-bg);
            color: var(--promo-text);
            text-align: center;
            padding: 10px 1rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Accessibility Toolbar */
        .a11y-toolbar {
            background: var(--bg-nav);
            border-bottom: 1px solid var(--border-color);
            padding: 6px 0;
        }
        .a11y-toolbar .container {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .a11y-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            font-weight: 600;
        }
        .a11y-btn {
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .a11y-btn:hover,
        .a11y-btn.active {
            background: var(--accent-primary);
            color: var(--text-inverse);
            border-color: var(--accent-primary);
        }
        .a11y-divider {
            width: 1px;
            height: 20px;
            background: var(--border-color);
            margin: 0 4px;
        }

        /* Navbar */
        .navbar {
            background: var(--bg-nav) !important;
            border-bottom: 1px solid var(--border-color);
            padding: 0.9rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(12px);
        }
        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--accent-bright) !important;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .navbar-brand .brand-leaf {
            width: 32px;
            height: 32px;
            background: var(--accent-muted);
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .navbar-brand .brand-leaf i {
            transform: rotate(45deg);
            font-size: 14px;
            color: white;
        }
        .nav-link {
            color: var(--text-secondary) !important;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.4rem 0.75rem !important;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-link:hover {
            color: var(--accent-bright) !important;
            background: rgba(76,175,80,0.1);
        }
        .dropdown-menu {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: var(--shadow-card);
        }
        .dropdown-item {
            color: var(--text-secondary);
            border-radius: 7px;
        }
        .dropdown-item:hover {
            background: rgba(76,175,80,0.12);
            color: var(--accent-bright);
        }

        /* Breadcrumb */
        .breadcrumb-wrapper {
            background: var(--bg-secondary);
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-subtle);
        }
        .breadcrumb {
            margin: 0;
            background: transparent;
        }
        .breadcrumb-item {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        .breadcrumb-item a {
            color: var(--text-secondary);
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            color: var(--accent-bright);
        }
        .breadcrumb-item.active {
            color: var(--accent-bright);
        }
        .breadcrumb-item + .breadcrumb-item::before {
            color: var(--text-muted);
            content: "›";
        }

        /* Order Tracking Main Section */
        .tracking-section {
            padding: 3rem 0 5rem;
            background: var(--bg-primary);
        }

        /* Order Header Card */
        .order-header-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .order-number {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-bright);
        }
        .order-date {
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-badge i {
            font-size: 0.9rem;
        }

        /* Progress Tracker */
        .progress-tracker {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
        }
        .tracker-step {
            position: relative;
            text-align: center;
            flex: 1;
        }
        .step-icon {
            width: 50px;
            height: 50px;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            transition: all 0.3s;
            position: relative;
            z-index: 2;
        }
        .step-icon i {
            font-size: 1.2rem;
            color: var(--text-muted);
        }
        .step-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }
        .step-date {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 5px;
        }
        /* Completed steps */
        .tracker-step.completed .step-icon {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
        }
        .tracker-step.completed .step-icon i {
            color: white;
        }
        .tracker-step.completed .step-label {
            color: var(--accent-bright);
        }
        /* Current step */
        .tracker-step.current .step-icon {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 4px rgba(76,175,80,0.3);
            animation: pulse 2s infinite;
        }
        .tracker-step.current .step-icon i {
            color: white;
        }
        .tracker-step.current .step-label {
            color: var(--accent-bright);
            font-weight: 700;
        }
        /* Connector lines */
        .tracker-connector {
            position: absolute;
            top: 25px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: var(--border-color);
            z-index: 1;
        }
        .tracker-connector.active {
            background: var(--accent-primary);
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(76,175,80,0.4); }
            50% { box-shadow: 0 0 0 8px rgba(76,175,80,0); }
        }

        /* Order Details Grid */
        .info-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.25rem;
            height: 100%;
        }
        .info-card-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--accent-gold);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-card-content {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        .info-card-content p {
            margin-bottom: 0.5rem;
        }

        /* Order Items Table */
        .items-table {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
        }
        .items-table .table {
            margin: 0;
            color: var(--text-secondary);
        }
        .items-table .table thead th {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .items-table .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom-color: var(--border-subtle);
        }
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        .total-row {
            background: var(--bg-secondary);
            font-weight: 600;
        }
        .total-row td {
            border-bottom: none !important;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .btn-primary-custom {
            background: var(--accent-primary);
            color: var(--text-inverse);
            border: none;
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-primary-custom:hover {
            background: var(--accent-bright);
            transform: translateY(-2px);
            color: var(--text-inverse);
        }
        .btn-outline-custom {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-outline-custom:hover {
            border-color: var(--accent-primary);
            color: var(--accent-bright);
        }

        /* Timeline */
        .timeline-section {
            margin-top: 2rem;
        }
        .timeline-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-left: 2px solid var(--border-color);
            margin-left: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 20px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--border-color);
        }
        .timeline-item.completed::before {
            background: var(--accent-primary);
        }
        .timeline-icon {
            width: 36px;
            height: 36px;
            background: var(--bg-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .timeline-content {
            flex: 1;
        }
        .timeline-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .timeline-date {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .timeline-desc {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Footer */
        footer {
            background: var(--bg-nav);
            border-top: 1px solid var(--border-color);
            padding: 3rem 0 2rem;
        }
        .footer-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent-bright);
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.5rem;
        }
        .footer-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.7;
        }
        .footer-heading {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        .footer-link {
            display: block;
            font-size: 0.85rem;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 0.5rem;
            transition: color 0.2s;
        }
        .footer-link:hover { color: var(--accent-bright); }
        .footer-divider {
            border: none;
            border-top: 1px solid var(--border-color);
            margin: 2rem 0 1.5rem;
        }
        .footer-copy {
            font-size: 0.78rem;
            color: var(--text-muted);
            text-align: center;
        }

        .text-accent { color: var(--accent-bright); }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--accent-muted); border-radius: 4px; }

        @media (max-width: 767px) {
            .tracker-step .step-label { font-size: 0.6rem; }
            .step-icon { width: 40px; height: 40px; }
            .step-icon i { font-size: 1rem; }
            .tracker-connector { top: 20px; }
            .order-number { font-size: 1.2rem; }
        }

        @media (max-width: 575px) {
            .progress-tracker { padding: 1.5rem 0.5rem; }
            .step-label { display: none; }
            .step-date { display: none; }
            .tracker-connector { top: 20px; }
        }

        *:focus { outline: none; }
        *:focus-visible { box-shadow: var(--focus-ring) !important; border-radius: 4px; }
    </style>
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<?php if ($contentManager->get('promo_banner')): ?>
<div class="promo-banner" role="banner">
    <i class="fas fa-tag" aria-hidden="true"></i>
    <?php echo $contentManager->render('promo_banner'); ?>
</div>
<?php endif; ?>

<!-- Accessibility Toolbar -->
<div class="a11y-toolbar" role="toolbar" aria-label="Accessibility options">
    <div class="container">
        <span class="a11y-label" aria-hidden="true"><i class="fas fa-universal-access"></i> Accessibility:</span>
        <button class="a11y-btn" id="btn-theme-dark" aria-pressed="false"><i class="fas fa-moon"></i> Dark</button>
        <div class="a11y-divider"></div>
        <button class="a11y-btn" id="btn-theme-high" aria-pressed="false"><i class="fas fa-adjust"></i> High Contrast</button>
        <button class="a11y-btn" id="btn-theme-low" aria-pressed="false"><i class="fas fa-circle-half-stroke"></i> Low Contrast</button>
        <div class="a11y-divider"></div>
        <button class="a11y-btn" id="btn-font-large" aria-pressed="false"><i class="fas fa-text-height"></i> Large Text</button>
        <button class="a11y-btn" id="btn-reset"><i class="fas fa-rotate-left"></i> Reset</button>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <div class="brand-leaf" aria-hidden="true"><i class="fas fa-seedling"></i></div>
            <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
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

<!-- Breadcrumb -->
<div class="breadcrumb-wrapper">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="order-history.php">My Orders</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order #<?php echo $orderId; ?></li>
            </ol>
        </nav>
    </div>
</div>

<main id="main-content">
<section class="tracking-section">
    <div class="container">

        <!-- Order Header -->
        <div class="order-header-card">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="order-number">Order #<?php echo str_pad($orderId, 8, '0', STR_PAD_LEFT); ?></div>
                    <div class="order-date">Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <?php
                    $statusInfo = $orderStatuses[$currentStatus];
                    $statusColor = $statusInfo['color'];
                    ?>
                    <span class="status-badge" style="background: <?php echo $statusColor; ?>20; color: <?php echo $statusColor; ?>; border: 1px solid <?php echo $statusColor; ?>40;">
                        <i class="fas <?php echo $statusInfo['icon']; ?>"></i>
                        <?php echo ucfirst($currentStatus); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Progress Tracker -->
        <div class="progress-tracker">
            <div class="row position-relative">
                <?php
                $statusSteps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                $stepLabels = ['Order Placed', 'Confirmed', 'Processing', 'Shipped', 'Delivered'];
                $stepIcons = ['fa-clock', 'fa-check-circle', 'fa-box', 'fa-truck', 'fa-home'];
                
                foreach ($statusSteps as $index => $step):
                    $stepNumber = $index + 1;
                    $isCompleted = $currentStep > $stepNumber;
                    $isCurrent = $currentStep == $stepNumber;
                    $stepClass = '';
                    if ($isCompleted) $stepClass = 'completed';
                    if ($isCurrent) $stepClass = 'current';
                    
                    // Get date for this status from timeline
                    $stepDate = '';
                    foreach ($orderTimeline as $timeline) {
                        if ($timeline['status'] == $step) {
                            $stepDate = date('M j', strtotime($timeline['created_at']));
                            break;
                        }
                    }
                    ?>
                    <div class="col tracker-step <?php echo $stepClass; ?>">
                        <?php if ($index < 4): ?>
                            <div class="tracker-connector <?php echo ($currentStep > $stepNumber) ? 'active' : ''; ?>" style="left: 0; width: 100%;"></div>
                        <?php endif; ?>
                        <div class="step-icon">
                            <i class="fas <?php echo $stepIcons[$index]; ?>"></i>
                        </div>
                        <div class="step-label"><?php echo $stepLabels[$index]; ?></div>
                        <?php if ($stepDate): ?>
                            <div class="step-date"><?php echo $stepDate; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Shipping Information -->
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-card-title">
                        <i class="fas fa-truck"></i> Shipping Information
                    </div>
                    <div class="info-card-content">
                        <p><strong><?php echo htmlspecialchars($shippingName); ?></strong></p>
                        <p><?php echo nl2br(htmlspecialchars($shippingAddress ?: 'No address provided')); ?></p>
                        <?php if (!empty($shippingPhone)): ?>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($shippingPhone); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($shippingEmail)): ?>
                            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($shippingEmail); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="col-md-6">
                <div class="info-card">
                    <div class="info-card-title">
                        <i class="fas fa-credit-card"></i> Payment Information
                    </div>
                    <div class="info-card-content">
                        <p><strong>Method:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $paymentMethod))); ?></p>
                        <p><strong>Status:</strong> 
                            <span style="color: <?php echo $paymentStatus == 'completed' ? '#4caf50' : '#ffc107'; ?>">
                                <?php echo ucfirst($paymentStatus); ?>
                            </span>
                        </p>
                        <?php if (!empty($transactionId)): ?>
                            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($transactionId); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="mt-4">
            <div class="info-card-title">
                <i class="fas fa-boxes"></i> Order Items
            </div>
            <div class="items-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        foreach ($orderItems as $item):
                            $itemPrice = isset($item['price']) ? (float)$item['price'] : (isset($item['unit_price_display']) ? (float)$item['unit_price_display'] : 0);
                            $itemTotal = $itemPrice * $item['quantity'];
                            $subtotal += $itemTotal;
                            $itemPriceFormatted = $currentCurrency['currency_symbol'] . number_format($itemPrice, 2);
                            $itemTotalFormatted = $currentCurrency['currency_symbol'] . number_format($itemTotal, 2);
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php 
                                    $itemImage = !empty($item['image_url']) ? '../' . $item['image_url'] : 'assets/images/default-product.jpg';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($itemImage); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-img" onerror="this.src='assets/images/default-product.jpg'">
                                    <div>
                                        <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['producer_name'] ?? 'Local Farm'); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end"><?php echo $itemPriceFormatted; ?></td>
                            <td class="text-end"><?php echo $itemTotalFormatted; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                            <td class="text-end"><?php echo $currentCurrency['currency_symbol'] . number_format($subtotal, 2); ?></td>
                        </tr>
                        <?php if ($shippingCost > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Shipping</strong></td>
                            <td class="text-end"><?php echo $currentCurrency['currency_symbol'] . number_format($shippingCost, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($taxAmount > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Tax</strong></td>
                            <td class="text-end"><?php echo $currentCurrency['currency_symbol'] . number_format($taxAmount, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($discountAmount > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Discount</strong></td>
                            <td class="text-end">-<?php echo $currentCurrency['currency_symbol'] . number_format($discountAmount, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-row">
                            <td colspan="3" class="text-end"><strong>Total</strong></td>
                            <td class="text-end"><strong><?php echo $currentCurrency['currency_symbol'] . number_format($totalAmount, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Order Timeline -->
        <?php if (!empty($orderTimeline)): ?>
        <div class="timeline-section">
            <div class="info-card-title">
                <i class="fas fa-history"></i> Order Timeline
            </div>
            <div class="info-card">
                <?php foreach ($orderTimeline as $timeline):
                    $timelineStatus = $orderStatuses[$timeline['status']] ?? null;
                ?>
                <div class="timeline-item completed">
                    <div class="timeline-icon" style="background: <?php echo $timelineStatus['color'] ?? '#4caf50'; ?>20;">
                        <i class="fas <?php echo $timelineStatus['icon'] ?? 'fa-info-circle'; ?>" style="color: <?php echo $timelineStatus['color'] ?? '#4caf50'; ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title"><?php echo ucfirst($timeline['status']); ?></div>
                        <div class="timeline-date"><?php echo date('F j, Y \a\t g:i A', strtotime($timeline['created_at'])); ?></div>
                        <?php if (!empty($timeline['notes'])): ?>
                            <div class="timeline-desc"><?php echo htmlspecialchars($timeline['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estimated Delivery -->
        <?php if ($currentStatus != 'delivered' && $currentStatus != 'cancelled'): ?>
        <div class="info-card mt-4" style="background: rgba(76,175,80,0.1); border-color: var(--accent-muted);">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-calendar-check fa-2x" style="color: var(--accent-bright);"></i>
                <div>
                    <strong>Estimated Delivery:</strong> 
                    <?php echo $estimatedDate->format('l, F j, Y'); ?> - 
                    <?php echo $deliveryDate->format('l, F j, Y'); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="order-history.php" class="btn-outline-custom">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <?php if ($currentStatus == 'delivered'): ?>
                <a href="write-review.php?order_id=<?php echo $orderId; ?>" class="btn-primary-custom">
                    <i class="fas fa-star"></i> Write a Review
                </a>
            <?php endif; ?>
            <?php if ($currentStatus == 'pending'): ?>
                <button id="cancelOrderBtn" class="btn-outline-custom" style="border-color: #f44336; color: #f44336;">
                    <i class="fas fa-ban"></i> Cancel Order
                </button>
            <?php endif; ?>
            <a href="products.php" class="btn-primary-custom">
                <i class="fas fa-shopping-cart"></i> Shop More
            </a>
        </div>

    </div>
</section>
</main>

<!-- Footer -->
<footer role="contentinfo">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <i class="fas fa-seedling"></i>
                    <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?>
                </div>
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
                <a href="mailto:<?php echo htmlspecialchars($contentManager->get('contact_email','support@greenfieldhub.com')); ?>" class="footer-link">
                    <?php echo htmlspecialchars($contentManager->get('contact_email','support@greenfieldhub.com')); ?>
                </a>
                <p class="footer-desc mt-2"><?php echo htmlspecialchars($contentManager->get('contact_phone', '+1 (555) 123-4567')); ?></p>
            </div>
        </div>
        <hr class="footer-divider">
        <p class="footer-copy">&copy; 2024 <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?>. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function() {
    'use strict';

    // Accessibility
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

    // Currency Switcher
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

    // Cancel Order
    const cancelBtn = document.getElementById('cancelOrderBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            Swal.fire({
                title: 'Cancel Order?',
                text: 'Are you sure you want to cancel this order? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#2e7d32',
                confirmButtonText: 'Yes, cancel order',
                cancelButtonText: 'No, keep it',
                background: '#172d1a',
                color: '#e8f5e9'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../ajax/cancel-order.php',
                        method: 'POST',
                        data: { order_id: <?php echo $orderId; ?> },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Order Cancelled',
                                    text: 'Your order has been cancelled successfully.',
                                    icon: 'success',
                                    background: '#172d1a',
                                    color: '#e8f5e9'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: response.message || 'Failed to cancel order',
                                    icon: 'error',
                                    background: '#172d1a',
                                    color: '#e8f5e9'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error',
                                text: 'Failed to cancel order',
                                icon: 'error',
                                background: '#172d1a',
                                color: '#e8f5e9'
                            });
                        }
                    });
                }
            });
        });
    }
})();
</script>
</body>
</html>