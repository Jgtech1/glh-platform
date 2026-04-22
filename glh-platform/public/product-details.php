<?php
require_once '../classes/Product.php';
require_once '../classes/User.php';
require_once '../classes/ContentManager.php';
require_once '../classes/CurrencyManager.php';
require_once '../classes/Cart.php';

$productObj = new Product();
$userObj = new User();
$contentManager = new ContentManager();
$currencyManager = new CurrencyManager();
$cartObj = new Cart();

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $productObj->getProductById($productId);

// If product not found, redirect to products page
if (!$product) {
    header('Location: products.php');
    exit;
}

$currentCurrency = $currencyManager->getCurrentCurrency();
$formattedPrice = $currentCurrency['currency_symbol'] . number_format($product['price'], 2);

// Get related products from same producer
$relatedProducts = $productObj->getProductsByProducer($product['producer_id'], 4, $productId);

// Get cart count for logged in users
$cartCount = 0;
if ($userObj->isLoggedIn() && $_SESSION['role'] == 'customer') {
    $cartCount = $cartObj->getCartCount();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | <?php echo $contentManager->get('site_title', 'Greenfield Local Hub'); ?></title>

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
           CSS CUSTOM PROPERTIES — THEME ENGINE
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

            --hero-overlay:      linear-gradient(160deg, rgba(13,31,15,0.92) 0%, rgba(30,77,32,0.7) 100%);
            --leaf-opacity:      0.07;

            --badge-bg:          #e53935;
            --badge-text:        #fff;

            --input-bg:          #1a2e1c;
            --input-border:      #2e5e30;
            --focus-ring:        0 0 0 3px rgba(76,175,80,0.45);
        }

        /* DARK MODE — deeper blacks */
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
            --leaf-opacity:      0.05;
            --input-bg:          #111d12;
            --input-border:      #244826;
        }

        /* HIGH CONTRAST */
        [data-theme="high-contrast"] {
            --bg-primary:        #000000;
            --bg-secondary:      #0a0a0a;
            --bg-card:           #0f0f0f;
            --bg-nav:            #000000;
            --bg-hero:           #000000;
            --accent-primary:    #00ff44;
            --accent-bright:     #00ff44;
            --accent-muted:      #00cc33;
            --accent-gold:       #ffff00;
            --accent-gold-dark:  #ffcc00;
            --text-primary:      #ffffff;
            --text-secondary:    #00ff44;
            --text-muted:        #00cc33;
            --border-color:      #00ff44;
            --border-subtle:     #00aa22;
            --shadow-card:       0 0 0 2px #00ff44;
            --shadow-hover:      0 0 0 3px #00ff44;
            --input-border:      #00ff44;
            --focus-ring:        0 0 0 4px #00ff44;
            --leaf-opacity:      0;
        }

        /* LOW CONTRAST */
        [data-theme="low-contrast"] {
            --bg-primary:        #1a2e1c;
            --bg-secondary:      #1e3520;
            --bg-card:           #223824;
            --bg-nav:            #182a1a;
            --bg-hero:           #1a2e1c;
            --accent-primary:    #6bab6e;
            --accent-bright:     #8dc490;
            --accent-muted:      #4a8a4d;
            --text-primary:      #c5dbc6;
            --text-secondary:    #9ec4a0;
            --text-muted:        #7aaa7d;
            --border-color:      #2e4e30;
            --border-subtle:     #284530;
            --leaf-opacity:      0.04;
            --input-bg:          #243c26;
            --input-border:      #3d6640;
        }

        /* LARGE TEXT */
        [data-font-size="large"] {
            font-size: 120% !important;
        }
        [data-font-size="large"] .card-text,
        [data-font-size="large"] p,
        [data-font-size="large"] .nav-link {
            font-size: 1.1rem !important;
        }
        [data-font-size="large"] h1 { font-size: 3.5rem !important; }
        [data-font-size="large"] h2 { font-size: 2.5rem !important; }
        [data-font-size="large"] h5 { font-size: 1.3rem !important; }
        [data-font-size="large"] .btn { font-size: 1.05rem !important; padding: 0.6rem 1.2rem !important; }

        /* =============================================
           BASE RESET & GLOBAL
        ============================================= */
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

        /* =============================================
           SKIP TO CONTENT (Accessibility)
        ============================================= */
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

        /* =============================================
           PROMO BANNER
        ============================================= */
        .promo-banner {
            background: var(--promo-bg);
            color: var(--promo-text);
            text-align: center;
            padding: 10px 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }

        /* =============================================
           ACCESSIBILITY TOOLBAR
        ============================================= */
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
            margin-right: 4px;
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
            font-family: 'DM Sans', sans-serif;
            line-height: 1.5;
        }
        .a11y-btn:hover,
        .a11y-btn.active {
            background: var(--accent-primary);
            color: var(--text-inverse);
            border-color: var(--accent-primary);
            transform: translateY(-1px);
        }
        .a11y-btn:focus-visible {
            outline: none;
            box-shadow: var(--focus-ring);
        }
        .a11y-divider {
            width: 1px;
            height: 20px;
            background: var(--border-color);
            margin: 0 4px;
        }

        /* =============================================
           NAVBAR
        ============================================= */
        .navbar {
            background: var(--bg-nav) !important;
            border-bottom: 1px solid var(--border-color);
            padding: 0.9rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .navbar-brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--accent-bright) !important;
            letter-spacing: -0.01em;
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
            flex-shrink: 0;
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
            letter-spacing: 0.01em;
        }
        .nav-link:hover, .nav-link:focus {
            color: var(--accent-bright) !important;
            background: rgba(76,175,80,0.1);
        }
        .nav-link:focus-visible { box-shadow: var(--focus-ring); outline: none; }

        .navbar-toggler {
            border-color: var(--border-color);
            padding: 0.4rem 0.6rem;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(165, 214, 167, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Cart badge */
        .cart-wrapper { position: relative; display: inline-block; }
        .cart-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: var(--badge-bg);
            color: var(--badge-text);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        /* Dropdown */
        .dropdown-menu {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            box-shadow: var(--shadow-card);
            padding: 0.4rem;
            min-width: 180px;
        }
        .dropdown-item {
            color: var(--text-secondary);
            border-radius: 7px;
            font-size: 0.85rem;
            padding: 0.45rem 0.75rem;
            transition: all 0.15s;
        }
        .dropdown-item:hover {
            background: rgba(76,175,80,0.12);
            color: var(--accent-bright);
        }

        /* =============================================
           BREADCRUMB
        ============================================= */
        .breadcrumb-wrapper {
            background: var(--bg-secondary);
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-subtle);
        }
        .breadcrumb {
            margin: 0;
            background: transparent;
            padding: 0;
        }
        .breadcrumb-item {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        .breadcrumb-item a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.2s;
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

        /* =============================================
           PRODUCT DETAILS SECTION
        ============================================= */
        .product-details-section {
            padding: 3rem 0 4rem;
            background: var(--bg-primary);
        }

        .product-gallery {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
        }
        .product-gallery .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: block;
        }
        .product-badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: var(--accent-gold);
            color: var(--text-inverse);
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 5px 12px;
            border-radius: 50px;
            z-index: 2;
        }
        .stock-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: var(--bg-card);
            color: var(--text-secondary);
            font-size: 0.7rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 50px;
            border: 1px solid var(--border-color);
            z-index: 2;
        }
        .stock-badge.in-stock {
            background: var(--accent-muted);
            color: white;
            border: none;
        }
        .stock-badge.low-stock {
            background: var(--accent-gold-dark);
            color: var(--text-inverse);
            border: none;
        }
        .stock-badge.out-of-stock {
            background: var(--badge-bg);
            color: white;
            border: none;
        }

        .product-info {
            padding: 0 1rem;
        }
        .product-category {
            display: inline-block;
            background: rgba(76,175,80,0.15);
            color: var(--accent-bright);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 12px;
            border-radius: 50px;
            margin-bottom: 1rem;
        }
        .product-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            line-height: 1.2;
        }
        .producer-info {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .producer-avatar {
            width: 32px;
            height: 32px;
            background: var(--accent-muted);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }
        .producer-name {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }
        .producer-name a {
            color: var(--accent-bright);
            text-decoration: none;
        }
        .producer-name a:hover {
            text-decoration: underline;
        }

        .price-wrapper {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1rem 1.25rem;
            margin: 1.25rem 0;
        }
        .current-price {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--accent-bright);
        }
        .original-price {
            font-size: 1rem;
            color: var(--text-muted);
            text-decoration: line-through;
            margin-left: 0.5rem;
        }
        .unit-info {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .product-description {
            color: var(--text-secondary);
            line-height: 1.7;
            margin: 1.25rem 0;
        }
        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 1.25rem 0;
            padding: 1rem 0;
            border-top: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .meta-item i {
            width: 20px;
            color: var(--accent-muted);
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 1.25rem 0;
        }
        .quantity-label {
            font-weight: 600;
            color: var(--text-secondary);
        }
        .quantity-control {
            display: flex;
            align-items: center;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
        }
        .quantity-btn {
            background: transparent;
            border: none;
            color: var(--text-primary);
            width: 36px;
            height: 36px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .quantity-btn:hover {
            color: var(--accent-bright);
        }
        .quantity-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .quantity-input {
            width: 50px;
            text-align: center;
            background: transparent;
            border: none;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1rem;
            padding: 0;
        }
        .quantity-input:focus {
            outline: none;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
            flex-wrap: wrap;
        }
        .btn-add-cart-detail {
            flex: 1;
            background: var(--accent-primary);
            color: var(--text-inverse);
            border: none;
            border-radius: 50px;
            padding: 0.9rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-add-cart-detail:hover:not(:disabled) {
            background: var(--accent-bright);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(76,175,80,0.3);
        }
        .btn-add-cart-detail:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .btn-wishlist {
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            padding: 0.9rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-wishlist:hover {
            border-color: var(--accent-primary);
            color: var(--accent-bright);
        }

        /* Share Section */
        .share-section {
            margin-top: 1.5rem;
        }
        .share-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-right: 12px;
        }
        .share-link {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-right: 12px;
            transition: color 0.2s;
        }
        .share-link:hover {
            color: var(--accent-bright);
        }

        /* =============================================
           RELATED PRODUCTS
        ============================================= */
        .related-section {
            padding: 3rem 0 5rem;
            background: var(--bg-secondary);
        }
        .related-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .related-header .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--accent-gold);
        }
        .related-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Product Cards (same as index) */
        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: var(--accent-muted);
        }
        .product-card .img-wrapper {
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        .product-card .img-wrapper img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.4s;
            display: block;
        }
        .product-card:hover .img-wrapper img { transform: scale(1.05); }
        .product-card .img-wrapper .badge-fresh {
            position: absolute;
            top: 12px; left: 12px;
            background: var(--accent-gold);
            color: var(--text-inverse);
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 4px 10px;
            border-radius: 50px;
        }
        .product-card .card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .product-card .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }
        .product-card .card-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.6;
        }
        .product-card .price-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin: 0.75rem 0 0.5rem;
        }
        .product-card .price {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-bright);
        }
        .product-card .unit {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .card-actions { display: flex; gap: 8px; margin-top: auto; }
        .btn-add-cart-related {
            flex: 1;
            background: var(--accent-muted);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 0.7rem;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-add-cart-related:hover {
            background: var(--accent-primary);
        }
        .btn-details-related {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.5rem 0.8rem;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-details-related:hover {
            border-color: var(--accent-primary);
            color: var(--accent-bright);
        }

        /* =============================================
           FOOTER
        ============================================= */
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
            max-width: 280px;
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
        }

        /* Utility */
        .text-accent { color: var(--accent-bright); }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--accent-muted); border-radius: 4px; }

        @media (max-width: 767px) {
            .product-gallery .main-image { height: 280px; }
            .product-title { font-size: 1.6rem; }
            .current-price { font-size: 1.8rem; }
            .action-buttons { flex-direction: column; }
            .btn-add-cart-detail, .btn-wishlist { width: 100%; justify-content: center; }
        }
        @media (max-width: 575px) {
            .product-details-section { padding: 1.5rem 0; }
        }
        *:focus { outline: none; }
        *:focus-visible { box-shadow: var(--focus-ring) !important; outline: 2px solid transparent; border-radius: 4px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.2s !important; }
        }
    </style>
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<?php if ($contentManager->get('promo_banner')): ?>
<div class="promo-banner" role="banner" aria-label="Promotional announcement">
    <i class="fas fa-tag" aria-hidden="true"></i>
    <?php echo $contentManager->render('promo_banner'); ?>
</div>
<?php endif; ?>

<!-- Accessibility Toolbar -->
<div class="a11y-toolbar" role="toolbar" aria-label="Accessibility options">
    <div class="container">
        <span class="a11y-label" aria-hidden="true"><i class="fas fa-universal-access"></i> Accessibility:</span>
        <button class="a11y-btn" id="btn-theme-dark" aria-pressed="false" title="Toggle dark mode">
            <i class="fas fa-moon" aria-hidden="true"></i> Dark
        </button>
        <div class="a11y-divider" role="separator" aria-hidden="true"></div>
        <button class="a11y-btn" id="btn-theme-high" aria-pressed="false" title="High contrast mode">
            <i class="fas fa-adjust" aria-hidden="true"></i> High Contrast
        </button>
        <button class="a11y-btn" id="btn-theme-low" aria-pressed="false" title="Low contrast mode">
            <i class="fas fa-circle-half-stroke" aria-hidden="true"></i> Low Contrast
        </button>
        <div class="a11y-divider" role="separator" aria-hidden="true"></div>
        <button class="a11y-btn" id="btn-font-large" aria-pressed="false" title="Toggle large text">
            <i class="fas fa-text-height" aria-hidden="true"></i> Large Text
        </button>
        <button class="a11y-btn" id="btn-reset" title="Reset to default appearance">
            <i class="fas fa-rotate-left" aria-hidden="true"></i> Reset
        </button>
    </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="index.php" aria-label="Greenfield Local Hub home">
            <div class="brand-leaf" aria-hidden="true"><i class="fas fa-seedling"></i></div>
            <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNav" aria-controls="navbarNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center" role="list">
                <li class="nav-item" role="listitem">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <li class="nav-item" role="listitem">
                    <a class="nav-link" href="products.php">Products</a>
                </li>

                <?php if ($userObj->isLoggedIn()): ?>
                    <?php if ($_SESSION['role'] == 'customer'): ?>
                        <li class="nav-item" role="listitem">
                            <a class="nav-link" href="order-history.php">My Orders</a>
                        </li>
                        <li class="nav-item" role="listitem">
                            <a class="nav-link cart-wrapper" href="cart.php" aria-label="Shopping cart">
                                <i class="fas fa-shopping-cart" aria-hidden="true"></i> Cart
                                <span id="cartCount" class="cart-badge" aria-live="polite" aria-label="<?php echo $cartCount; ?> items in cart"><?php echo $cartCount; ?></span>
                            </a>
                        </li>
                    <?php elseif ($_SESSION['role'] == 'producer'): ?>
                        <li class="nav-item" role="listitem">
                            <a class="nav-link" href="../producer/dashboard.php">Dashboard</a>
                        </li>
                    <?php elseif ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item" role="listitem">
                            <a class="nav-link" href="../admin/dashboard.php">Admin Panel</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item" role="listitem">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                            Logout <span class="text-accent">(<?php echo htmlspecialchars($_SESSION['username']); ?>)</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item" role="listitem">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item" role="listitem">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                <?php endif; ?>

                <li class="nav-item dropdown" role="listitem">
                    <button class="nav-link dropdown-toggle btn btn-link border-0"
                            id="currencyDropdown" aria-haspopup="true" aria-expanded="false"
                            data-bs-toggle="dropdown"
                            aria-label="Select currency: <?php echo htmlspecialchars($currentCurrency['currency_code']); ?>">
                        <?php echo htmlspecialchars($currentCurrency['currency_symbol']); ?>
                        <?php echo htmlspecialchars($currentCurrency['currency_code']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="currencyDropdown">
                        <?php foreach ($currencyManager->getAllCurrencies() as $currency): ?>
                            <li>
                                <button class="dropdown-item currency-option"
                                        data-currency="<?php echo htmlspecialchars($currency['currency_code']); ?>">
                                    <?php echo htmlspecialchars($currency['currency_symbol']); ?>
                                    <?php echo htmlspecialchars($currency['currency_code']); ?> –
                                    <?php echo htmlspecialchars($currency['currency_name']); ?>
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
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<main id="main-content">
<!-- Product Details Section -->
<section class="product-details-section">
    <div class="container">
        <div class="row g-5">
            <!-- Product Image Gallery -->
            <div class="col-lg-6">
                <div class="product-gallery">
                    <?php
                    $imagePath = !empty($product['image_url']) ? '../' . $product['image_url'] : 'assets/images/default-product.jpg';
                    ?>
                    <img src="<?php echo htmlspecialchars($imagePath); ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         class="main-image"
                         onerror="this.src='assets/images/default-product.jpg'">
                    <span class="product-badge">Fresh</span>
                    <span class="stock-badge <?php
                        if ($product['stock_quantity'] <= 0) echo 'out-of-stock';
                        elseif ($product['stock_quantity'] < 10) echo 'low-stock';
                        else echo 'in-stock';
                    ?>">
                        <?php if ($product['stock_quantity'] <= 0): ?>
                            <i class="fas fa-times-circle" aria-hidden="true"></i> Out of Stock
                        <?php elseif ($product['stock_quantity'] < 10): ?>
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Only <?php echo $product['stock_quantity']; ?> left
                        <?php else: ?>
                            <i class="fas fa-check-circle" aria-hidden="true"></i> In Stock
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-info">
                    <span class="product-category">
                        <i class="fas fa-tag" aria-hidden="true"></i>
                        <?php echo htmlspecialchars($product['category_name'] ?? 'Farm Fresh'); ?>
                    </span>
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                    <div class="producer-info">
                        <div class="producer-avatar" aria-hidden="true">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <span class="producer-name">
                            By <a href="producer-products.php?id=<?php echo $product['producer_id']; ?>">
                                <?php echo htmlspecialchars($product['producer_name']); ?>
                            </a>
                        </span>
                    </div>

                    <div class="price-wrapper">
                        <div>
                            <span class="current-price"><?php echo $formattedPrice; ?></span>
                            <?php if (isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                <span class="original-price">
                                    <?php echo $currentCurrency['currency_symbol'] . number_format($product['original_price'], 2); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="unit-info">
                            <i class="fas fa-weight-hanging" aria-hidden="true"></i>
                            Price per <?php echo htmlspecialchars($product['unit'] ?? 'item'); ?>
                        </div>
                    </div>

                    <div class="product-description">
                        <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?></p>
                    </div>

                    <div class="product-meta">
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($product['farm_location'] ?? 'Local Farm'); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                            <span>Harvested: <?php echo date('M j, Y', strtotime($product['harvest_date'] ?? 'now')); ?></span>
                        </div>
                        <?php if (!empty($product['certification'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-certificate" aria-hidden="true"></i>
                            <span><?php echo htmlspecialchars($product['certification']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($userObj->isLoggedIn() && $_SESSION['role'] == 'customer' && $product['stock_quantity'] > 0): ?>
                    <!-- Quantity Selector -->
                    <div class="quantity-selector">
                        <span class="quantity-label">Quantity:</span>
                        <div class="quantity-control">
                            <button class="quantity-btn" id="qtyMinus" aria-label="Decrease quantity">−</button>
                            <input type="number" id="quantityInput" class="quantity-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" step="1">
                            <button class="quantity-btn" id="qtyPlus" aria-label="Increase quantity">+</button>
                        </div>
                        <span class="text-muted small">(Max <?php echo $product['stock_quantity']; ?> available)</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="btn-add-cart-detail" id="addToCartBtn" data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-cart-plus" aria-hidden="true"></i> Add to Cart
                        </button>
                        <button class="btn-wishlist" id="wishlistBtn" data-product-id="<?php echo $product['id']; ?>">
                            <i class="far fa-heart" aria-hidden="true"></i> Wishlist
                        </button>
                    </div>
                    <?php elseif ($product['stock_quantity'] <= 0): ?>
                    <div class="alert alert-danger mt-3" style="background: rgba(229,57,53,0.15); border-color: #e53935; color: #ff8a80;">
                        <i class="fas fa-ban" aria-hidden="true"></i> This product is currently out of stock.
                    </div>
                    <?php elseif (!$userObj->isLoggedIn()): ?>
                    <div class="alert alert-info mt-3" style="background: rgba(76,175,80,0.15); border-color: var(--accent-muted);">
                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                        <a href="login.php" class="text-accent">Login</a> to add this item to your cart.
                    </div>
                    <?php endif; ?>

                    <!-- Share Section -->
                    <div class="share-section">
                        <span class="share-label"><i class="fas fa-share-alt" aria-hidden="true"></i> Share:</span>
                        <a href="#" class="share-link" id="shareFacebook" aria-label="Share on Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="share-link" id="shareTwitter" aria-label="Share on Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="share-link" id="shareWhatsapp" aria-label="Share on WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <button class="share-link btn btn-link p-0" id="copyLinkBtn" aria-label="Copy product link" style="background: none; border: none; display: inline; font-size: 1.1rem;">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Products Section -->
<?php if (!empty($relatedProducts)): ?>
<section class="related-section" aria-labelledby="related-heading">
    <div class="container">
        <div class="related-header">
            <p class="eyebrow"><i class="fas fa-seedling" aria-hidden="true"></i> You might also like</p>
            <h2 id="related-heading">More from <?php echo htmlspecialchars($product['producer_name']); ?></h2>
            <div class="section-divider" aria-hidden="true"></div>
        </div>

        <div class="row g-4" id="relatedProductsContainer">
            <?php foreach ($relatedProducts as $related):
                $relatedPrice = $currentCurrency['currency_symbol'] . number_format($related['price'], 2);
                $relatedImage = !empty($related['image_url']) ? '../' . $related['image_url'] : 'assets/images/default-product.jpg';
            ?>
            <div class="col-sm-6 col-lg-3">
                <article class="product-card" aria-label="<?php echo htmlspecialchars($related['name']); ?>">
                    <div class="img-wrapper">
                        <img src="<?php echo htmlspecialchars($relatedImage); ?>"
                             alt="<?php echo htmlspecialchars($related['name']); ?>"
                             loading="lazy"
                             onerror="this.src='assets/images/default-product.jpg'">
                        <span class="badge-fresh" aria-label="Fresh product">Fresh</span>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars(substr($related['description'] ?? '', 0, 70)); ?><?php echo (strlen($related['description'] ?? '') > 70) ? '…' : ''; ?></p>
                        <div class="price-row">
                            <span class="price"><?php echo $relatedPrice; ?></span>
                            <span class="unit">/ <?php echo htmlspecialchars($related['unit'] ?? 'item'); ?></span>
                        </div>
                        <div class="card-actions">
                            <?php if ($userObj->isLoggedIn() && $_SESSION['role'] == 'customer' && $related['stock_quantity'] > 0): ?>
                            <button class="btn-add-cart-related add-to-cart-related"
                                    data-product-id="<?php echo (int)$related['id']; ?>"
                                    aria-label="Add <?php echo htmlspecialchars($related['name']); ?> to cart">
                                <i class="fas fa-cart-plus" aria-hidden="true"></i> Add
                            </button>
                            <?php endif; ?>
                            <a href="product-details.php?id=<?php echo (int)$related['id']; ?>"
                               class="btn-details-related"
                               aria-label="View details for <?php echo htmlspecialchars($related['name']); ?>">
                                <i class="fas fa-eye" aria-hidden="true"></i> View
                            </a>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
</main>

<!-- Footer -->
<footer role="contentinfo">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <i class="fas fa-seedling" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?>
                </div>
                <p class="footer-desc"><?php echo htmlspecialchars($contentManager->get('footer_text', 'Connecting local farmers with communities for fresher, fairer food.')); ?></p>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <p class="footer-heading">Navigate</p>
                <a href="index.php" class="footer-link">Home</a>
                <a href="products.php" class="footer-link">Products</a>
                <a href="login.php" class="footer-link">Login</a>
                <a href="register.php" class="footer-link">Register</a>
            </div>
            <div class="col-lg-2 col-md-6 col-6">
                <p class="footer-heading">Account</p>
                <a href="order-history.php" class="footer-link">My Orders</a>
                <a href="cart.php" class="footer-link">Cart</a>
                <a href="logout.php" class="footer-link">Logout</a>
            </div>
            <div class="col-lg-4 col-md-6">
                <p class="footer-heading">Contact Us</p>
                <p class="footer-desc">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <a href="mailto:<?php echo htmlspecialchars($contentManager->get('contact_email','support@greenfieldhub.com')); ?>" class="footer-link d-inline">
                        <?php echo htmlspecialchars($contentManager->get('contact_email','support@greenfieldhub.com')); ?>
                    </a>
                </p>
                <p class="footer-desc mt-1">
                    <i class="fas fa-phone" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($contentManager->get('contact_phone', '+1 (555) 123-4567')); ?>
                </p>
            </div>
        </div>
        <hr class="footer-divider" aria-hidden="true">
        <p class="footer-copy text-center">
            &copy; 2024 <?php echo htmlspecialchars($contentManager->get('site_title', 'Greenfield Local Hub')); ?>.
            All rights reserved.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    // Accessibility Preferences
    const html = document.documentElement;
    const THEME_KEY = 'glh_theme';
    const FONT_KEY = 'glh_font';

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme || 'default');
        document.querySelectorAll('[id^="btn-theme-"]').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });
        if (theme === 'dark') {
            document.getElementById('btn-theme-dark').classList.add('active');
            document.getElementById('btn-theme-dark').setAttribute('aria-pressed', 'true');
        } else if (theme === 'high-contrast') {
            document.getElementById('btn-theme-high').classList.add('active');
            document.getElementById('btn-theme-high').setAttribute('aria-pressed', 'true');
        } else if (theme === 'low-contrast') {
            document.getElementById('btn-theme-low').classList.add('active');
            document.getElementById('btn-theme-low').setAttribute('aria-pressed', 'true');
        }
        localStorage.setItem(THEME_KEY, theme || 'default');
    }

    function toggleTheme(theme) {
        const current = html.getAttribute('data-theme');
        applyTheme(current === theme ? 'default' : theme);
    }

    function applyFontSize(size) {
        html.setAttribute('data-font-size', size || 'normal');
        const btn = document.getElementById('btn-font-large');
        const isLarge = size === 'large';
        btn.classList.toggle('active', isLarge);
        btn.setAttribute('aria-pressed', isLarge ? 'true' : 'false');
        localStorage.setItem(FONT_KEY, size || 'normal');
    }

    function toggleFontSize() {
        const current = html.getAttribute('data-font-size');
        applyFontSize(current === 'large' ? 'normal' : 'large');
    }

    const savedTheme = localStorage.getItem(THEME_KEY);
    const savedFont = localStorage.getItem(FONT_KEY);
    if (savedTheme) applyTheme(savedTheme);
    if (savedFont) applyFontSize(savedFont);

    document.getElementById('btn-theme-dark')?.addEventListener('click', () => toggleTheme('dark'));
    document.getElementById('btn-theme-high')?.addEventListener('click', () => toggleTheme('high-contrast'));
    document.getElementById('btn-theme-low')?.addEventListener('click', () => toggleTheme('low-contrast'));
    document.getElementById('btn-font-large')?.addEventListener('click', toggleFontSize);
    document.getElementById('btn-reset')?.addEventListener('click', () => {
        applyTheme('default');
        applyFontSize('normal');
    });

    // Cart count update function
    function loadCartCount() {
        $.ajax({
            url: '../ajax/load-cart-count.php',
            method: 'GET',
            success: function(response) {
                const count = parseInt(response) || 0;
                const badge = document.getElementById('cartCount');
                if (badge) {
                    badge.textContent = count;
                    badge.setAttribute('aria-label', count + ' item' + (count !== 1 ? 's' : '') + ' in cart');
                }
            }
        });
    }

    <?php if ($userObj->isLoggedIn() && $_SESSION['role'] == 'customer'): ?>
    loadCartCount();
    <?php endif; ?>

    // Currency Switcher
    document.querySelectorAll('.currency-option').forEach(function(el) {
        el.addEventListener('click', function() {
            const currencyCode = this.dataset.currency;
            $.ajax({
                url: 'set-currency.php',
                method: 'POST',
                data: { currency: currencyCode },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        Swal.fire({ title: 'Error', text: 'Failed to change currency', icon: 'error',
                            background: '#172d1a', color: '#e8f5e9',
                            confirmButtonColor: '#4caf50' });
                    }
                },
                error: function() {
                    Swal.fire({ title: 'Error', text: 'Failed to change currency', icon: 'error',
                        background: '#172d1a', color: '#e8f5e9',
                        confirmButtonColor: '#4caf50' });
                }
            });
        });
    });

    // Quantity selector
    const qtyInput = document.getElementById('quantityInput');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');
    const maxStock = <?php echo $product['stock_quantity']; ?>;

    if (qtyInput) {
        function updateQuantityButtons() {
            const val = parseInt(qtyInput.value) || 1;
            qtyMinus.disabled = val <= 1;
            qtyPlus.disabled = val >= maxStock;
        }

        qtyMinus?.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) {
                qtyInput.value = val - 1;
                updateQuantityButtons();
            }
        });

        qtyPlus?.addEventListener('click', () => {
            let val = parseInt(qtyInput.value) || 1;
            if (val < maxStock) {
                qtyInput.value = val + 1;
                updateQuantityButtons();
            }
        });

        qtyInput.addEventListener('change', () => {
            let val = parseInt(qtyInput.value) || 1;
            val = Math.min(maxStock, Math.max(1, val));
            qtyInput.value = val;
            updateQuantityButtons();
        });

        updateQuantityButtons();
    }

    // Add to Cart (Main)
    const addToCartBtn = document.getElementById('addToCartBtn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const quantity = parseInt(document.getElementById('quantityInput')?.value) || 1;
            const originalHTML = this.innerHTML;

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Adding…';

            $.ajax({
                url: '../ajax/add-to-cart.php',
                method: 'POST',
                data: { product_id: productId, quantity: quantity },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Added to Cart!',
                            text: 'Product has been added to your cart.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            background: '#172d1a',
                            color: '#e8f5e9',
                            iconColor: '#4caf50'
                        });
                        loadCartCount();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Failed to add to cart',
                            icon: 'error',
                            background: '#172d1a',
                            color: '#e8f5e9',
                            confirmButtonColor: '#4caf50'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to add to cart',
                        icon: 'error',
                        background: '#172d1a',
                        color: '#e8f5e9',
                        confirmButtonColor: '#4caf50'
                    });
                },
                complete: function() {
                    addToCartBtn.disabled = false;
                    addToCartBtn.innerHTML = originalHTML;
                }
            });
        });
    }

    // Add to Cart for Related Products
    document.querySelectorAll('.add-to-cart-related').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const originalHTML = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';

            $.ajax({
                url: '../ajax/add-to-cart.php',
                method: 'POST',
                data: { product_id: productId, quantity: 1 },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Added!',
                            text: 'Product added to cart!',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            background: '#172d1a',
                            color: '#e8f5e9',
                            iconColor: '#4caf50'
                        });
                        loadCartCount();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Failed to add to cart',
                            icon: 'error',
                            background: '#172d1a',
                            color: '#e8f5e9',
                            confirmButtonColor: '#4caf50'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to add to cart',
                        icon: 'error',
                        background: '#172d1a',
                        color: '#e8f5e9',
                        confirmButtonColor: '#4caf50'
                    });
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            });
        });
    });

    // Wishlist functionality
    const wishlistBtn = document.getElementById('wishlistBtn');
    if (wishlistBtn) {
        wishlistBtn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const originalHTML = this.innerHTML;

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i>';

            $.ajax({
                url: '../ajax/add-to-wishlist.php',
                method: 'POST',
                data: { product_id: productId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Added to Wishlist!',
                            text: 'Product saved to your wishlist.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            background: '#172d1a',
                            color: '#e8f5e9',
                            iconColor: '#4caf50'
                        });
                        wishlistBtn.innerHTML = '<i class="fas fa-heart" aria-hidden="true"></i> Wishlisted';
                    } else if (response.already_exists) {
                        Swal.fire({
                            title: 'Already in Wishlist',
                            text: 'This product is already in your wishlist.',
                            icon: 'info',
                            timer: 2000,
                            showConfirmButton: false,
                            background: '#172d1a',
                            color: '#e8f5e9'
                        });
                        wishlistBtn.innerHTML = originalHTML;
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Failed to add to wishlist',
                            icon: 'error',
                            background: '#172d1a',
                            color: '#e8f5e9',
                            confirmButtonColor: '#4caf50'
                        });
                        wishlistBtn.innerHTML = originalHTML;
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to add to wishlist',
                        icon: 'error',
                        background: '#172d1a',
                        color: '#e8f5e9',
                        confirmButtonColor: '#4caf50'
                    });
                    wishlistBtn.innerHTML = originalHTML;
                },
                complete: function() {
                    wishlistBtn.disabled = false;
                }
            });
        });
    }

    // Share functionality
    const currentUrl = encodeURIComponent(window.location.href);
    const productTitle = encodeURIComponent("<?php echo htmlspecialchars($product['name']); ?>");

    document.getElementById('shareFacebook')?.addEventListener('click', (e) => {
        e.preventDefault();
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${currentUrl}`, '_blank', 'width=600,height=400');
    });

    document.getElementById('shareTwitter')?.addEventListener('click', (e) => {
        e.preventDefault();
        window.open(`https://twitter.com/intent/tweet?text=Check out ${productTitle}&url=${currentUrl}`, '_blank', 'width=600,height=400');
    });

    document.getElementById('shareWhatsapp')?.addEventListener('click', (e) => {
        e.preventDefault();
        window.open(`https://wa.me/?text=${productTitle}%20${currentUrl}`, '_blank');
    });

    document.getElementById('copyLinkBtn')?.addEventListener('click', () => {
        navigator.clipboard.writeText(window.location.href).then(() => {
            Swal.fire({
                title: 'Link Copied!',
                text: 'Product link copied to clipboard',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                background: '#172d1a',
                color: '#e8f5e9'
            });
        });
    });
})();
</script>
</body>
</html>