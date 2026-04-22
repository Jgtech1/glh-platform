<?php
require_once '../classes/User.php';
$userObj = new User();

if ($userObj->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error   = '';
$success = '';
$post    = $_POST; // preserve form values on error

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result = $userObj->register($post);

    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = implode('<br>', $result['errors']);
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Greenfield Local Hub</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ── Theme variables ── */
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
            --success-bg:      rgba(27,94,32,0.25);
            --success-border:  #2e7d32;
            --success-text:    #a5d6a7;
        }
        [data-theme="dark"] {
            --bg-primary:      #060e07;
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
        [data-font-size="large"] select,
        [data-font-size="large"] textarea,
        [data-font-size="large"] .btn-submit { font-size: 1.05rem !important; }
        [data-font-size="large"] .card-title  { font-size: 1.9rem !important; }

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

        /* ── Top nav ── */
        .top-nav {
            background: var(--bg-nav);
            border-bottom: 1px solid var(--border-color);
            padding: 0.9rem 1.25rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .brand-link {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem; font-weight: 700;
            color: var(--accent-bright); text-decoration: none;
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

        /* ── Page body ── */
        .page-body {
            flex: 1; display: flex; align-items: flex-start; justify-content: center;
            padding: 3rem 1rem 4rem;
            position: relative; overflow: hidden;
        }
        .page-body::before, .page-body::after {
            content: ''; position: absolute;
            border-radius: 50% 0 50% 0; opacity: var(--leaf-opacity); pointer-events: none;
        }
        .page-body::before {
            width: 500px; height: 500px; background: var(--accent-primary);
            top: -100px; right: -100px; transform: rotate(20deg);
        }
        .page-body::after {
            width: 320px; height: 320px; background: var(--accent-bright);
            bottom: -80px; left: -80px; transform: rotate(-15deg);
        }

        /* ── Register card ── */
        .register-card {
            position: relative; z-index: 2;
            width: 100%; max-width: 520px;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 20px;
            padding: 2.5rem 2.25rem;
            box-shadow: var(--shadow-card);
            animation: cardIn 0.4s cubic-bezier(0.22,1,0.36,1) both;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-eyebrow {
            text-transform: uppercase; letter-spacing: 0.1em;
            font-size: 0.7rem; font-weight: 700;
            color: var(--accent-gold); margin-bottom: 0.5rem;
            display: flex; align-items: center; gap: 6px;
        }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem; font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em; line-height: 1.2; margin-bottom: 0.4rem;
        }
        .card-subtitle {
            font-size: 0.85rem; color: var(--text-muted);
            margin-bottom: 1.75rem; line-height: 1.6;
        }

        /* ── Alerts ── */
        .error-box {
            background: var(--error-bg, rgba(183,28,28,0.15));
            border: 1px solid var(--error-border, #c62828);
            border-left: 4px solid var(--error-border, #c62828);
            color: var(--error-text, #ef9a9a);
            border-radius: 10px; padding: 0.85rem 1rem;
            margin-bottom: 1.5rem; font-size: 0.875rem;
            display: flex; align-items: flex-start; gap: 8px;
            animation: shake 0.35s ease;
        }
        .success-box {
            background: var(--success-bg, rgba(27,94,32,0.2));
            border: 1px solid var(--success-border, #2e7d32);
            border-left: 4px solid var(--success-border, #2e7d32);
            color: var(--success-text, #a5d6a7);
            border-radius: 10px; padding: 0.85rem 1rem;
            margin-bottom: 1.5rem; font-size: 0.875rem;
            display: flex; align-items: flex-start; gap: 8px;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-6px); }
            40%       { transform: translateX(6px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        /* ── Form section labels ── */
        .form-section {
            margin-bottom: 0.25rem;
            margin-top: 1.4rem;
        }
        .form-section-label {
            font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.1em;
            font-weight: 700; color: var(--accent-gold);
            display: flex; align-items: center; gap: 6px;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }
        .form-section:first-of-type { margin-top: 0; }

        /* ── Two-column row ── */
        .field-row {
            display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;
        }
        @media (max-width: 480px) { .field-row { grid-template-columns: 1fr; } }

        /* ── Field ── */
        .field-group { margin-bottom: 1.1rem; }
        .field-label {
            display: block; font-size: 0.8rem; font-weight: 600;
            color: var(--text-secondary); margin-bottom: 0.4rem; letter-spacing: 0.02em;
        }
        .field-label .req {
            color: var(--accent-gold); margin-left: 2px; font-size: 0.7rem;
        }
        .field-hint {
            display: block; font-size: 0.73rem; color: var(--text-muted);
            margin-top: 0.3rem;
        }

        .field-wrap { position: relative; }
        .field-icon {
            position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 0.82rem; pointer-events: none;
            transition: color 0.2s;
        }
        textarea ~ .field-icon { top: 14px; transform: none; }

        .field-input {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 10px;
            color: var(--text-primary);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.88rem;
            padding: 0.65rem 0.9rem 0.65rem 2.4rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none; -webkit-appearance: none; resize: vertical;
        }
        .field-input::placeholder { color: var(--text-muted); opacity: 0.55; }
        .field-input:focus {
            border-color: var(--accent-primary);
            box-shadow: var(--focus-ring);
        }
        .field-wrap:focus-within .field-icon { color: var(--accent-bright); }

        /* Select arrow */
        select.field-input {
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2366bb6a' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            padding-right: 2.5rem;
            cursor: pointer;
        }
        select.field-input option { background: var(--bg-card); color: var(--text-primary); }

        /* Password toggle */
        .pw-toggle {
            position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); font-size: 0.82rem;
            padding: 4px; border-radius: 4px; transition: color 0.2s;
        }
        .pw-toggle:hover { color: var(--accent-bright); }
        .pw-toggle:focus-visible { outline: none; box-shadow: var(--focus-ring); border-radius: 4px; }
        .field-input.has-toggle { padding-right: 2.6rem; }

        /* Role selector cards */
        .role-cards {
            display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;
            margin-bottom: 0.25rem;
        }
        .role-card input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
        .role-card label {
            display: flex; flex-direction: column; align-items: center;
            gap: 8px; padding: 1rem 0.75rem;
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            border-radius: 12px; cursor: pointer;
            transition: all 0.2s; text-align: center;
            font-size: 0.82rem; font-weight: 600; color: var(--text-secondary);
        }
        .role-card label i { font-size: 1.4rem; color: var(--text-muted); transition: color 0.2s; }
        .role-card label .role-desc { font-size: 0.7rem; font-weight: 400; color: var(--text-muted); margin-top: 2px; }
        .role-card input:checked + label {
            border-color: var(--accent-primary);
            background: rgba(76,175,80,0.1);
            color: var(--accent-bright);
        }
        .role-card input:checked + label i { color: var(--accent-bright); }
        .role-card label:hover {
            border-color: var(--accent-muted);
            color: var(--accent-bright);
        }
        .role-card input:focus-visible + label { box-shadow: var(--focus-ring); border-radius: 12px; }

        /* ── Submit ── */
        .btn-submit {
            width: 100%;
            background: var(--accent-muted); color: white; border: none;
            border-radius: 10px; padding: 0.8rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem; font-weight: 700; letter-spacing: 0.03em;
            cursor: pointer; transition: all 0.25s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-top: 1.5rem;
        }
        .btn-submit:hover:not(:disabled) {
            background: var(--accent-primary);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(76,175,80,0.35);
        }
        .btn-submit:disabled { opacity: 0.65; cursor: not-allowed; }
        .btn-submit:focus-visible { outline: none; box-shadow: var(--focus-ring); }

        /* ── Divider / footer ── */
        .form-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 1.5rem 0; color: var(--text-muted); font-size: 0.75rem;
        }
        .form-divider::before, .form-divider::after {
            content: ''; flex: 1; height: 1px; background: var(--border-color);
        }
        .card-footer-links {
            text-align: center; font-size: 0.84rem; color: var(--text-muted);
        }
        .card-footer-links a {
            color: var(--accent-bright); text-decoration: none; font-weight: 600; transition: color 0.2s;
        }
        .card-footer-links a:hover { color: var(--accent-primary); text-decoration: underline; }
        .card-footer-links a:focus-visible { outline: none; box-shadow: var(--focus-ring); border-radius: 3px; }

        /* ── Page footer ── */
        .page-footer {
            background: var(--bg-nav); border-top: 1px solid var(--border-color);
            text-align: center; padding: 1rem;
            font-size: 0.75rem; color: var(--text-muted);
        }
        .page-footer a { color: var(--text-muted); text-decoration: none; }
        .page-footer a:hover { color: var(--accent-bright); }

        /* ── Password strength ── */
        .strength-bar {
            height: 3px; border-radius: 2px; margin-top: 6px;
            background: var(--border-color); overflow: hidden;
        }
        .strength-fill {
            height: 100%; border-radius: 2px;
            transition: width 0.3s, background 0.3s;
            width: 0%;
        }
        .strength-text {
            font-size: 0.7rem; margin-top: 4px; color: var(--text-muted);
        }

        /* Focus / reduced motion */
        *:focus { outline: none; }
        *:focus-visible { box-shadow: var(--focus-ring) !important; outline: 2px solid transparent; border-radius: 4px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: 0.01ms !important; transition-duration: 0.15s !important; }
        }
        @media (max-width: 480px) {
            .register-card { padding: 1.75rem 1.2rem; border-radius: 16px; }
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
    <a class="back-link" href="login.php">
        <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Login
    </a>
</nav>

<!-- Main -->
<main id="main-content" class="page-body">
    <div class="register-card" role="region" aria-label="Registration form">

        <p class="card-eyebrow"><i class="fas fa-leaf" aria-hidden="true"></i> Join the community</p>
        <h1 class="card-title">Create your account</h1>
        <p class="card-subtitle">Fresh produce, local farmers, delivered to your door.</p>

        <?php if ($error): ?>
        <div class="error-box" role="alert" aria-live="assertive">
            <i class="fas fa-circle-exclamation" style="flex-shrink:0;margin-top:2px" aria-hidden="true"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="success-box" role="status" aria-live="polite">
            <i class="fas fa-circle-check" style="flex-shrink:0;margin-top:2px" aria-hidden="true"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="registerForm" novalidate>

            <!-- Account info -->
            <div class="form-section">
                <p class="form-section-label"><i class="fas fa-user-circle" aria-hidden="true"></i> Account Info</p>
                <div class="field-row">
                    <div class="field-group">
                        <label class="field-label" for="username">Username <span class="req" aria-hidden="true">*</span></label>
                        <div class="field-wrap">
                            <input class="field-input" type="text" id="username" name="username"
                                   autocomplete="username" placeholder="e.g. john_doe"
                                   required aria-required="true"
                                   value="<?php echo htmlspecialchars($post['username'] ?? ''); ?>">
                            <i class="fas fa-at field-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="full_name">Full Name <span class="req" aria-hidden="true">*</span></label>
                        <div class="field-wrap">
                            <input class="field-input" type="text" id="full_name" name="full_name"
                                   autocomplete="name" placeholder="Your full name"
                                   required aria-required="true"
                                   value="<?php echo htmlspecialchars($post['full_name'] ?? ''); ?>">
                            <i class="fas fa-id-card field-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label" for="email">Email Address <span class="req" aria-hidden="true">*</span></label>
                    <div class="field-wrap">
                        <input class="field-input" type="email" id="email" name="email"
                               autocomplete="email" placeholder="you@example.com"
                               required aria-required="true"
                               value="<?php echo htmlspecialchars($post['email'] ?? ''); ?>">
                        <i class="fas fa-envelope field-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label" for="password">Password <span class="req" aria-hidden="true">*</span></label>
                    <div class="field-wrap">
                        <input class="field-input has-toggle" type="password" id="password" name="password"
                               autocomplete="new-password" placeholder="Minimum 6 characters"
                               required aria-required="true" aria-describedby="pw-strength-text">
                        <i class="fas fa-lock field-icon" aria-hidden="true"></i>
                        <button type="button" class="pw-toggle" id="pwToggle"
                                aria-label="Show password" aria-pressed="false">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="strength-bar" aria-hidden="true">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <span class="strength-text" id="pw-strength-text" aria-live="polite"></span>
                </div>
            </div>

            <!-- Contact info -->
            <div class="form-section">
                <p class="form-section-label"><i class="fas fa-address-book" aria-hidden="true"></i> Contact Details</p>
                <div class="field-group">
                    <label class="field-label" for="phone">Phone Number</label>
                    <div class="field-wrap">
                        <input class="field-input" type="tel" id="phone" name="phone"
                               autocomplete="tel" placeholder="+1 (555) 000-0000"
                               value="<?php echo htmlspecialchars($post['phone'] ?? ''); ?>">
                        <i class="fas fa-phone field-icon" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label" for="address">Delivery Address</label>
                    <div class="field-wrap">
                        <textarea class="field-input" id="address" name="address"
                                  rows="2" placeholder="Street, City, Postcode"
                                  style="padding-top:0.65rem"
                                  autocomplete="street-address"><?php echo htmlspecialchars($post['address'] ?? ''); ?></textarea>
                        <i class="fas fa-map-marker-alt field-icon" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <!-- Role -->
            <div class="form-section">
                <p class="form-section-label"><i class="fas fa-user-tag" aria-hidden="true"></i> I want to join as</p>
                <div class="role-cards" role="radiogroup" aria-label="Account type">
                    <div class="role-card">
                        <input type="radio" id="role-customer" name="role" value="customer"
                               <?php echo (!isset($post['role']) || $post['role'] === 'customer') ? 'checked' : ''; ?>>
                        <label for="role-customer">
                            <i class="fas fa-shopping-basket" aria-hidden="true"></i>
                            Customer
                            <span class="role-desc">Browse & buy fresh produce</span>
                        </label>
                    </div>
                    <div class="role-card">
                        <input type="radio" id="role-producer" name="role" value="producer"
                               <?php echo (isset($post['role']) && $post['role'] === 'producer') ? 'checked' : ''; ?>>
                        <label for="role-producer">
                            <i class="fas fa-tractor" aria-hidden="true"></i>
                            Producer
                            <span class="role-desc">Sell your farm products</span>
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-seedling" aria-hidden="true"></i>
                <span id="submitLabel">Create Account</span>
            </button>
        </form>

        <div class="form-divider" aria-hidden="true">or</div>

        <div class="card-footer-links">
            <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>
</main>

<footer class="page-footer" role="contentinfo">
    <p>&copy; 2024 <a href="index.php">Greenfield Local Hub</a>. All rights reserved.</p>
</footer>

<?php if ($success): ?>
<script>
    // Redirect after successful registration
    Swal.fire({
        title: 'Account Created!',
        text: 'Welcome to Greenfield! Please sign in.',
        icon: 'success',
        confirmButtonText: 'Go to Login',
        background: '#172d1a',
        color: '#e8f5e9',
        iconColor: '#4caf50',
        confirmButtonColor: '#2e7d32'
    }).then(function() {
        window.location.href = 'login.php';
    });
</script>
<?php endif; ?>

<script>
(function () {
    'use strict';

    /* ---- Accessibility (shared theme engine) ---- */
    var html      = document.documentElement;
    var THEME_KEY = 'glh_theme';
    var FONT_KEY  = 'glh_font';

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
    function toggleTheme(theme) { applyTheme(html.getAttribute('data-theme') === theme ? 'default' : theme); }

    function applyFontSize(size) {
        html.setAttribute('data-font-size', size || 'normal');
        var btn = document.getElementById('btn-font-large');
        var large = size === 'large';
        btn.classList.toggle('active', large);
        btn.setAttribute('aria-pressed', large ? 'true' : 'false');
        localStorage.setItem(FONT_KEY, size || 'normal');
    }
    function toggleFont() { applyFontSize(html.getAttribute('data-font-size') === 'large' ? 'normal' : 'large'); }

    var savedTheme = localStorage.getItem(THEME_KEY);
    var savedFont  = localStorage.getItem(FONT_KEY);
    if (savedTheme) applyTheme(savedTheme);
    if (savedFont)  applyFontSize(savedFont);

    document.getElementById('btn-theme-dark').addEventListener('click', function(){ toggleTheme('dark'); });
    document.getElementById('btn-theme-high').addEventListener('click', function(){ toggleTheme('high-contrast'); });
    document.getElementById('btn-theme-low').addEventListener('click',  function(){ toggleTheme('low-contrast'); });
    document.getElementById('btn-font-large').addEventListener('click', toggleFont);
    document.getElementById('btn-reset').addEventListener('click', function(){ applyTheme('default'); applyFontSize('normal'); });

    /* ---- Password visibility toggle ---- */
    var pwInput  = document.getElementById('password');
    var pwToggle = document.getElementById('pwToggle');
    var pwIcon   = pwToggle.querySelector('i');
    pwToggle.addEventListener('click', function() {
        var hidden = pwInput.type === 'password';
        pwInput.type = hidden ? 'text' : 'password';
        pwIcon.className = hidden ? 'fas fa-eye-slash' : 'fas fa-eye';
        pwToggle.setAttribute('aria-label',   hidden ? 'Hide password' : 'Show password');
        pwToggle.setAttribute('aria-pressed',  hidden ? 'true' : 'false');
        pwInput.focus();
    });

    /* ---- Password strength meter ---- */
    var fill  = document.getElementById('strengthFill');
    var label = document.getElementById('pw-strength-text');
    var levels = [
        { max: 0,  pct: 0,   color: 'transparent',  text: '' },
        { max: 2,  pct: 25,  color: '#e53935',       text: 'Weak' },
        { max: 3,  pct: 50,  color: '#fb8c00',       text: 'Fair' },
        { max: 4,  pct: 75,  color: '#fdd835',       text: 'Good' },
        { max: 99, pct: 100, color: '#43a047',       text: 'Strong ✓' }
    ];
    pwInput.addEventListener('input', function() {
        var v = pwInput.value;
        var score = 0;
        if (v.length >= 6)               score++;
        if (v.length >= 10)              score++;
        if (/[A-Z]/.test(v))             score++;
        if (/[0-9]/.test(v))             score++;
        if (/[^A-Za-z0-9]/.test(v))      score++;
        var lvl = levels[Math.min(score, levels.length - 1)];
        fill.style.width      = lvl.pct + '%';
        fill.style.background = lvl.color;
        label.textContent     = v.length ? lvl.text : '';
    });

    /* ---- Submit loading state ---- */
    document.getElementById('registerForm').addEventListener('submit', function() {
        var btn   = document.getElementById('submitBtn');
        var lbl   = document.getElementById('submitLabel');
        btn.disabled = true;
        lbl.textContent = 'Creating account…';
        btn.querySelector('i').className = 'fas fa-spinner fa-spin';
    });
})();
</script>
</body>
</html>