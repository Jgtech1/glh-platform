<?php
session_start();
require_once '../classes/User.php';
require_once '../classes/Cart.php';
require_once '../classes/Order.php';
require_once '../classes/Product.php';
require_once '../classes/ContentManager.php';
require_once '../classes/CurrencyManager.php';

$userObj        = new User();
$cartObj        = new Cart();
$orderObj       = new Order();
$productObj     = new Product();
$contentManager = new ContentManager();
$currencyManager = new CurrencyManager();

// Redirect if not logged in or not a customer
if (!$userObj->isLoggedIn() || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit;
}

$currentCurrency = $currencyManager->getCurrentCurrency();
$userId          = $_SESSION['user_id'];

// Get cart items
$cartItems = $cartObj->getCartItems($userId);

// Redirect if cart is empty
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// ---------------------------------------------------------------------------
// Calculate totals
// ---------------------------------------------------------------------------
$subtotal = 0;
foreach ($cartItems as $item) {
    $itemPrice = isset($item['price'])
        ? $item['price']
        : (isset($item['base_price']) ? $item['base_price'] : 0);
    $subtotal += $itemPrice * $item['quantity'];
}

$shippingCost   = 5.00;   // flat rate
$taxRate        = 0.08;   // 8 %
$taxAmount      = $subtotal * $taxRate;
$discountAmount = 0;

// ---------------------------------------------------------------------------
// Loyalty points
// ---------------------------------------------------------------------------
$userLoyaltyPoints    = $orderObj->getUserLoyaltyPoints($userId);
$loyaltyPointsToUse   = 0;
$loyaltyDiscount      = 0;

// ---------------------------------------------------------------------------
// Process form submission
// ---------------------------------------------------------------------------
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitise inputs
    $fullName      = trim($_POST['full_name']      ?? '');
    $email         = trim($_POST['email']          ?? '');
    $phone         = trim($_POST['phone']          ?? '');
    $address       = trim($_POST['address']        ?? '');
    $city          = trim($_POST['city']           ?? '');
    $zip           = trim($_POST['zip']            ?? '');
    $country       = trim($_POST['country']        ?? 'USA');
    $paymentMethod = $_POST['payment_method']      ?? 'cod';
    $notes         = trim($_POST['notes']          ?? '');

    // How many loyalty points the customer wants to redeem
    $loyaltyPointsToUse = max(0, (int) ($_POST['loyalty_points_used'] ?? 0));

    // Cap redemption to what the user actually has and to 50 % of order total
    $rawTotal    = $subtotal + $shippingCost + $taxAmount;
    $maxRedeem   = $orderObj->getMaxRedeemablePoints($userLoyaltyPoints, $rawTotal);
    $loyaltyPointsToUse = min($loyaltyPointsToUse, $maxRedeem);
    $loyaltyDiscount    = $orderObj->calculatePointsDiscount($loyaltyPointsToUse);

    $totalAmount = $rawTotal - $loyaltyDiscount - $discountAmount;
    $totalAmount = max(0.01, $totalAmount); // never zero

    // Validation
    if (empty($fullName) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($zip)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {

        $orderData = [
            'user_id'             => $userId,
            'shipping_name'       => $fullName,
            'shipping_email'      => $email,
            'shipping_phone'      => $phone,
            'shipping_address'    => $address,
            'shipping_city'       => $city,
            'shipping_zip'        => $zip,
            'shipping_country'    => $country,
            'subtotal'            => $subtotal,
            'shipping_cost'       => $shippingCost,
            'tax_amount'          => $taxAmount,
            'discount_amount'     => $discountAmount,
            'total_amount'        => $totalAmount,
            'payment_method'      => $paymentMethod,
            'payment_status'      => 'pending',
            'order_notes'         => $notes,
            'loyalty_points_used' => $loyaltyPointsToUse,
        ];

        $orderId = $orderObj->createOrder($orderData, $cartItems);

        if ($orderId) {
            $cartObj->clearCart($userId);
            header('Location: order-success.php?order_id=' . $orderId);
            exit;
        } else {
            $error = 'Failed to place order. Please try again. '
                   . 'If this keeps happening, please contact support.';
        }
    }
}

// Recalculate totals for display (reflects any loyalty discount entered so far)
$rawTotal        = $subtotal + $shippingCost + $taxAmount;
$maxRedeem       = $orderObj->getMaxRedeemablePoints($userLoyaltyPoints, $rawTotal);
$loyaltyDiscount = $orderObj->calculatePointsDiscount($loyaltyPointsToUse);
$totalAmount     = max(0.01, $rawTotal - $loyaltyDiscount - $discountAmount);

$siteTitle = $contentManager->get('site_title', 'Greenfield Local Hub');
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | <?php echo htmlspecialchars($siteTitle); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ---------- design tokens ---------- */
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

        /* promo */
        .promo-banner {
            background:var(--promo-bg); color:var(--text-inverse);
            text-align:center; padding:10px 1rem; font-size:.9rem; font-weight:600;
        }

        /* a11y toolbar */
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

        /* navbar */
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

        /* breadcrumb */
        .breadcrumb-wrapper { background:var(--bg-secondary); padding:.75rem 0; border-bottom:1px solid var(--border-subtle); }
        .breadcrumb { margin:0; background:transparent; }
        .breadcrumb-item { color:var(--text-muted); font-size:.8rem; }
        .breadcrumb-item a { color:var(--text-secondary); text-decoration:none; }
        .breadcrumb-item a:hover { color:var(--accent-bright); }
        .breadcrumb-item.active { color:var(--accent-bright); }
        .breadcrumb-item + .breadcrumb-item::before { color:var(--text-muted); content:"›"; }

        /* layout */
        .checkout-section { padding:3rem 0 5rem; }
        .section-header { margin-bottom:2rem; }
        .section-header h1 { font-family:'Playfair Display',serif; font-size:clamp(1.8rem,3vw,2.2rem); font-weight:700; }

        /* cards */
        .form-card, .order-summary {
            background:var(--bg-card); border:1px solid var(--border-color);
            border-radius:20px; padding:1.5rem;
        }
        .form-card-title, .summary-title {
            font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:700;
            color:var(--text-primary); margin-bottom:1.25rem;
            padding-bottom:.75rem; border-bottom:1px solid var(--border-subtle);
        }

        /* form elements */
        .form-label { color:var(--text-secondary); font-size:.85rem; font-weight:500; margin-bottom:.4rem; }
        .form-control, .form-select {
            background:var(--input-bg); border:1px solid var(--input-border);
            color:var(--text-primary); border-radius:10px; padding:.7rem 1rem; font-size:.9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color:var(--accent-primary); box-shadow:var(--focus-ring);
            background:var(--input-bg); color:var(--text-primary);
        }
        textarea.form-control { resize:vertical; min-height:80px; }

        /* order summary */
        .summary-item {
            display:flex; justify-content:space-between;
            padding:.75rem 0; border-bottom:1px solid var(--border-subtle);
        }
        .summary-item:last-of-type { border-bottom:none; }
        .summary-total {
            font-size:1.2rem; font-weight:700; color:var(--accent-bright);
            margin-top:1rem; padding-top:1rem; border-top:2px solid var(--accent-primary);
            display:flex; justify-content:space-between;
        }
        .discount-row { color:var(--accent-gold); }

        /* cart item preview */
        .cart-item-preview {
            display:flex; gap:1rem; padding:.75rem 0;
            border-bottom:1px solid var(--border-subtle); align-items:center;
        }
        .cart-item-preview:last-child { border-bottom:none; }
        .cart-item-img {
            width:50px; height:50px; object-fit:cover; border-radius:8px;
            background:var(--bg-secondary); flex-shrink:0;
        }
        /* ------------------------------------------------------------------ */
        /* placeholder SVG shown when a product has no image or image 404s     */
        /* ------------------------------------------------------------------ */
        .cart-item-img-placeholder {
            width:50px; height:50px; border-radius:8px; flex-shrink:0;
            background:var(--bg-secondary); display:flex; align-items:center;
            justify-content:center; color:var(--text-muted); font-size:1.2rem;
        }
        .cart-item-details { flex:1; min-width:0; }
        .cart-item-name { font-weight:600; font-size:.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .cart-item-meta { font-size:.7rem; color:var(--text-muted); }
        .cart-item-price { font-weight:600; color:var(--accent-bright); white-space:nowrap; }

        /* loyalty panel */
        .loyalty-panel {
            background:rgba(255,213,79,.07); border:1px solid rgba(255,213,79,.25);
            border-radius:12px; padding:1rem 1.25rem; margin-top:1rem;
        }
        .loyalty-panel .loyalty-title {
            font-weight:700; color:var(--accent-gold);
            display:flex; align-items:center; gap:8px; margin-bottom:.5rem;
        }
        .loyalty-balance { font-size:.82rem; color:var(--text-muted); margin-bottom:.75rem; }
        .loyalty-input-row { display:flex; gap:.5rem; align-items:center; }
        .loyalty-input-row .form-control { max-width:120px; }
        .btn-apply-points {
            background:var(--accent-gold); color:#1a1a00; border:none;
            border-radius:8px; padding:.45rem .9rem; font-size:.82rem; font-weight:700;
            cursor:pointer; white-space:nowrap;
        }
        .btn-apply-points:hover { opacity:.88; }
        .loyalty-applied-note { font-size:.78rem; color:var(--accent-gold); margin-top:.4rem; }

        /* payment options */
        .payment-methods { display:flex; flex-direction:column; gap:.75rem; }
        .payment-option {
            display:flex; align-items:center; gap:1rem;
            padding:1rem; background:var(--bg-secondary);
            border:1px solid var(--border-color); border-radius:12px;
            cursor:pointer; transition:all .2s;
        }
        .payment-option:hover { border-color:var(--accent-primary); }
        .payment-option.selected { border-color:var(--accent-primary); background:rgba(76,175,80,.1); }
        .payment-option input { margin:0; }
        .payment-option .payment-icon { font-size:1.5rem; color:var(--accent-bright); }
        .payment-option .payment-name { font-weight:600; }

        /* CTA */
        .btn-place-order {
            width:100%; background:var(--accent-primary); color:var(--text-inverse);
            border:none; border-radius:50px; padding:1rem;
            font-size:1rem; font-weight:700; transition:all .2s; margin-top:1rem;
        }
        .btn-place-order:hover { background:var(--accent-bright); transform:translateY(-2px); }
        .btn-back-cart {
            background:transparent; color:var(--text-secondary);
            border:1px solid var(--border-color); border-radius:50px;
            padding:.75rem 1.5rem; text-decoration:none;
            display:inline-flex; align-items:center; gap:8px;
        }
        .btn-back-cart:hover { border-color:var(--accent-primary); color:var(--accent-bright); }

        /* alerts */
        .error-alert {
            background:rgba(244,67,54,.15); border:1px solid #f44336; color:#ff8a80;
            border-radius:12px; padding:1rem; margin-bottom:1.5rem;
        }

        /* footer */
        footer { background:var(--bg-nav); border-top:1px solid var(--border-color); padding:3rem 0 2rem; }
        .footer-brand { font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:700; color:var(--accent-bright); display:flex; align-items:center; gap:8px; }
        .footer-desc { font-size:.85rem; color:var(--text-muted); }
        .footer-heading { font-size:.72rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); margin-bottom:1rem; }
        .footer-link { display:block; font-size:.85rem; color:var(--text-secondary); text-decoration:none; margin-bottom:.5rem; }
        .footer-link:hover { color:var(--accent-bright); }
        .footer-divider { border-top:1px solid var(--border-color); margin:2rem 0; }
        .footer-copy { font-size:.78rem; color:var(--text-muted); text-align:center; }

        @media(max-width:767px) { .form-card, .order-summary { margin-bottom:1.5rem; } }
        *:focus { outline:none; }
        *:focus-visible { box-shadow:var(--focus-ring) !important; border-radius:4px; }
    </style>
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<?php if ($contentManager->get('promo_banner')): ?>
<div class="promo-banner"><i class="fas fa-tag"></i> <?php echo $contentManager->render('promo_banner'); ?></div>
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
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>
    </div>
</div>

<!-- ========== MAIN ========== -->
<main id="main-content">
<section class="checkout-section">
    <div class="container">

        <div class="section-header">
            <h1>Checkout</h1>
        </div>

        <?php if ($error): ?>
        <div class="error-alert">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="checkoutForm">

            <!-- Hidden field carries the resolved loyalty points into POST -->
            <input type="hidden" name="loyalty_points_used" id="loyalty_points_used_field" value="<?php echo $loyaltyPointsToUse; ?>">

            <div class="row g-4">

                <!-- ====== LEFT: Shipping + Payment ====== -->
                <div class="col-lg-7">

                    <!-- Shipping information -->
                    <div class="form-card">
                        <div class="form-card-title">
                            <i class="fas fa-truck"></i> Shipping Information
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? $_SESSION['full_name'] ?? ''); ?>"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Street Address *</label>
                                <input type="text" name="address" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City *</label>
                                <input type="text" name="city" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>"
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">ZIP Code *</label>
                                <input type="text" name="zip" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['zip'] ?? ''); ?>"
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country *</label>
                                <select name="country" class="form-select" required>
                                    <?php
                                    $countries = ['USA'=>'United States','Canada'=>'Canada','UK'=>'United Kingdom','Australia'=>'Australia'];
                                    $selCountry = $_POST['country'] ?? 'USA';
                                    foreach ($countries as $val => $label):
                                    ?>
                                    <option value="<?php echo $val; ?>" <?php echo $selCountry === $val ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Order Notes (Optional)</label>
                                <textarea name="notes" class="form-control"
                                          placeholder="Special delivery instructions or notes..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment method -->
                    <div class="form-card mt-4">
                        <div class="form-card-title">
                            <i class="fas fa-credit-card"></i> Payment Method
                        </div>
                        <div class="payment-methods">
                            <label class="payment-option selected">
                                <input type="radio" name="payment_method" value="cod" checked hidden>
                                <div class="payment-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div>
                                    <div class="payment-name">Cash on Delivery</div>
                                    <div class="text-muted small">Pay when you receive your order</div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="card" hidden>
                                <div class="payment-icon"><i class="fab fa-cc-visa"></i></div>
                                <div>
                                    <div class="payment-name">Credit / Debit Card</div>
                                    <div class="text-muted small">Visa, Mastercard, Amex</div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="paypal" hidden>
                                <div class="payment-icon"><i class="fab fa-paypal"></i></div>
                                <div>
                                    <div class="payment-name">PayPal</div>
                                    <div class="text-muted small">Secure online payment</div>
                                </div>
                            </label>
                        </div>
                    </div>

                </div><!-- /col-lg-7 -->

                <!-- ====== RIGHT: Order summary ====== -->
                <div class="col-lg-5">
                    <div class="order-summary">

                        <div class="summary-title">
                            <i class="fas fa-shopping-bag"></i> Order Summary
                        </div>

                        <!-- Cart items preview -->
                        <div class="cart-items-preview mb-2">
                            <?php foreach ($cartItems as $item):
                                $itemPrice = isset($item['price'])
                                    ? $item['price']
                                    : (isset($item['base_price']) ? $item['base_price'] : 0);
                                $lineTotal = $itemPrice * $item['quantity'];
                                $hasImage  = !empty($item['image_url']);
                                // Build a safe src – prepend ../ only when the path doesn't already start with http
                                $imgSrc = $hasImage
                                    ? (strpos($item['image_url'], 'http') === 0
                                        ? $item['image_url']
                                        : '../' . ltrim($item['image_url'], '/'))
                                    : null;
                            ?>
                            <div class="cart-item-preview">
                                <?php if ($imgSrc): ?>
                                    <!--
                                        FIX: onerror replaces the broken src with a transparent 1×1 pixel
                                        data URI, then sets this.onerror=null to break the infinite loop.
                                        The placeholder icon div is shown via JS after the swap.
                                    -->
                                    <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="cart-item-img"
                                         onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="cart-item-img-placeholder" style="display:none;">
                                        <i class="fas fa-leaf"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="cart-item-img-placeholder">
                                        <i class="fas fa-leaf"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="cart-item-details">
                                    <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="cart-item-meta">
                                        Qty: <?php echo (int)$item['quantity']; ?>
                                        <?php if (!empty($item['unit'])): ?>
                                            &nbsp;·&nbsp;<?php echo htmlspecialchars($item['unit']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="cart-item-price">
                                    <?php echo htmlspecialchars($currentCurrency['currency_symbol'])
                                             . number_format($lineTotal, 2); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Loyalty points panel (only shown when user has points) -->
                        <?php if ($userLoyaltyPoints > 0): ?>
                        <div class="loyalty-panel">
                            <div class="loyalty-title">
                                <i class="fas fa-star"></i> Loyalty Points
                            </div>
                            <div class="loyalty-balance">
                                You have <strong><?php echo number_format($userLoyaltyPoints); ?></strong> points
                                (max redeemable: <strong id="maxRedeemDisplay"><?php echo number_format($maxRedeem); ?></strong>
                                = <?php echo htmlspecialchars($currentCurrency['currency_symbol']); ?><strong id="maxDiscountDisplay"><?php echo number_format($orderObj->calculatePointsDiscount($maxRedeem), 2); ?></strong> off)
                            </div>
                            <div class="loyalty-input-row">
                                <input type="number" id="loyaltyPointsInput" class="form-control"
                                       placeholder="Points to use" min="0"
                                       max="<?php echo $maxRedeem; ?>"
                                       value="<?php echo $loyaltyPointsToUse > 0 ? $loyaltyPointsToUse : ''; ?>">
                                <button type="button" class="btn-apply-points" id="applyPointsBtn">
                                    <i class="fas fa-check"></i> Apply
                                </button>
                                <?php if ($loyaltyPointsToUse > 0): ?>
                                <button type="button" class="btn-apply-points" id="removePointsBtn"
                                        style="background:#f44336;color:#fff;">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="loyalty-applied-note" id="loyaltyAppliedNote"
                                 style="<?php echo $loyaltyPointsToUse > 0 ? '' : 'display:none;'; ?>">
                                <i class="fas fa-check-circle"></i>
                                <span id="loyaltyAppliedText">
                                    <?php if ($loyaltyPointsToUse > 0): ?>
                                        <?php echo number_format($loyaltyPointsToUse); ?> points applied
                                        (<?php echo htmlspecialchars($currentCurrency['currency_symbol']); ?>
                                        <?php echo number_format($loyaltyDiscount, 2); ?> off)
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Totals -->
                        <div class="summary-item mt-3">
                            <span>Subtotal</span>
                            <span><?php echo htmlspecialchars($currentCurrency['currency_symbol']) . number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Shipping</span>
                            <span><?php echo htmlspecialchars($currentCurrency['currency_symbol']) . number_format($shippingCost, 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span>Tax (8%)</span>
                            <span><?php echo htmlspecialchars($currentCurrency['currency_symbol']) . number_format($taxAmount, 2); ?></span>
                        </div>
                        <?php if ($discountAmount > 0): ?>
                        <div class="summary-item discount-row">
                            <span>Discount</span>
                            <span>-<?php echo htmlspecialchars($currentCurrency['currency_symbol']) . number_format($discountAmount, 2); ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Loyalty discount row – updated live by JS -->
                        <div class="summary-item discount-row" id="loyaltyDiscountRow"
                             style="<?php echo $loyaltyPointsToUse > 0 ? '' : 'display:none;'; ?>">
                            <span><i class="fas fa-star"></i> Loyalty Discount</span>
                            <span id="loyaltyDiscountDisplay">
                                -<?php echo htmlspecialchars($currentCurrency['currency_symbol'])
                                          . number_format($loyaltyDiscount, 2); ?>
                            </span>
                        </div>

                        <div class="summary-total" id="orderTotalRow">
                            <span>Total</span>
                            <span id="orderTotalDisplay">
                                <?php echo htmlspecialchars($currentCurrency['currency_symbol'])
                                         . number_format($totalAmount, 2); ?>
                            </span>
                        </div>

                        <button type="submit" class="btn-place-order">
                            <i class="fas fa-check-circle"></i> Place Order
                        </button>
                        <div class="text-center mt-3">
                            <a href="cart.php" class="btn-back-cart">
                                <i class="fas fa-arrow-left"></i> Back to Cart
                            </a>
                        </div>

                    </div><!-- /order-summary -->
                </div><!-- /col-lg-5 -->

            </div><!-- /row -->
        </form>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    // ----------------------------------------------------------------
    // Theme / font-size persistence
    // ----------------------------------------------------------------
    const html     = document.documentElement;
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

    // ----------------------------------------------------------------
    // Currency switcher
    // ----------------------------------------------------------------
    document.querySelectorAll('.currency-option').forEach(el => {
        el.addEventListener('click', function () {
            $.ajax({
                url: 'set-currency.php',
                method: 'POST',
                data: { currency: this.dataset.currency },
                dataType: 'json',
                success: function (r) { if (r.success) location.reload(); }
            });
        });
    });

    // ----------------------------------------------------------------
    // Payment method visual selection
    // ----------------------------------------------------------------
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.addEventListener('click', function () {
            document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
        });
    });

    // ----------------------------------------------------------------
    // Loyalty points – live total update
    // ----------------------------------------------------------------
    const CURRENCY_SYMBOL   = <?php echo json_encode($currentCurrency['currency_symbol']); ?>;
    const BASE_TOTAL        = <?php echo json_encode($rawTotal); ?>;               // subtotal + shipping + tax
    const REDEMPTION_RATE   = <?php echo json_encode(Order::POINTS_REDEMPTION_RATE); ?>;
    const MAX_REDEEM        = <?php echo json_encode($maxRedeem); ?>;
    const DISCOUNT_AMOUNT   = <?php echo json_encode($discountAmount); ?>;

    const loyaltyInput      = document.getElementById('loyaltyPointsInput');
    const loyaltyField      = document.getElementById('loyalty_points_used_field');
    const applyBtn          = document.getElementById('applyPointsBtn');
    const removeBtn         = document.getElementById('removePointsBtn');
    const discountRow       = document.getElementById('loyaltyDiscountRow');
    const discountDisplay   = document.getElementById('loyaltyDiscountDisplay');
    const totalDisplay      = document.getElementById('orderTotalDisplay');
    const appliedNote       = document.getElementById('loyaltyAppliedNote');
    const appliedText       = document.getElementById('loyaltyAppliedText');

    function applyPoints() {
        if (!loyaltyInput) return;
        let pts = parseInt(loyaltyInput.value, 10) || 0;
        pts = Math.max(0, Math.min(pts, MAX_REDEEM));
        loyaltyInput.value = pts > 0 ? pts : '';

        const discount  = parseFloat((pts / REDEMPTION_RATE).toFixed(2));
        const newTotal  = Math.max(0.01, BASE_TOTAL - discount - DISCOUNT_AMOUNT);

        loyaltyField.value = pts;

        if (pts > 0) {
            if (discountRow)     discountRow.style.display     = '';
            if (discountDisplay) discountDisplay.textContent   = '-' + CURRENCY_SYMBOL + discount.toFixed(2);
            if (totalDisplay)    totalDisplay.textContent      = CURRENCY_SYMBOL + newTotal.toFixed(2);
            if (appliedNote)     appliedNote.style.display     = '';
            if (appliedText)     appliedText.textContent       = pts.toLocaleString() + ' points applied (' + CURRENCY_SYMBOL + discount.toFixed(2) + ' off)';
        } else {
            if (discountRow)     discountRow.style.display     = 'none';
            if (totalDisplay)    totalDisplay.textContent      = CURRENCY_SYMBOL + (BASE_TOTAL - DISCOUNT_AMOUNT).toFixed(2);
            if (appliedNote)     appliedNote.style.display     = 'none';
            loyaltyField.value = 0;
        }
    }

    if (applyBtn)  applyBtn.addEventListener('click',  applyPoints);
    if (removeBtn) removeBtn.addEventListener('click', function () {
        if (loyaltyInput) loyaltyInput.value = '';
        applyPoints();
    });
    // Also recalc if user types and presses Enter
    if (loyaltyInput) loyaltyInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); applyPoints(); }
    });

})();
</script>
</body>
</html>