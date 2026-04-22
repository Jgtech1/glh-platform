<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'glh_platform');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_URL', 'http://localhost:8080/glh-platform/');
define('SITE_NAME', 'Greenfield Local Hub');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>