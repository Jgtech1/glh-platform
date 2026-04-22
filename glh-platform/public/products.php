<?php
session_start();
require_once '../classes/Product.php';
require_once '../classes/CurrencyManager.php';

$productObj      = new Product();
$currencyManager = new CurrencyManager();
$currentCurrency = $currencyManager->getCurrentCurrency();

$search   = $_GET['search']   ?? '';
$category = $_GET['category'] ?? '';
$sort     = $_GET['sort']     ?? 'default';

if ($search) {
    $products = $productObj->searchProducts($search);
} else {
    $products = $productObj->getAllProducts();
}

// Client-side sort (keeps it simple without extra DB queries)
if (!empty($products)) {
    usort($products, function($a, $b) use ($sort) {
        $pa = isset($a['price']) ? $a['price'] : (isset($a['base_price']) ? $a['base_price'] : 0);
        $pb = isset($b['price']) ? $b['price'] : (isset($b['base_price']) ? $b['base_price'] : 0);
        switch ($sort) {
            case 'price_asc':  return $pa <=> $pb;
            case 'price_desc': return $pb <=> $pa;
            case 'name_asc':   return strcasecmp($a['name'], $b['name']);
            case 'name_desc':  return strcasecmp($b['name'], $a['name']);
            default:           return 0;
        }
    });
}

$totalProducts  = count($products);
$inStockCount   = count(array_filter($products, fn($p) => ($p['stock_quantity'] ?? 0) > 0));
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $search ? 'Search: ' . htmlspecialchars($search) . ' — ' : ''; ?>Products — Greenfield Local Hub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ── Theme variables ── */
        :root, [data-theme="default"] {
            --bg-primary:     #0d1f0f;
            --bg-secondary:   #122614;
            --bg-card:        #172d1a;
            --bg-nav:         #0a1a0c;
            --accent-primary: #4caf50;
            --accent-bright:  #81c784;
            --accent-muted:   #2e7d32;
            --accent-gold:    #ffd54f;
            --text-primary:   #e8f5e9;
            --text-secondary: #a5d6a7;
            --text-muted:     #66bb6a;
            --text-inverse:   #0d1f0f;
            --border-color:   #1e3d20;
            --border-subtle:  #1a3320;
            --shadow-card:    0 8px 32px rgba(0,0,0,0.45);
            --shadow-hover:   0 16px 48px rgba(0,0,0,0.6);
            --input-bg:       #1a2e1c;
            --input-border:   #2e5e30;
            --focus-ring:     0 0 0 3px rgba(76,175,80,0.45);
            --badge-red:      #e53935;
            --leaf-opacity:   0.06;
        }
        [data-theme="dark"] {
            --bg-primary:     #060e07;
            --bg-secondary:   #0a140b;
            --bg-card:        #0e1f10;
            --bg-nav:         #040a05;
            --accent-primary: #66bb6a;
            --accent-bright:  #a5d6a7;
            --accent-muted:   #388e3c;
            --text-primary:   #f1f8e9;
            --text-secondary: #c8e6c9;
            --text-muted:     #81c784;
            --border-color:   #152617;
            --input-bg:       #111d12;
            --input-border:   #244826;
            --leaf-opacity:   0.04;
        }
        [data-theme="high-contrast"] {
            --bg-primary:     #000;
            --bg-secondary:   #0a0a0a;
            --bg-card:        #0f0f0f;
            --bg-nav:         #000;
            --accent-primary: #00ff44;
            --accent-bright:  #00ff44;
            --accent-muted:   #00cc33;
            --accent-gold:    #ffff00;
            --text-primary:   #fff;
            --text-secondary: #00ff44;
            --text-muted:     #00cc33;
            --border-color:   #00ff44;
            --input-bg:       #000;
            --input-border:   #00ff44;
            --focus-ring:     0 0 0 4px #00ff44;
            --leaf-opacity:   0;
        }
        [data-theme="low-contrast"] {
            --bg-primary:     #1a2e1c;
            --bg-secondary:   #1e3520;
            --bg-card:        #223824;
            --bg-nav:         #182a1a;
            --accent-primary: #6bab6e;
            --accent-bright:  #8dc490;
            --text-primary:   #c5dbc6;
            --text-secondary: #9ec4a0;
            --text-muted:     #7aaa7d;
            --border-color:   #2e4e30;
            --input-bg:       #243c26;
            --input-border:   #3d6640;
            --leaf-opacity:   0.03;
        }
        [data-font-size="large"] { font-size: 118% !important; }
        [data-font-size="large"] .card-title  { font-size: 1.15rem !important; }
        [data-font-size="large"] .card-text,
        [data-font-size="large"] .nav-link    { font-size: 1rem !important; }
        [data-font-size="large"] .btn-sm      { font-size: 0.9rem !important; padding: 0.45rem 0.9rem !important; }

        /* ── Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex; flex-direction: column;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Skip link */
        .skip-link {
            position: absolute; top: -60px; left: 1rem;
            background: var(--accent-primary); color: var(--text-inverse);
            padding: 0.5rem 1rem; border-radius: 0 0 8px 8px;
            font-weight: 600; z-index: 9999; transition: top 0.2s; text-decoration: none;
        }
        .skip-link:focus { top: 0; }

        /* ── A11y toolbar ── */
        .a11y-toolbar {
            background: var(--bg-nav);
            border-bottom: 1px solid var(--border-color);
            padding: 6px 0;
        }
        .a11y-toolbar .container {
            display: flex; align-items: center; gap: 8px;
            flex-wrap: wrap; justify-content: flex-end;
        }
        .a11y-label {
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em;
            color: var(--text-muted); font-weight: 600; margin-right: 4px;
        }
        .a11y-btn {
            background: var(--bg-card); color: var(--text-secondary);
            border: 1px solid var(--border-color); border-radius: 6px;
            padding: 4px 10px; font-size: 0.75rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            display: inline-flex; align-items: center; gap: 5px;
            font-family: 'DM Sans', sans-serif;
        }
        .a11y-btn:hover, .a11y-btn.active {
            background: var(--accent-primary); color: var(--text-inverse);
            border-color: var(--accent-primary); transform: translateY(-1px);
        }
        .a11y-btn:focus-visible { outline: none; box-shadow: var(--focus-ring); }
        .a11y-divider { width: 1px; height: 20px; background: var(--border-color); margin: 0 4px; }

        /* ── Navbar ── */
        .navbar {
            background: var(--bg-nav) !important;
            border-bottom: 1px solid var(--border-color);
            padding: 0.9rem 0;
            position: sticky; top: 0; z-index: 1000;
            backdrop-filter: blur(12px);
        }
        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem; font-weight: 700;
            color: var(--accent-bright) !important;
            text-decoration: none;
            display: flex; align-items: center; gap: 8px;
        }
        .brand-leaf {
            width: 30px; height: 30px;
            background: var(--accent-muted); border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .brand-leaf i { transform: rotate(45deg); font-size: 13px; color: #fff; }

        .nav-link {
            color: var(--text-secondary) !important;
            font-size: 0.875rem; font-weight: 500;
            padding: 0.4rem 0.75rem !important;
            border-radius: 8px; transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: var(--accent-bright) !important;
            background: rgba(76,175,80,0.1);
        }
        .nav-link:focus-visible { outline: none; box-shadow: var(--focus-ring); }
        .navbar-toggler {
            border-color: var(--border-color); padding: 0.4rem 0.6rem;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(165,214,167,0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .cart-wrapper { position: relative; display: inline-flex; align-items: center; }
        .cart-badge {
            position: absolute; top: -5px; right: -8px;
            background: var(--badge-red); color: #fff;
            border-radius: 50%; width: 17px; height: 17px;
            font-size: 9px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }

        /* ── Search bar ── */
        .search-input {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 10px 0 0 10px;
            color: var(--text-primary);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem; padding: 0.5rem 1rem;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
            width: 220px;
        }
        .search-input::placeholder { color: var(--text-muted); opacity: 0.6; }
        .search-input:focus { border-color: var(--accent-primary); box-shadow: var(--focus-ring); }
        .search-btn {
            background: var(--accent-muted); color: #fff;
            border: none; border-radius: 0 10px 10px 0;
            padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .search-btn:hover { background: var(--accent-primary); }
        .search-btn:focus-visible { outline: none; box-shadow: var(--focus-ring); }

        /* ── Page hero strip ── */
        .page-hero {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 2.5rem 0 2rem;
            position: relative; overflow: hidden;
        }
        .page-hero::after {
            content: '';
            position: absolute; top: -60px; right: -60px;
            width: 260px; height: 260px;
            border-radius: 50% 0 50% 0;
            background: var(--accent-primary);
            opacity: var(--leaf-opacity);
            transform: rotate(15deg);
        }
        .page-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.7rem, 3vw, 2.4rem);
            font-weight: 700; color: var(--text-primary);
            letter-spacing: -0.02em; line-height: 1.2;
        }
        .page-hero h1 span { color: var(--accent-bright); }
        .page-hero p { font-size: 0.9rem; color: var(--text-muted); margin-top: 0.4rem; }

        /* Stats pills */
        .stats-row {
            display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1.25rem;
        }
        .stat-pill {
            background: rgba(76,175,80,0.1);
            border: 1px solid var(--border-color);
            border-radius: 50px; padding: 5px 14px;
            font-size: 0.78rem; font-weight: 600;
            color: var(--text-secondary);
            display: flex; align-items: center; gap: 6px;
        }
        .stat-pill i { color: var(--accent-muted); }

        /* ── Toolbar (filter/sort bar) ── */
        .filter-bar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 0.85rem 0;
            position: sticky; top: 61px; z-index: 90;
        }
        .filter-bar .container {
            display: flex; align-items: center;
            justify-content: space-between; gap: 1rem; flex-wrap: wrap;
        }
        .filter-label {
            font-size: 0.75rem; text-transform: uppercase;
            letter-spacing: 0.08em; color: var(--text-muted); font-weight: 600;
        }
        .filter-select {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px; color: var(--text-primary);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem; padding: 0.4rem 2rem 0.4rem 0.75rem;
            outline: none; cursor: pointer; transition: border-color 0.2s;
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7' viewBox='0 0 10 7'%3E%3Cpath fill='%2366bb6a' d='M5 7L0 0h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
        }
        .filter-select:focus { border-color: var(--accent-primary); box-shadow: var(--focus-ring); }
        .filter-select option { background: var(--bg-card); }
        .result-count { font-size: 0.82rem; color: var(--text-muted); }
        .result-count strong { color: var(--accent-bright); }

        /* ── Products grid ── */
        .products-section { flex: 1; padding: 2.5rem 0 4rem; }

        /* ── Product card ── */
        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px; overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
            height: 100%; display: flex; flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: var(--accent-muted);
        }
        .product-card.out-of-stock { opacity: 0.72; }

        .card-img-wrap {
            position: relative; overflow: hidden; flex-shrink: 0;
        }
        .card-img-wrap img {
            width: 100%; height: 200px; object-fit: cover; display: block;
            transition: transform 0.4s;
        }
        .product-card:hover .card-img-wrap img { transform: scale(1.05); }

        .badge-fresh, .badge-sold-out {
            position: absolute; top: 10px;
            font-size: 0.62rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; padding: 4px 10px; border-radius: 50px;
        }
        .badge-fresh {
            left: 10px;
            background: var(--accent-gold); color: var(--text-inverse);
        }
        .badge-sold-out {
            right: 10px;
            background: rgba(0,0,0,0.65); color: #ef9a9a;
            border: 1px solid #c62828;
        }

        /* Stock level dot */
        .stock-dot {
            display: inline-block; width: 7px; height: 7px;
            border-radius: 50%; margin-right: 4px; flex-shrink: 0;
        }
        .stock-dot.high   { background: var(--accent-primary); }
        .stock-dot.medium { background: var(--accent-gold); }
        .stock-dot.low    { background: #ef9a9a; }

        .card-body {
            padding: 1.1rem 1.15rem 1.15rem;
            display: flex; flex-direction: column; flex: 1;
            background: var(--bg-card);
        }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem; font-weight: 700;
            color: var(--text-primary); margin-bottom: 0.35rem;
            line-height: 1.3;
        }
        .card-desc {
            font-size: 0.82rem; color: var(--text-muted);
            line-height: 1.55; flex-grow: 1; margin-bottom: 0.75rem;
        }
        .price-row {
            display: flex; align-items: baseline; gap: 5px; margin-bottom: 0.5rem;
        }
        .price {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem; font-weight: 700; color: var(--accent-bright);
        }
        .unit { font-size: 0.78rem; color: var(--text-muted); }

        .meta-row {
            display: flex; align-items: center; gap: 10px;
            font-size: 0.74rem; color: var(--text-muted);
            margin-bottom: 0.9rem; flex-wrap: wrap;
        }
        .meta-row i { color: var(--accent-muted); }

        .card-actions { display: flex; gap: 7px; margin-top: auto; }

        .btn-add-cart {
            flex: 1; background: var(--accent-muted); color: #fff;
            border: none; border-radius: 8px;
            padding: 0.52rem 0.75rem; font-size: 0.8rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-add-cart:hover:not(:disabled) {
            background: var(--accent-primary); transform: translateY(-1px);
        }
        .btn-add-cart:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-add-cart:focus-visible { outline: none; box-shadow: var(--focus-ring); }

        .btn-details {
            background: transparent; color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px; padding: 0.52rem 0.85rem;
            font-size: 0.8rem; font-weight: 600;
            text-decoration: none; transition: all 0.2s;
            display: flex; align-items: center; gap: 5px; white-space: nowrap;
        }
        .btn-details:hover {
            border-color: var(--accent-primary); color: var(--accent-bright);
        }
        .btn-details:focus-visible { outline: none; box-shadow: var(--focus-ring); }

        /* ── Empty state ── */
        .empty-state {
            text-align: center; padding: 5rem 1rem;
        }
        .empty-icon {
            width: 80px; height: 80px;
            background: rgba(76,175,80,0.1);
            border: 1px dashed var(--border-color);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.8rem; color: var(--accent-muted);
        }
        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem; color: var(--text-primary); margin-bottom: 0.5rem;
        }
        .empty-state p { font-size: 0.9rem; color: var(--text-muted); }
        .btn-clear-search {
            display: inline-flex; align-items: center; gap: 6px;
            margin-top: 1.25rem;
            background: var(--accent-muted); color: #fff;
            border: none; border-radius: 50px;
            padding: 0.6rem 1.4rem; font-size: 0.85rem; font-weight: 600;
            text-decoration: none; transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-clear-search:hover { background: var(--accent-primary); color: #fff; }

        /* ── Footer ── */
        footer {
            background: var(--bg-nav);
            border-top: 1px solid var(--border-color);
            padding: 2rem 0; text-align: center;
        }
        footer p { font-size: 0.8rem; color: var(--text-muted); }
        footer a { color: var(--text-muted); text-decoration: none; }
        footer a:hover { color: var(--accent-bright); }

        /* Focus */
        *:focus { outline: none; }
        *:focus-visible { box-shadow: var(--focus-ring) !important; outline: 2px solid transparent; border-radius: 4px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.15s !important; }
        }
        @media (max-width: 575px) {
            .filter-bar .container { flex-direction: column; align-items: flex-start; }
            .search-input { width: 160px; }
        }
    </style>
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<!-- A11y toolbar -->
<div class="a11y-toolbar" role="toolbar" aria-label="Accessibility options">
    <div class="container">
        <span class="a11y-label" aria-hidden="true"><i class="fas fa-universal-access"></i> Accessibility:</span>
        <button class="a11y-btn" id="btn-theme-dark"  aria-pressed="false" title="Toggle dark mode"><i class="fas fa-moon" aria-hidden="true"></i> Dark</button>
        <div class="a11y-divider" role="separator" aria-hidden="true"></div>
        <button class="a11y-btn" id="btn-theme-high"  aria-pressed="false" title="High contrast"><i class="fas fa-adjust" aria-hidden="true"></i> High Contrast</button>
        <button class="a11y-btn" id="btn-theme-low"   aria-pressed="false" title="Low contrast"><i class="fas fa-circle-half-stroke" aria-hidden="true"></i> Low Contrast</button>
        <div class="a11y-divider" role="separator" aria-hidden="true"></div>
        <button class="a11y-btn" id="btn-font-large"  aria-pressed="false" title="Large text"><i class="fas fa-text-height" aria-hidden="true"></i> Large Text</button>
        <button class="a11y-btn" id="btn-reset"        title="Reset appearance"><i class="fas fa-rotate-left" aria-hidden="true"></i> Reset</button>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="index.php" aria-label="Greenfield Local Hub home">
            <div class="brand-leaf" aria-hidden="true"><i class="fas fa-seedling"></i></div>
            Greenfield Local Hub
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto" role="list">
                <li class="nav-item" role="listitem">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item" role="listitem">
                    <a class="nav-link active" href="products.php" aria-current="page">Products</a>
                </li>
            </ul>

            <!-- Search form -->
            <form class="d-flex me-3" method="GET" action="products.php" role="search" aria-label="Search products">
                <input class="search-input" type="search" name="search"
                       placeholder="Search products…"
                       value="<?php echo htmlspecialchars($search); ?>"
                       aria-label="Search products">
                <button class="search-btn" type="submit" aria-label="Submit search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>
            </form>

            <ul class="navbar-nav" role="list">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item" role="listitem">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                            Logout <span style="color:var(--accent-bright)">(<?php echo htmlspecialchars($_SESSION['username']); ?>)</span>
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] == 'customer'): ?>
                    <li class="nav-item" role="listitem">
                        <a class="nav-link cart-wrapper" href="cart.php" aria-label="Shopping cart">
                            <i class="fas fa-shopping-cart" aria-hidden="true"></i> Cart
                            <span id="cartCount" class="cart-badge" aria-live="polite" aria-label="0 items">0</span>
                        </a>
                    </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item" role="listitem"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item" role="listitem"><a class="nav-link" href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Page hero -->
<div class="page-hero" role="banner">
    <div class="container">
        <h1>
            <?php if ($search): ?>
                Results for <span>"<?php echo htmlspecialchars($search); ?>"</span>
            <?php else: ?>
                All <span>Products</span>
            <?php endif; ?>
        </h1>
        <p>
            <?php if ($search): ?>
                Showing matches from our local farmers
            <?php else: ?>
                Fresh produce from verified local farms, harvested daily
            <?php endif; ?>
        </p>
        <div class="stats-row" aria-label="Catalogue summary">
            <div class="stat-pill"><i class="fas fa-box" aria-hidden="true"></i> <?php echo $totalProducts; ?> product<?php echo $totalProducts !== 1 ? 's' : ''; ?></div>
            <div class="stat-pill"><i class="fas fa-check-circle" aria-hidden="true"></i> <?php echo $inStockCount; ?> in stock</div>
            <?php if ($search): ?>
            <div class="stat-pill">
                <i class="fas fa-search" aria-hidden="true"></i>
                Searching: "<?php echo htmlspecialchars($search); ?>"
                <a href="products.php" style="color:var(--accent-gold);margin-left:4px;text-decoration:none" aria-label="Clear search">✕</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filter / sort bar -->
<div class="filter-bar" role="toolbar" aria-label="Sort and filter options">
    <div class="container">
        <p class="result-count" aria-live="polite">
            Showing <strong><?php echo $totalProducts; ?></strong> result<?php echo $totalProducts !== 1 ? 's' : ''; ?>
        </p>
        <form method="GET" action="products.php" id="sortForm" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <?php if ($search): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <?php endif; ?>
            <span class="filter-label" id="sort-label">Sort by:</span>
            <select class="filter-select" name="sort" aria-labelledby="sort-label"
                    onchange="this.form.submit()">
                <option value="default"    <?php echo $sort === 'default'    ? 'selected' : ''; ?>>Default</option>
                <option value="price_asc"  <?php echo $sort === 'price_asc'  ? 'selected' : ''; ?>>Price: Low → High</option>
                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High → Low</option>
                <option value="name_asc"   <?php echo $sort === 'name_asc'   ? 'selected' : ''; ?>>Name: A → Z</option>
                <option value="name_desc"  <?php echo $sort === 'name_desc'  ? 'selected' : ''; ?>>Name: Z → A</option>
            </select>
        </form>
    </div>
</div>

<!-- Products grid -->
<main id="main-content" class="products-section">
    <div class="container">
        <?php if (empty($products)): ?>
            <div class="empty-state" role="status">
                <div class="empty-icon" aria-hidden="true"><i class="fas fa-seedling"></i></div>
                <h3><?php echo $search ? 'No results found' : 'No products yet'; ?></h3>
                <p>
                    <?php if ($search): ?>
                        Nothing matched "<?php echo htmlspecialchars($search); ?>". Try a different term.
                    <?php else: ?>
                        Check back soon — local farmers update their listings daily!
                    <?php endif; ?>
                </p>
                <?php if ($search): ?>
                <a href="products.php" class="btn-clear-search">
                    <i class="fas fa-times" aria-hidden="true"></i> Clear Search
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="row g-4" aria-label="Product listings" aria-live="polite">
                <?php foreach ($products as $product):
                    $price       = isset($product['price']) ? $product['price'] : (isset($product['base_price']) ? $product['base_price'] : 0);
                    $converted   = $currencyManager->convert($price);
                    $formatted   = $currencyManager->formatPrice($converted);
                    $imagePath   = !empty($product['image_url']) ? '../public/' . $product['image_url'] : 'assets/images/default-product.jpg';
                    $stock       = (int)($product['stock_quantity'] ?? 0);
                    $stockClass  = $stock <= 0 ? '' : ($stock <= 5 ? 'low' : ($stock <= 20 ? 'medium' : 'high'));
                    $inStock     = $stock > 0;
                ?>
                <div class="col-sm-6 col-lg-4">
                    <article class="product-card<?php echo !$inStock ? ' out-of-stock' : ''; ?>"
                             aria-label="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-img-wrap">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy"
                                 onerror="this.src='assets/images/default-product.jpg'">
                            <?php if ($inStock): ?>
                                <span class="badge-fresh" aria-label="In stock">Fresh</span>
                            <?php else: ?>
                                <span class="badge-sold-out" aria-label="Out of stock">Sold Out</span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <h2 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                            <p class="card-desc">
                                <?php
                                $desc = $product['description'] ?? '';
                                echo htmlspecialchars(strlen($desc) > 95 ? substr($desc, 0, 95) . '…' : $desc);
                                ?>
                            </p>

                            <div class="price-row">
                                <span class="price"><?php echo $formatted; ?></span>
                                <span class="unit">/ <?php echo htmlspecialchars($product['unit'] ?? 'item'); ?></span>
                            </div>

                            <div class="meta-row">
                                <span>
                                    <?php if ($inStock): ?>
                                        <span class="stock-dot <?php echo $stockClass; ?>" aria-hidden="true"></span>
                                        <?php echo $stock; ?> unit<?php echo $stock !== 1 ? 's' : ''; ?> left
                                    <?php else: ?>
                                        <i class="fas fa-times-circle" style="color:#ef9a9a" aria-hidden="true"></i>
                                        Out of stock
                                    <?php endif; ?>
                                </span>
                                <span><i class="fas fa-user-tie" aria-hidden="true"></i> <?php echo htmlspecialchars($product['producer_name']); ?></span>
                            </div>

                            <div class="card-actions">
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer' && $inStock): ?>
                                <button class="btn-add-cart add-to-cart"
                                        data-product-id="<?php echo (int)$product['id']; ?>"
                                        aria-label="Add <?php echo htmlspecialchars($product['name']); ?> to cart">
                                    <i class="fas fa-cart-plus" aria-hidden="true"></i> Add to Cart
                                </button>
                                <?php elseif (!$inStock): ?>
                                <button class="btn-add-cart" disabled aria-disabled="true">
                                    <i class="fas fa-ban" aria-hidden="true"></i> Unavailable
                                </button>
                                <?php endif; ?>
                                <a href="product-details.php?id=<?php echo (int)$product['id']; ?>"
                                   class="btn-details"
                                   aria-label="View details for <?php echo htmlspecialchars($product['name']); ?>">
                                    <i class="fas fa-eye" aria-hidden="true"></i> Details
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Footer -->
<footer role="contentinfo">
    <div class="container">
        <p>&copy; 2024 <a href="index.php">Greenfield Local Hub</a>. Fresh from local farms to your table.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    /* ---- A11y theme engine ---- */
    var html      = document.documentElement;
    var THEME_KEY = 'glh_theme';
    var FONT_KEY  = 'glh_font';

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme || 'default');
        ['dark','high','low'].forEach(function(t) {
            var b = document.getElementById('btn-theme-' + t);
            if (b) { b.classList.remove('active'); b.setAttribute('aria-pressed','false'); }
        });
        var map = { dark:'btn-theme-dark', 'high-contrast':'btn-theme-high', 'low-contrast':'btn-theme-low' };
        if (map[theme]) {
            var el = document.getElementById(map[theme]);
            if (el) { el.classList.add('active'); el.setAttribute('aria-pressed','true'); }
        }
        localStorage.setItem(THEME_KEY, theme || 'default');
    }
    function toggleTheme(t) { applyTheme(html.getAttribute('data-theme') === t ? 'default' : t); }

    function applyFont(size) {
        html.setAttribute('data-font-size', size || 'normal');
        var btn = document.getElementById('btn-font-large');
        var lg  = size === 'large';
        btn.classList.toggle('active', lg);
        btn.setAttribute('aria-pressed', lg ? 'true' : 'false');
        localStorage.setItem(FONT_KEY, size || 'normal');
    }

    var st = localStorage.getItem(THEME_KEY);
    var sf = localStorage.getItem(FONT_KEY);
    if (st) applyTheme(st);
    if (sf) applyFont(sf);

    document.getElementById('btn-theme-dark').addEventListener('click', function(){ toggleTheme('dark'); });
    document.getElementById('btn-theme-high').addEventListener('click', function(){ toggleTheme('high-contrast'); });
    document.getElementById('btn-theme-low').addEventListener('click',  function(){ toggleTheme('low-contrast'); });
    document.getElementById('btn-font-large').addEventListener('click', function(){ applyFont(html.getAttribute('data-font-size') === 'large' ? 'normal' : 'large'); });
    document.getElementById('btn-reset').addEventListener('click', function(){ applyTheme('default'); applyFont('normal'); });

    /* ---- Cart count ---- */
    function loadCartCount() {
        $.ajax({
            url: '../ajax/load-cart-count.php',
            method: 'GET',
            success: function(response) {
                var count = parseInt(response) || 0;
                var badge = document.getElementById('cartCount');
                if (badge) {
                    badge.textContent = count;
                    badge.setAttribute('aria-label', count + ' item' + (count !== 1 ? 's' : '') + ' in cart');
                }
            }
        });
    }
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
    loadCartCount();
    <?php endif; ?>

    /* ---- Add to cart ---- */
    document.querySelectorAll('.add-to-cart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var productId   = this.dataset.productId;
            var origHTML    = this.innerHTML;
            this.disabled   = true;
            this.innerHTML  = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Adding…';
            var self = this;

            $.ajax({
                url: '../ajax/add-to-cart.php',
                method: 'POST',
                data: { product_id: productId, quantity: 1 },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success', title: 'Added to Cart!',
                            text: 'Product added successfully.',
                            timer: 1500, showConfirmButton: false,
                            background: '#172d1a', color: '#e8f5e9', iconColor: '#4caf50'
                        });
                        loadCartCount();
                    } else {
                        Swal.fire({
                            icon: 'error', title: 'Error',
                            text: response.message || 'Failed to add to cart.',
                            background: '#172d1a', color: '#e8f5e9', confirmButtonColor: '#2e7d32'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error', title: 'Error',
                        text: 'An error occurred. Please try again.',
                        background: '#172d1a', color: '#e8f5e9', confirmButtonColor: '#2e7d32'
                    });
                },
                complete: function() {
                    self.disabled  = false;
                    self.innerHTML = origHTML;
                }
            });
        });
    });
})();
</script>
</body>
</html>