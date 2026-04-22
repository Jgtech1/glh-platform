<?php
session_start();
require_once '../classes/User.php';
require_once '../classes/Cart.php';
require_once '../classes/Product.php';
require_once '../classes/ContentManager.php';
require_once '../classes/CurrencyManager.php';

$userObj        = new User();
$cartObj        = new Cart();
$productObj     = new Product();
$contentManager = new ContentManager();
$currencyManager = new CurrencyManager();

// Redirect if not logged in or not a customer
if (!$userObj->isLoggedIn() || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit;
}

$currentCurrency = $currencyManager->getCurrentCurrency();

// ---------------------------------------------------------------------------
// Handle AJAX cart actions — must come BEFORE any HTML output
// ---------------------------------------------------------------------------
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        !empty($_POST['action'])          // fallback for requests without the header
    )
) {
    header('Content-Type: application/json');

    $action   = $_POST['action']   ?? '';
    $cartId   = (int) ($_POST['cart_id']  ?? 0);
    $quantity = (int) ($_POST['quantity'] ?? 1);

    if ($action === 'update' && $cartId > 0) {
        // Cart::updateCartItem() is the correct method name (not updateQuantity)
        $result = $cartObj->updateCartItem($cartId, $quantity);
        echo json_encode(['success' => (bool) $result]);

    } elseif ($action === 'remove' && $cartId > 0) {
        $result = $cartObj->removeFromCart($cartId);
        echo json_encode(['success' => (bool) $result]);

    } elseif ($action === 'clear') {
        $result = $cartObj->clearCart($_SESSION['user_id']);
        echo json_encode(['success' => (bool) $result]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}

// ---------------------------------------------------------------------------
// Normal page load — fetch cart items
// ---------------------------------------------------------------------------
$cartItems = $cartObj->getCartItems($_SESSION['user_id']);

$subtotal = 0;
foreach ($cartItems as $item) {
    $itemPrice = isset($item['price'])
        ? $item['price']
        : (isset($item['base_price']) ? $item['base_price'] : 0);
    $subtotal += $itemPrice * $item['quantity'];
}

$siteTitle = $contentManager->get('site_title', 'Greenfield Local Hub');
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | <?php echo htmlspecialchars($siteTitle); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
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
            color:var(--accent-bright) !important; display:flex; align-items:center; gap:8px; text-decoration:none;
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

        /* cart layout */
        .cart-section { padding:3rem 0 5rem; }
        .section-header { margin-bottom:2rem; }
        .section-header h1 { font-family:'Playfair Display',serif; font-size:clamp(1.8rem,3vw,2.2rem); font-weight:700; }

        .cart-table-container {
            background:var(--bg-card); border-radius:20px;
            border:1px solid var(--border-color); overflow:hidden;
        }
        .cart-table { margin:0; color:var(--text-secondary); }
        .cart-table thead th {
            background:var(--bg-secondary); color:var(--text-primary);
            border-bottom:1px solid var(--border-color); padding:1rem; font-weight:600;
        }
        .cart-table tbody td { padding:1rem; vertical-align:middle; border-bottom-color:var(--border-subtle); }

        /* product image — same fix as checkout: placeholder instead of broken fallback */
        .product-img-cart { width:70px; height:70px; object-fit:cover; border-radius:12px; }
        .product-img-placeholder {
            width:70px; height:70px; border-radius:12px; flex-shrink:0;
            background:var(--bg-secondary); display:flex; align-items:center;
            justify-content:center; color:var(--text-muted); font-size:1.6rem;
        }

        .product-name-cart { font-weight:600; color:var(--text-primary); margin-bottom:.25rem; }

        .quantity-control {
            display:inline-flex; align-items:center;
            border:1px solid var(--border-color); border-radius:8px;
            background:var(--input-bg);
        }
        .qty-btn {
            background:transparent; border:none; color:var(--text-primary);
            width:32px; height:32px; cursor:pointer; font-size:1rem; transition:all .2s;
        }
        .qty-btn:hover { color:var(--accent-bright); }
        .qty-input {
            width:50px; text-align:center; background:transparent;
            border:none; color:var(--text-primary); font-weight:600;
        }
        /* hide browser number spinners */
        .qty-input::-webkit-outer-spin-button,
        .qty-input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
        .qty-input[type=number] { -moz-appearance:textfield; }

        .remove-btn { background:transparent; border:none; color:#f44336; cursor:pointer; transition:all .2s; }
        .remove-btn:hover { color:#ff6b6b; transform:scale(1.1); }

        /* summary sidebar */
        .cart-summary {
            background:var(--bg-card); border-radius:20px;
            border:1px solid var(--border-color); padding:1.5rem;
            position:sticky; top:100px;
        }
        .summary-title {
            font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:700;
            margin-bottom:1.25rem; padding-bottom:.75rem; border-bottom:1px solid var(--border-subtle);
        }
        .summary-row { display:flex; justify-content:space-between; padding:.75rem 0; border-bottom:1px solid var(--border-subtle); }
        .summary-total {
            font-size:1.2rem; font-weight:700; color:var(--accent-bright);
            margin-top:1rem; padding-top:1rem;
            border-top:2px solid var(--accent-primary);
            display:flex; justify-content:space-between;
        }

        /* buttons */
        .btn-checkout {
            width:100%; background:var(--accent-primary); color:var(--text-inverse);
            border:none; border-radius:50px; padding:.9rem;
            font-size:1rem; font-weight:700;
            transition:all .2s; margin-top:1rem;
            text-decoration:none; display:inline-block; text-align:center;
        }
        .btn-checkout:hover { background:var(--accent-bright); transform:translateY(-2px); color:var(--text-inverse); }
        .btn-clear-cart {
            background:transparent; color:#f44336; border:1px solid #f44336;
            border-radius:50px; padding:.5rem 1rem; font-size:.8rem;
            cursor:pointer; transition:all .2s;
        }
        .btn-clear-cart:hover { background:rgba(244,67,54,.1); }
        .btn-continue {
            background:transparent; color:var(--text-secondary);
            border:1px solid var(--border-color); border-radius:50px;
            padding:.75rem 1.5rem; text-decoration:none;
            display:inline-flex; align-items:center; gap:8px;
        }
        .btn-continue:hover { border-color:var(--accent-primary); color:var(--accent-bright); }

        /* empty state */
        .empty-cart {
            text-align:center; padding:4rem 2rem;
            background:var(--bg-card); border-radius:20px;
            border:1px dashed var(--border-color);
        }
        .empty-cart i { font-size:4rem; color:var(--text-muted); margin-bottom:1rem; display:block; }

        /* updating overlay on row */
        tr.updating { opacity:.5; pointer-events:none; transition:opacity .2s; }

        /* footer */
        footer { background:var(--bg-nav); border-top:1px solid var(--border-color); padding:3rem 0 2rem; }
        .footer-brand { font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:700; color:var(--accent-bright); display:flex; align-items:center; gap:8px; }
        .footer-desc { font-size:.85rem; color:var(--text-muted); }
        .footer-heading { font-size:.72rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); margin-bottom:1rem; }
        .footer-link { display:block; font-size:.85rem; color:var(--text-secondary); text-decoration:none; margin-bottom:.5rem; }
        .footer-link:hover { color:var(--accent-bright); }
        .footer-divider { border-top:1px solid var(--border-color); margin:2rem 0; }
        .footer-copy { font-size:.78rem; color:var(--text-muted); text-align:center; }

        @media(max-width:767px) {
            .cart-table thead { display:none; }
            .cart-table tbody tr { display:block; margin-bottom:1rem; border-bottom:1px solid var(--border-color); }
            .cart-table tbody td { display:flex; justify-content:space-between; align-items:center; text-align:right; }
            .cart-table tbody td::before { content:attr(data-label); font-weight:600; text-align:left; }
            .product-img-cart, .product-img-placeholder { width:50px; height:50px; font-size:1.2rem; }
        }
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
                <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
            </ol>
        </nav>
    </div>
</div>

<!-- ========== MAIN ========== -->
<main id="main-content">
<section class="cart-section">
    <div class="container">

        <div class="section-header">
            <h1>Shopping Cart
                <?php if (!empty($cartItems)): ?>
                <small style="font-size:.55em; color:var(--text-muted); font-family:'DM Sans',sans-serif;">
                    (<?php echo count($cartItems); ?> item<?php echo count($cartItems) !== 1 ? 's' : ''; ?>)
                </small>
                <?php endif; ?>
            </h1>
        </div>

        <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p class="text-muted">Looks like you haven't added any items yet.</p>
            <a href="products.php" class="btn-checkout" style="display:inline-block;width:auto;padding:.75rem 2rem;">
                <i class="fas fa-store"></i> Continue Shopping
            </a>
        </div>

        <?php else: ?>
        <div class="row g-4">

            <!-- Cart table -->
            <div class="col-lg-8">
                <div class="cart-table-container">
                    <table class="table cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartItemsContainer">
                        <?php foreach ($cartItems as $item):
                            $itemPrice = isset($item['price'])
                                ? (float) $item['price']
                                : (float) ($item['base_price'] ?? 0);
                            $itemTotal = $itemPrice * $item['quantity'];

                            // Build image src — prepend ../ for relative paths
                            $hasImage = !empty($item['image_url']);
                            $imgSrc   = $hasImage
                                ? (strpos($item['image_url'], 'http') === 0
                                    ? $item['image_url']
                                    : '../' . ltrim($item['image_url'], '/'))
                                : null;
                        ?>
                        <tr data-cart-id="<?php echo (int)$item['cart_id']; ?>"
                            data-price="<?php echo $itemPrice; ?>">

                            <td data-label="Product">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($imgSrc): ?>
                                        <!--
                                            FIX: onerror sets null handler first to break the
                                            infinite loop, hides the broken img, shows placeholder.
                                        -->
                                        <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="product-img-cart"
                                             onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex';">
                                        <div class="product-img-placeholder" style="display:none;">
                                            <i class="fas fa-leaf"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="product-img-placeholder">
                                            <i class="fas fa-leaf"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="product-name-cart"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($item['producer_name'] ?? 'Local Farm'); ?>
                                            <?php if (!empty($item['unit'])): ?>
                                                &nbsp;·&nbsp;<?php echo htmlspecialchars($item['unit']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td data-label="Price">
                                <?php echo htmlspecialchars($currentCurrency['currency_symbol'])
                                         . number_format($itemPrice, 2); ?>
                            </td>

                            <td data-label="Quantity">
                                <div class="quantity-control">
                                    <button class="qty-btn qty-minus"
                                            data-cart-id="<?php echo (int)$item['cart_id']; ?>"
                                            aria-label="Decrease quantity">−</button>
                                    <input type="number" class="qty-input"
                                           value="<?php echo (int)$item['quantity']; ?>"
                                           min="1"
                                           max="<?php echo (int)($item['stock_quantity'] ?? 99); ?>"
                                           data-cart-id="<?php echo (int)$item['cart_id']; ?>"
                                           aria-label="Quantity for <?php echo htmlspecialchars($item['name']); ?>">
                                    <button class="qty-btn qty-plus"
                                            data-cart-id="<?php echo (int)$item['cart_id']; ?>"
                                            aria-label="Increase quantity">+</button>
                                </div>
                            </td>

                            <td data-label="Total" class="item-total fw-600" style="color:var(--accent-bright);font-weight:600;">
                                <?php echo htmlspecialchars($currentCurrency['currency_symbol'])
                                         . number_format($itemTotal, 2); ?>
                            </td>

                            <td data-label="">
                                <button class="remove-btn"
                                        data-cart-id="<?php echo (int)$item['cart_id']; ?>"
                                        aria-label="Remove <?php echo htmlspecialchars($item['name']); ?> from cart">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-3 flex-wrap gap-2">
                    <a href="products.php" class="btn-continue">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                    <button id="clearCartBtn" class="btn-clear-cart">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </div>
            </div>

            <!-- Order summary -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <div class="summary-title">Order Summary</div>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">
                            <?php echo htmlspecialchars($currentCurrency['currency_symbol'])
                                     . number_format($subtotal, 2); ?>
                        </span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>
                    <div class="summary-total">
                        <span>Estimated Total</span>
                        <span id="total">
                            <?php echo htmlspecialchars($currentCurrency['currency_symbol'])
                                     . number_format($subtotal, 2); ?>
                        </span>
                    </div>
                    <a href="checkout.php" class="btn-checkout">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>
                </div>
            </div>

        </div><!-- /row -->
        <?php endif; ?>

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
    'use strict';

    const SYMBOL = <?php echo json_encode($currentCurrency['currency_symbol']); ?>;

    // ----------------------------------------------------------------
    // Theme / font persistence
    // ----------------------------------------------------------------
    const html      = document.documentElement;
    const THEME_KEY = 'glh_theme';
    const FONT_KEY  = 'glh_font';

    function applyTheme(t) { html.setAttribute('data-theme', t || 'default'); localStorage.setItem(THEME_KEY, t || 'default'); }
    function applyFont(s)  { html.setAttribute('data-font-size', s || 'normal'); localStorage.setItem(FONT_KEY, s || 'normal'); }

    const sv = localStorage.getItem(THEME_KEY), sf = localStorage.getItem(FONT_KEY);
    if (sv) applyTheme(sv);
    if (sf) applyFont(sf);

    document.getElementById('btn-theme-dark')?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme') === 'dark'          ? 'default' : 'dark'));
    document.getElementById('btn-theme-high')?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme') === 'high-contrast'  ? 'default' : 'high-contrast'));
    document.getElementById('btn-theme-low')?.addEventListener('click',  () => applyTheme(html.getAttribute('data-theme') === 'low-contrast'   ? 'default' : 'low-contrast'));
    document.getElementById('btn-font-large')?.addEventListener('click', () => applyFont(html.getAttribute('data-font-size') === 'large' ? 'normal' : 'large'));
    document.getElementById('btn-reset')?.addEventListener('click', () => { applyTheme('default'); applyFont('normal'); });

    // ----------------------------------------------------------------
    // Currency switcher
    // ----------------------------------------------------------------
    document.querySelectorAll('.currency-option').forEach(el => {
        el.addEventListener('click', function () {
            $.ajax({
                url: 'set-currency.php', method: 'POST',
                data: { currency: this.dataset.currency }, dataType: 'json',
                success: r => { if (r.success) location.reload(); }
            });
        });
    });

    // ----------------------------------------------------------------
    // Recalculate and display subtotal from current DOM state
    // (no page reload needed — we update the numbers in-place)
    // ----------------------------------------------------------------
    function recalcTotals() {
        let sub = 0;
        document.querySelectorAll('#cartItemsContainer tr').forEach(row => {
            const price = parseFloat(row.dataset.price) || 0;
            const qty   = parseInt(row.querySelector('.qty-input')?.value, 10) || 0;
            const cell  = row.querySelector('.item-total');
            const line  = price * qty;
            if (cell) cell.textContent = SYMBOL + line.toFixed(2);
            sub += line;
        });
        const subtotalEl = document.getElementById('subtotal');
        const totalEl    = document.getElementById('total');
        if (subtotalEl) subtotalEl.textContent = SYMBOL + sub.toFixed(2);
        if (totalEl)    totalEl.textContent    = SYMBOL + sub.toFixed(2);
    }

    // ----------------------------------------------------------------
    // Quantity controls — update via AJAX, reflect in DOM, no reload
    // ----------------------------------------------------------------
    function sendUpdate(cartId, quantity, row, onFail) {
        row.classList.add('updating');
        $.ajax({
            url: 'cart.php',
            method: 'POST',
            // FIX: include X-Requested-With header so PHP detects AJAX correctly
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: { action: 'update', cart_id: cartId, quantity: quantity },
            dataType: 'json',
            success: function (res) {
                row.classList.remove('updating');
                if (res.success) {
                    recalcTotals();
                } else {
                    if (onFail) onFail();
                    Swal.fire({
                        title: 'Could not update',
                        text: 'The quantity could not be saved (may exceed stock).',
                        icon: 'warning',
                        background: '#172d1a', color: '#e8f5e9', confirmButtonColor: '#4caf50'
                    });
                }
            },
            error: function () {
                row.classList.remove('updating');
                if (onFail) onFail();
            }
        });
    }

    // Minus button
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function () {
            const input  = this.parentElement.querySelector('.qty-input');
            const cartId = parseInt(this.dataset.cartId, 10);
            const row    = this.closest('tr');
            let val      = parseInt(input.value, 10);
            if (val <= 1) return;
            input.value  = val - 1;
            sendUpdate(cartId, val - 1, row, () => { input.value = val; });
        });
    });

    // Plus button
    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function () {
            const input  = this.parentElement.querySelector('.qty-input');
            const cartId = parseInt(this.dataset.cartId, 10);
            const row    = this.closest('tr');
            const max    = parseInt(input.getAttribute('max'), 10) || 99;
            let val      = parseInt(input.value, 10);
            if (val >= max) {
                Swal.fire({ title: 'Stock limit reached', text: 'No more stock available.', icon: 'info', background: '#172d1a', color: '#e8f5e9', confirmButtonColor: '#4caf50' });
                return;
            }
            input.value  = val + 1;
            sendUpdate(cartId, val + 1, row, () => { input.value = val; });
        });
    });

    // Direct input change (user types a number)
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function () {
            const cartId = parseInt(this.dataset.cartId, 10);
            const row    = this.closest('tr');
            const max    = parseInt(this.getAttribute('max'), 10) || 99;
            let val      = parseInt(this.value, 10);
            const prev   = parseInt(this.defaultValue, 10) || 1;

            if (isNaN(val) || val < 1) { this.value = 1; val = 1; }
            if (val > max)             { this.value = max; val = max; }

            sendUpdate(cartId, val, row, () => { this.value = prev; });
            this.defaultValue = this.value; // update baseline for next change
        });
    });

    // ----------------------------------------------------------------
    // Remove single item
    // ----------------------------------------------------------------
    document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const cartId = parseInt(this.dataset.cartId, 10);
            const row    = this.closest('tr');

            Swal.fire({
                title: 'Remove item?',
                text: 'Are you sure you want to remove this item from your cart?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f44336',
                cancelButtonColor: '#2e7d32',
                confirmButtonText: 'Yes, remove',
                background: '#172d1a', color: '#e8f5e9'
            }).then(result => {
                if (!result.isConfirmed) return;

                row.classList.add('updating');
                $.ajax({
                    url: 'cart.php', method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    data: { action: 'remove', cart_id: cartId },
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            row.style.transition = 'opacity .3s';
                            row.style.opacity    = '0';
                            setTimeout(() => {
                                row.remove();
                                recalcTotals();
                                // If no rows left, reload to show the empty-cart state
                                if (!document.querySelector('#cartItemsContainer tr')) {
                                    location.reload();
                                }
                            }, 300);
                        } else {
                            row.classList.remove('updating');
                            Swal.fire({ title: 'Error', text: 'Failed to remove item.', icon: 'error', background: '#172d1a', color: '#e8f5e9' });
                        }
                    },
                    error: function () {
                        row.classList.remove('updating');
                    }
                });
            });
        });
    });

    // ----------------------------------------------------------------
    // Clear entire cart
    // ----------------------------------------------------------------
    document.getElementById('clearCartBtn')?.addEventListener('click', function () {
        Swal.fire({
            title: 'Clear cart?',
            text: 'Are you sure you want to remove all items?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f44336',
            cancelButtonColor: '#2e7d32',
            confirmButtonText: 'Yes, clear all',
            background: '#172d1a', color: '#e8f5e9'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: 'cart.php', method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: { action: 'clear' },
                dataType: 'json',
                success: function (res) {
                    if (res.success) location.reload();
                }
            });
        });
    });

})();
</script>
</body>
</html>