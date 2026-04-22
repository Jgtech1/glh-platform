<?php
session_start();
require_once '../classes/Cart.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$cartId = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($cartId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit();
}

$cartObj = new Cart();
$result = $cartObj->updateCartItem($cartId, $quantity);

echo json_encode(['success' => $result, 'message' => $result ? 'Updated' : 'Failed to update']);
?>