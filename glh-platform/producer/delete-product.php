<?php
session_start();
require_once '../../classes/Product.php';
require_once '../../classes/User.php';

$userObj = new User();
$productObj = new Product();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'producer') {
    header('Location: ../public/login.php');
    exit();
}

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId > 0) {
    $productObj->deleteProduct($productId, $_SESSION['user_id']);
}

header('Location: products.php');
exit();
?>