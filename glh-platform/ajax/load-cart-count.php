<?php
session_start();
require_once '../classes/Cart.php';

header('Content-Type: text/plain');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '0';
    exit;
}

// Check if user is customer
if ($_SESSION['role'] != 'customer') {
    echo '0';
    exit;
}

$cartObj = new Cart();
$count = $cartObj->getCartCount($_SESSION['user_id']);
echo $count;
?>