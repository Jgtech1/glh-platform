<?php
require_once '../classes/Product.php';
require_once '../classes/User.php';
require_once '../classes/ContentManager.php';
require_once '../classes/CurrencyManager.php';

$productObj = new Product();
$userObj = new User();
$contentManager = new ContentManager();
$currencyManager = new CurrencyManager();

$products = $productObj->getAllProducts();
$currentCurrency = $currencyManager->getCurrentCurrency();
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $contentManager->get('site_title', 'Greenfield Local Hub'); ?></title>

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
            /* Forest green palette */
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
           HERO
        ============================================= */
        .hero-section {
            position: relative;
            min-height: 520px;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: var(--bg-hero);
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 60% 50%, rgba(46,125,50,0.22) 0%, transparent 70%),
                radial-gradient(ellipse 50% 80% at 20% 80%, rgba(27,94,32,0.3) 0%, transparent 60%);
        }

        /* Decorative leaf shapes */
        .hero-bg::before,
        .hero-bg::after {
            content: '';
            position: absolute;
            border-radius: 50% 0 50% 0;
            opacity: var(--leaf-opacity);
        }
        .hero-bg::before {
            width: 500px; height: 500px;
            background: var(--accent-primary);
            top: -100px; right: -80px;
            transform: rotate(25deg);
        }
        .hero-bg::after {
            width: 300px; height: 300px;
            background: var(--accent-bright);
            bottom: -80px; left: -50px;
            transform: rotate(-15deg);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding: 80px 0 100px;
        }
        .hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.4rem, 5vw, 4rem);
            font-weight: 900;
            color: var(--text-primary);
            line-height: 1.15;
            letter-spacing: -0.02em;
            margin-bottom: 1.25rem;
        }
        .hero-content h1 span {
            color: var(--accent-bright);
            position: relative;
            display: inline-block;
        }
        .hero-content h1 span::after {
            content: '';
            position: absolute;
            left: 0; bottom: -4px;
            width: 100%; height: 3px;
            background: var(--accent-gold);
            border-radius: 2px;
            transform: scaleX(0.9);
        }
        .hero-content p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 520px;
            line-height: 1.7;
            margin-bottom: 2rem;
            font-weight: 300;
        }

        .btn-hero-primary {
            background: var(--accent-primary);
            color: var(--text-inverse);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            transition: all 0.25s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-hero-primary:hover {
            background: var(--accent-bright);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(76,175,80,0.4);
            color: var(--text-inverse);
        }
        .btn-hero-primary:focus-visible { outline: none; box-shadow: var(--focus-ring); }

        .btn-hero-outline {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.25s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-hero-outline:hover {
            border-color: var(--accent-primary);
            color: var(--accent-bright);
            transform: translateY(-2px);
        }

        /* Hero stats */
        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }
        .stat-item {
            display: flex;
            flex-direction: column;
        }
        .stat-item .stat-num {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent-bright);
            line-height: 1;
        }
        .stat-item .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 3px;
        }
        .hero-visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-badge-float {
            position: relative;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(46,125,50,0.3), rgba(27,94,32,0.15));
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 4s ease-in-out infinite;
        }
        .hero-badge-float i {
            font-size: 7rem;
            color: var(--accent-muted);
            opacity: 0.6;
        }
        .hero-badge-float::after {
            content: '100% Local';
            position: absolute;
            bottom: 30px;
            background: var(--accent-gold);
            color: var(--text-inverse);
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-14px); }
        }

        /* =============================================
           WELCOME ALERT
        ============================================= */
        .welcome-alert {
            background: rgba(46,125,50,0.15);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--accent-primary);
            color: var(--text-secondary);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        /* =============================================
           SECTION TITLES
        ============================================= */
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .section-header .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--accent-gold);
            margin-bottom: 0.5rem;
        }
        .section-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            line-height: 1.2;
        }
        .section-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 0.5rem;
        }
        .section-divider {
            width: 48px; height: 3px;
            background: var(--accent-primary);
            margin: 1rem auto 0;
            border-radius: 2px;
        }

        /* =============================================
           PRODUCT CARDS
        ============================================= */
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
            height: 210px;
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
            background: var(--bg-card);
        }
        .product-card .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }
        .product-card .card-text {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.6;
            flex-grow: 1;
        }
        .product-card .price-row {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin: 0.75rem 0 0.5rem;
        }
        .product-card .price {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent-bright);
        }
        .product-card .unit {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        .product-card .producer {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .product-card .producer i { color: var(--accent-muted); }

        .card-actions { display: flex; gap: 8px; margin-top: auto; }

        .btn-add-cart {
            flex: 1;
            background: var(--accent-muted);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.55rem 0.75rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: 'DM Sans', sans-serif;
        }
        .btn-add-cart:hover {
            background: var(--accent-primary);
            transform: translateY(-1px);
        }
        .btn-add-cart:focus-visible { outline: none; box-shadow: var(--focus-ring); }

        .btn-details {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.55rem 0.9rem;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            white-space: nowrap;
        }
        .btn-details:hover {
            border-color: var(--accent-primary);
            color: var(--accent-bright);
        }
        .btn-details:focus-visible { outline: none; box-shadow: var(--focus-ring); }

        /* =============================================
           PRODUCTS GRID SECTION
        ============================================= */
        .products-section {
            padding: 5rem 0;
            background: var(--bg-secondary);
            position: relative;
        }
        .products-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-color), transparent);
        }

        /* =============================================
           WHY US STRIP
        ============================================= */
        .why-strip {
            padding: 4rem 0;
            background: var(--bg-primary);
        }
        .why-card {
            text-align: center;
            padding: 1.5rem;
        }
        .why-card .icon-wrap {
            width: 60px; height: 60px;
            border-radius: 16px;
            background: rgba(46,125,50,0.15);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.4rem;
            color: var(--accent-bright);
            transition: all 0.3s;
        }
        .why-card:hover .icon-wrap {
            background: var(--accent-muted);
            color: white;
            transform: translateY(-3px);
        }
        .why-card h5 {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.4rem;
        }
        .why-card p {
            font-size: 0.82rem;
            color: var(--text-muted);
            line-height: 1.6;
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
        .footer-link:focus-visible { outline: none; box-shadow: var(--focus-ring); border-radius: 3px; }

        .footer-divider {
            border: none;
            border-top: 1px solid var(--border-color);
            margin: 2rem 0 1.5rem;
        }
        .footer-copy {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* =============================================
           UTILITY / MISC
        ============================================= */
        .text-accent { color: var(--accent-bright); }
        .bg-card { background: var(--bg-card); }

        .no-products-msg {
            background: var(--bg-card);
            border: 1px dashed var(--border-color);
            color: var(--text-muted);
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-primary); }
        ::-webkit-scrollbar-thumb { background: var(--accent-muted); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent-primary); }

        /* Responsive tweaks */
        @media (max-width: 767px) {
            .hero-content { padding: 60px 0 50px; }
            .hero-visual { display: none; }
            .hero-stats { gap: 1.5rem; }
            .a11y-toolbar .container { justify-content: flex-start; }
            .card-actions { flex-direction: column; }
            .btn-add-cart, .btn-details { width: 100%; }
        }

        @media (max-width: 575px) {
            .hero-content h1 { font-size: 2rem; }
            .hero-content p { font-size: 0.95rem; }
            .btn-hero-primary, .btn-hero-outline { width: 100%; justify-content: center; }
            .hero-cta { display: flex; flex-direction: column; gap: 0.75rem; }
        }

        /* Focus visible global (accessibility) */
        *:focus { outline: none; }
        *:focus-visible { box-shadow: var(--focus-ring) !important; outline: 2px solid transparent; border-radius: 4px; }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.2s !important;
            }
            .hero-badge-float { animation: none; }
        }
    </style>
</head>
<body>

<!-- Skip to main content (keyboard accessibility) -->
<a class="skip-link" href="#main-content">Skip to main content</a>

<?php if ($contentManager->get('promo_banner')): ?>
<div class="promo-banner" role="banner" aria-label="Promotional announcement">
    <i class="fas fa-tag" aria-hidden="true"></i>
    <?php echo $contentManager->render('promo_banner'); ?>
</div>
<?php endif; ?>

<!-- ===================== ACCESSIBILITY TOOLBAR ===================== -->
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

<!-- ===================== NAVBAR ===================== -->
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
                    <a class="nav-link" href="index.php" aria-current="page">Home</a>
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
                                <span id="cartCount" class="cart-badge" aria-live="polite" aria-label="0 items in cart">0</span>
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

                <!-- Currency switcher -->
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

<!-- ===================== HERO ===================== -->
<main id="main-content">
<section class="hero-section" aria-label="Welcome banner">
    <div class="hero-bg" aria-hidden="true"></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 hero-content">
                <h1>
                    <?php
                    $heroTitle = $contentManager->get('hero_title', 'Fresh from <span>Local Farms</span>');
                    echo $heroTitle;
                    ?>
                </h1>
                <p><?php echo htmlspecialchars($contentManager->get('hero_subtitle', 'Support local farmers and get the freshest produce delivered to your doorstep. Farm to table, every single day.')); ?></p>
                <div class="hero-cta d-flex flex-wrap gap-3">
                    <a href="products.php" class="btn-hero-primary">
                        <i class="fas fa-leaf" aria-hidden="true"></i> Shop Now
                    </a>
                    <a href="register.php" class="btn-hero-outline">
                        <i class="fas fa-user-plus" aria-hidden="true"></i> Join Community
                    </a>
                </div>
                <div class="hero-stats" aria-label="Platform highlights">
                    <div class="stat-item">
                        <span class="stat-num">200+</span>
                        <span class="stat-label">Local Farmers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num">1,400+</span>
                        <span class="stat-label">Products</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-num">24h</span>
                        <span class="stat-label">Fresh Delivery</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 hero-visual d-none d-lg-flex" aria-hidden="true">
                <div class="hero-badge-float">
                    <i class="fas fa-seedling"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- WHY US STRIP -->
<section class="why-strip" aria-labelledby="why-heading">
    <div class="container">
        <h2 id="why-heading" class="visually-hidden">Why choose Greenfield</h2>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="why-card">
                    <div class="icon-wrap" aria-hidden="true"><i class="fas fa-tractor"></i></div>
                    <h5>Farm Direct</h5>
                    <p>Straight from verified local farms to your basket.</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="why-card">
                    <div class="icon-wrap" aria-hidden="true"><i class="fas fa-award"></i></div>
                    <h5>Quality Assured</h5>
                    <p>Every product meets our freshness standards.</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="why-card">
                    <div class="icon-wrap" aria-hidden="true"><i class="fas fa-truck"></i></div>
                    <h5>Fast Delivery</h5>
                    <p>Delivered within 24 hours of harvest.</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="why-card">
                    <div class="icon-wrap" aria-hidden="true"><i class="fas fa-leaf"></i></div>
                    <h5>Eco Friendly</h5>
                    <p>Sustainable packaging and farming practices.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== PRODUCTS SECTION ===================== -->
<section class="products-section" aria-labelledby="featured-heading">
    <div class="container">

        <?php if ($contentManager->get('welcome_message')): ?>
        <div class="welcome-alert mb-4" role="region" aria-label="Welcome message">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            <?php echo $contentManager->get('welcome_message', 'Welcome to Greenfield Local Hub!'); ?>
        </div>
        <?php endif; ?>

        <div class="section-header">
            <p class="eyebrow"><i class="fas fa-star" aria-hidden="true"></i> Handpicked for you</p>
            <h2 id="featured-heading">Featured Products</h2>
            <p>The freshest picks from our farmers this week</p>
            <div class="section-divider" aria-hidden="true"></div>
        </div>

        <div class="row g-4" id="productsContainer" aria-live="polite">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="no-products-msg" role="status">
                        <i class="fas fa-seedling fa-2x mb-3 d-block" style="color:var(--accent-muted)" aria-hidden="true"></i>
                        No products available at the moment. Check back soon!
                    </div>
                </div>
            <?php else: ?>
                <?php
                $displayProducts = array_slice($products, 0, 6);
                foreach ($displayProducts as $product):
                    $price = isset($product['price']) ? $product['price'] : (isset($product['base_price']) ? $product['base_price'] : 0);
                    $formattedPrice = $currentCurrency['currency_symbol'] . number_format($price, 2);

                    // ── FIX: index.php lives in /public/ and uploads are in
                    //         /public/assets/uploads/ so image_url needs no prefix ──
                    if (!empty($product['image_url'])) {
                        $imagePath = $product['image_url'];          // e.g. assets/uploads/filename.jpg
                    } else {
                        $imagePath = 'assets/images/default-product.jpg';
                    }
                ?>
                <div class="col-sm-6 col-lg-4">
                    <article class="product-card" aria-label="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="img-wrapper">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='assets/images/default-product.jpg';">
                            <span class="badge-fresh" aria-label="Fresh product">Fresh</span>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="card-text"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 90)); ?><?php echo (strlen($product['description'] ?? '') > 90) ? '…' : ''; ?></p>
                            <div class="price-row">
                                <span class="price"><?php echo $formattedPrice; ?></span>
                                <span class="unit">/ <?php echo htmlspecialchars($product['unit'] ?? 'item'); ?></span>
                            </div>
                            <p class="producer">
                                <i class="fas fa-user-tie" aria-hidden="true"></i>
                                <?php echo htmlspecialchars($product['producer_name']); ?>
                            </p>
                            <div class="card-actions">
                                <?php if ($userObj->isLoggedIn() && $_SESSION['role'] == 'customer'): ?>
                                <button class="btn-add-cart add-to-cart"
                                        data-product-id="<?php echo (int)$product['id']; ?>"
                                        aria-label="Add <?php echo htmlspecialchars($product['name']); ?> to cart">
                                    <i class="fas fa-cart-plus" aria-hidden="true"></i> Add to Cart
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
            <?php endif; ?>
        </div>

        <?php if (!empty($products) && count($products) > 6): ?>
        <div class="text-center mt-5">
            <a href="products.php" class="btn-hero-primary" style="display:inline-flex">
                <i class="fas fa-store" aria-hidden="true"></i> View All Products
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>
</main>

<!-- ===================== FOOTER ===================== -->
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

    /* ---- Accessibility Preferences ---- */
    const html       = document.documentElement;
    const THEME_KEY  = 'glh_theme';
    const FONT_KEY   = 'glh_font';

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

    /* Restore saved preferences */
    const savedTheme = localStorage.getItem(THEME_KEY);
    const savedFont  = localStorage.getItem(FONT_KEY);
    if (savedTheme) applyTheme(savedTheme);
    if (savedFont)  applyFontSize(savedFont);

    /* Button bindings */
    document.getElementById('btn-theme-dark').addEventListener('click', () => toggleTheme('dark'));
    document.getElementById('btn-theme-high').addEventListener('click', () => toggleTheme('high-contrast'));
    document.getElementById('btn-theme-low').addEventListener('click',  () => toggleTheme('low-contrast'));
    document.getElementById('btn-font-large').addEventListener('click', toggleFontSize);
    document.getElementById('btn-reset').addEventListener('click', () => {
        applyTheme('default');
        applyFontSize('normal');
    });

    /* ---- Cart count ---- */
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

    /* ---- Currency Switcher ---- */
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

    /* ---- Add to Cart ---- */
    document.querySelectorAll('.add-to-cart').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const originalHTML = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Adding…';

            $.ajax({
                url: '../ajax/add-to-cart.php',
                method: 'POST',
                data: { product_id: productId, quantity: 1 },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({ title: 'Added!', text: 'Product added to cart!', icon: 'success',
                            timer: 1800, showConfirmButton: false,
                            background: '#172d1a', color: '#e8f5e9',
                            iconColor: '#4caf50' });
                        loadCartCount();
                    } else {
                        Swal.fire({ title: 'Error', text: response.message || 'Failed to add to cart', icon: 'error',
                            background: '#172d1a', color: '#e8f5e9',
                            confirmButtonColor: '#4caf50' });
                    }
                },
                error: function() {
                    Swal.fire({ title: 'Error', text: 'Failed to add to cart', icon: 'error',
                        background: '#172d1a', color: '#e8f5e9',
                        confirmButtonColor: '#4caf50' });
                },
                complete: function() {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }
            });
        });
    });
})();
</script>
</body>
</html>