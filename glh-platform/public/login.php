<?php
require_once '../classes/User.php';
$userObj = new User();

if ($userObj->isLoggedIn()) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: ../admin/dashboard.php');
    } elseif ($_SESSION['role'] == 'producer') {
        header('Location: ../producer/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $userObj->login($username, $password);

    if ($result['success']) {
        if ($result['role'] == 'admin') {
            header('Location: ../admin/dashboard.php');
        } elseif ($result['role'] == 'producer') {
            header('Location: ../producer/dashboard.php');
        } else {
            header('Location: index.php');
        }
        exit();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Greenfield Local Hub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* ── Theme variables (mirrors index.php) ── */
        :root, [data-theme="default"] {
            --bg-primary:      #0d1f0f;
            --bg-secondary:    #122614;
            --bg-card:         #172d1a;
            --bg-nav:          #0a1a0c;
            --accent-primary:  #4caf50;
            --accent-bright:   #81c784;
            --accent-muted:    #2e7d32;
            --accent-gold:     #ffd54f;
            --text-primary:    #e8f5e9;
            --text-secondary:  #a5d6a7;
            --text-muted:      #66bb6a;
            --text-inverse:    #0d1f0f;
            --border-color:    #1e3d20;
            --border-subtle:   #1a3320;
            --shadow-card:     0 24px 64px rgba(0,0,0,0.55);
            --input-bg:        #1a2e1c;
            --input-border:    #2e5e30;
            --focus-ring:      0 0 0 3px rgba(76,175,80,0.45);
            --leaf-opacity:    0.07;
            --error-bg:        rgba(183,28,28,0.18);
            --error-border:    #c62828;
            --error-text:      #ef9a9a;
        }
        [data-theme="dark"] {
            --bg-primary:      #060e07;
            --bg-secondary:    #0a140b;
            --bg-card:         #0e1f10;
            --bg-nav:          #040a05;
            --accent-primary:  #66bb6a;
            --accent-bright:   #a5d6a7;
            --accent-muted:    #388e3c;
            --text-primary:    #f1f8e9;
            --text-secondary:  #c8e6c9;
            --text-muted:      #81c784;
            --border-color:    #152617;
            --input-bg:        #111d12;
            --input-border:    #244826;
            --leaf-opacity:    0.05;
        }
        [data-theme="high-contrast"] {
            --bg-primary:      #000000;
            --bg-card:         #0f0f0f;
            --bg-nav:          #000000;
            --accent-primary:  #00ff44;
            --accent-bright:   #00ff44;
            --accent-muted:    #00cc33;
            --accent-gold:     #ffff00;
            --text-primary:    #ffffff;
            --text-secondary:  #00ff44;
            --text-muted:      #00cc33;
            --border-color:    #00ff44;
            --input-bg:        #000;
            --input-border:    #00ff44;
            --focus-ring:      0 0 0 4px #00ff44;
            --leaf-opacity:    0;
            --error-text:      #ff6b6b;
            --error-border:    #ff0000;
        }
        [data-theme="low-contrast"] {
            --bg-primary:      #1a2e1c;
            --bg-card:         #223824;
            --bg-nav:          #182a1a;
            --accent-primary:  #6bab6e;
            --accent-bright:   #8dc490;
            --text-primary:    #c5dbc6;
            --text-secondary:  #9ec4a0;
            --text-muted:      #7aaa7d;
            --border-color:    #2e4e30;
            --input-bg:        #243c26;
            --input-border:    #3d6640;
            --leaf-opacity:    0.04;
        }
        [data-font-size="large"] { font-size: 118% !important; }
        [data-font-size="large"] label,
        [data-font-size="large"] input,
        [data-font-size="large"] .btn-submit { font-size: 1.05rem !important; }
        [data-font-size="large"] .card-title  { font-size: 2rem !important; }

        /* ── Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s, color 0.3s;
        }

        /* Skip link */
        .skip-link {
            position: absolute; top: -60px; left: 1rem;
            background: var(--accent-primary); color: var(--text-inverse);
            padding: 0.5rem 1rem; border-radius: 0 0 8px 8px;
            font-weight: 600; z-index: 9999; transition: top 0.2s;
            text-decoration: none;
        }
        .skip-link:focus { top: 0; }

        /* ── Accessibility toolbar ── */
        .a11y-toolbar {
            background: var(--bg-nav);
            border-bottom: 1px solid var(--border-color);
            padding: 6px 0;
        }
        .a11y-toolbar .inner {
            max-width: 1200px; margin: 0 auto; padding: 0 1.25rem;
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

        /* ── Top nav bar ── */
        .top-nav {
            background: var(--bg-nav);
            border-bottom: 1px solid var(--border-color);
            padding: 0.9rem 1.25rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .brand-link {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem; font-weight: 700;
            color: var(--accent-bright);
            text-decoration: none;
            display: flex; align-items: center; gap: 8px;
        }
        .brand-leaf {
            width: 30px; height: 30px;
            background: var(--accent-muted); border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .brand-leaf i { transform: rotate(45deg); font-size: 13px; color: white; }
        .brand-link:hover { color: var(--accent-primary); }
        .back-link {
            font-size: 0.82rem; color: var(--text-muted);
            text-decoration: none; display: flex; align-items: center; gap: 6px;
            transition: color 0.2s;
        }
        .back-link:hover { color: var(--accent-bright); }
        .back-link:focus-visible { outline: none; box-shadow: var(--focus-ring); border-radius: 4px; }

        /* ── Page layout ── */
        .page-body {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 3rem 1rem;
            position: relative; overflow: hidden;
        }

        /* Background decoration */
        .page-body::before, .page-body::after {
            content: '';
            position: absolute;
            border-radius: 50% 0 50% 0;
            opacity: var(--leaf-opacity);
            pointer-events: none;
        }
        .page-body::before {
            width: 480px; height: 480px;
            background: var(--accent-primary);
            top: -120px; right: -100px;
            transform: rotate(20deg);
        }
        .page-body::after {
            width: 320px; height: 320px;
            background: var(--accent-bright);
            bottom: -80px; left: -80px;
            transform: rotate(-15deg);
        }

        /* ── Login card ── */
        .login-card {
            position: relative; z-index: 2;
            width: 100%; max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 2.5rem 2.25rem;
            box-shadow: var(--shadow-card);
            animation: cardIn 0.4s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0)   scale(1); }
        }

        .card-eyebrow {
            text-transform: uppercase; letter-spacing: 0.1em;
            font-size: 0.7rem; font-weight: 700;
            color: var(--accent-gold);
            margin-bottom: 0.5rem;
            display: flex; align-items: center; gap: 6px;
        }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem; font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em; line-height: 1.2;
            margin-bottom: 0.4rem;
        }
        .card-subtitle {
            font-size: 0.85rem; color: var(--text-muted);
            margin-bottom: 2rem; line-height: 1.6;
        }

        /* ── Error message ── */
        .error-box {
            background: var(--error-bg, rgba(183,28,28,0.15));
            border: 1px solid var(--error-border, #c62828);
            border-left: 4px solid var(--error-border, #c62828);
            color: var(--error-text, #ef9a9a);
            border-radius: 10px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex; align-items: flex-start; gap: 8px;
            animation: shake 0.35s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        /* ── Form fields ── */
        .field-group { margin-bottom: 1.25rem; }

        .field-label {
            display: block;
            font-size: 0.8rem; font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.45rem;
            letter-spacing: 0.02em;
        }

        .field-wrap {
            position: relative;
        }
        .field-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.85rem; pointer-events: none;
            transition: color 0.2s;
        }
        .field-input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 10px;
            color: var(--text-primary);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.9rem;
            padding: 0.7rem 0.9rem 0.7rem 2.5rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            -webkit-appearance: none;
        }
        .field-input::placeholder { color: var(--text-muted); opacity: 0.6; }
        .field-input:focus {
            border-color: var(--accent-primary);
            box-shadow: var(--focus-ring);
        }
        .field-input:focus + .field-icon,
        .field-wrap:focus-within .field-icon {
            color: var(--accent-bright);
        }

        /* Password toggle */
        .pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); font-size: 0.85rem;
            padding: 4px; border-radius: 4px;
            transition: color 0.2s;
        }
        .pw-toggle:hover { color: var(--accent-bright); }
        .pw-toggle:focus-visible { outline: none; box-shadow: var(--focus-ring); border-radius: 4px; }
        .field-input.has-toggle { padding-right: 2.8rem; }

        /* ── Submit button ── */
        .btn-submit {
            width: 100%;
            background: var(--accent-muted);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            cursor: pointer;
            transition: all 0.25s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 0.5rem;
        }
        .btn-submit:hover:not(:disabled) {
            background: var(--accent-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(76,175,80,0.35);
        }
        .btn-submit:active:not(:disabled) { transform: translateY(0); }
        .btn-submit:disabled { opacity: 0.65; cursor: not-allowed; }
        .btn-submit:focus-visible { outline: none; box-shadow: var(--focus-ring); }

        /* ── Divider ── */
        .form-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 1.5rem 0;
            color: var(--text-muted); font-size: 0.75rem;
        }
        .form-divider::before, .form-divider::after {
            content: ''; flex: 1;
            height: 1px; background: var(--border-color);
        }

        /* ── Footer links ── */
        .card-footer-links {
            text-align: center; margin-top: 1.5rem;
            font-size: 0.84rem; color: var(--text-muted);
        }
        .card-footer-links a {
            color: var(--accent-bright);
            text-decoration: none; font-weight: 600;
            transition: color 0.2s;
        }
        .card-footer-links a:hover { color: var(--accent-primary); text-decoration: underline; }
        .card-footer-links a:focus-visible { outline: none; box-shadow: var(--focus-ring); border-radius: 3px; }

        /* ── Bottom page footer ── */
        .page-footer {
            background: var(--bg-nav);
            border-top: 1px solid var(--border-color);
            text-align: center;
            padding: 1rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .page-footer a { color: var(--text-muted); text-decoration: none; }
        .page-footer a:hover { color: var(--accent-bright); }

        /* Focus global */
        *:focus { outline: none; }
        *:focus-visible { box-shadow: var(--focus-ring) !important; outline: 2px solid transparent; border-radius: 4px; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.15s !important; }
        }
        @media (max-width: 480px) {
            .login-card { padding: 2rem 1.25rem; border-radius: 16px; }
        }
    </style>
</head>
<body>

<a class="skip-link" href="#main-content">Skip to main content</a>

<!-- Accessibility toolbar -->
<div class="a11y-toolbar" role="toolbar" aria-label="Accessibility options">
    <div class="inner">
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

<!-- Top nav -->
<nav class="top-nav" aria-label="Site navigation">
    <a class="brand-link" href="index.php" aria-label="Greenfield Local Hub home">
        <div class="brand-leaf" aria-hidden="true"><i class="fas fa-seedling"></i></div>
        Greenfield Local Hub
    </a>
    <a class="back-link" href="index.php">
        <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Home
    </a>
</nav>

<!-- Main content -->
<main id="main-content" class="page-body">
    <div class="login-card" role="region" aria-label="Login form">

        <p class="card-eyebrow"><i class="fas fa-seedling" aria-hidden="true"></i> Welcome back</p>
        <h1 class="card-title">Sign in to your account</h1>
        <p class="card-subtitle">Access your orders, favourites, and farm-fresh products.</p>

        <?php if ($error): ?>
        <div class="error-box" role="alert" aria-live="assertive">
            <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" novalidate>

            <div class="field-group">
                <label class="field-label" for="username">Username or Email</label>
                <div class="field-wrap">
                    <input
                        class="field-input"
                        type="text"
                        id="username"
                        name="username"
                        autocomplete="username"
                        placeholder="Enter your username or email"
                        required
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        aria-required="true"
                        aria-describedby="username-hint"
                    >
                    <i class="fas fa-user field-icon" aria-hidden="true"></i>
                </div>
            </div>

            <div class="field-group">
                <label class="field-label" for="password">Password</label>
                <div class="field-wrap">
                    <input
                        class="field-input has-toggle"
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        required
                        aria-required="true"
                    >
                    <i class="fas fa-lock field-icon" aria-hidden="true"></i>
                    <button type="button" class="pw-toggle" id="pwToggle"
                            aria-label="Show password" aria-pressed="false">
                        <i class="fas fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
                <span id="submitLabel">Sign In</span>
            </button>
        </form>

        <div class="form-divider" aria-hidden="true">or</div>

        <div class="card-footer-links">
            <p>Don't have an account? <a href="register.php">Create one free</a></p>
        </div>
    </div>
</main>

<footer class="page-footer" role="contentinfo">
    <p>&copy; 2024 <a href="index.php">Greenfield Local Hub</a>. All rights reserved.</p>
</footer>

<script>
(function () {
    'use strict';

    /* ---- Accessibility preferences (shared with index.php) ---- */
    const html      = document.documentElement;
    const THEME_KEY = 'glh_theme';
    const FONT_KEY  = 'glh_font';

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme || 'default');
        ['dark','high','low'].forEach(function(t) {
            var btn = document.getElementById('btn-theme-' + t);
            if (btn) { btn.classList.remove('active'); btn.setAttribute('aria-pressed','false'); }
        });
        var map = { dark: 'btn-theme-dark', 'high-contrast': 'btn-theme-high', 'low-contrast': 'btn-theme-low' };
        if (map[theme]) {
            var el = document.getElementById(map[theme]);
            if (el) { el.classList.add('active'); el.setAttribute('aria-pressed','true'); }
        }
        localStorage.setItem(THEME_KEY, theme || 'default');
    }

    function toggleTheme(theme) {
        applyTheme(html.getAttribute('data-theme') === theme ? 'default' : theme);
    }

    function applyFontSize(size) {
        html.setAttribute('data-font-size', size || 'normal');
        var btn = document.getElementById('btn-font-large');
        var isLarge = size === 'large';
        btn.classList.toggle('active', isLarge);
        btn.setAttribute('aria-pressed', isLarge ? 'true' : 'false');
        localStorage.setItem(FONT_KEY, size || 'normal');
    }

    function toggleFont() {
        applyFontSize(html.getAttribute('data-font-size') === 'large' ? 'normal' : 'large');
    }

    // Restore saved prefs immediately (before paint)
    var savedTheme = localStorage.getItem(THEME_KEY);
    var savedFont  = localStorage.getItem(FONT_KEY);
    if (savedTheme) applyTheme(savedTheme);
    if (savedFont)  applyFontSize(savedFont);

    document.getElementById('btn-theme-dark').addEventListener('click', function(){ toggleTheme('dark'); });
    document.getElementById('btn-theme-high').addEventListener('click', function(){ toggleTheme('high-contrast'); });
    document.getElementById('btn-theme-low').addEventListener('click',  function(){ toggleTheme('low-contrast'); });
    document.getElementById('btn-font-large').addEventListener('click', toggleFont);
    document.getElementById('btn-reset').addEventListener('click', function(){
        applyTheme('default'); applyFontSize('normal');
    });

    /* ---- Password visibility toggle ---- */
    var pwInput  = document.getElementById('password');
    var pwToggle = document.getElementById('pwToggle');
    var pwIcon   = pwToggle.querySelector('i');

    pwToggle.addEventListener('click', function() {
        var isHidden = pwInput.type === 'password';
        pwInput.type = isHidden ? 'text' : 'password';
        pwIcon.className = isHidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        pwToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        pwToggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        pwInput.focus();
    });

    /* ---- Form submission with loading state ---- */
    document.getElementById('loginForm').addEventListener('submit', function() {
        var btn   = document.getElementById('submitBtn');
        var label = document.getElementById('submitLabel');
        btn.disabled = true;
        label.textContent = 'Signing in…';
        btn.querySelector('i').className = 'fas fa-spinner fa-spin';
        // Let the native POST proceed — no AJAX (avoids the broken AJAX logic in original)
    });
})();
</script>
</body>
</html>