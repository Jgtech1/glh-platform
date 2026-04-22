<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// If a session cookie is used, delete it
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Also clear any currency cookie if set
if (isset($_COOKIE['user_currency'])) {
    setcookie('user_currency', '', time() - 3600, '/');
}

// Redirect to login page (go up one level to public folder)
header('Location: ../public/index.php?message=loggedout');
exit();
?>