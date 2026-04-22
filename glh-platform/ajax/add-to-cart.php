<?php
session_start();
require_once '../classes/Cart.php';

header('Content-Type: application/json');

// Enable error logging
error_log("Add to cart request received");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit();
}

// Check if user is customer
if ($_SESSION['role'] != 'customer') {
    echo json_encode(['success' => false, 'message' => 'Only customers can add items to cart']);
    exit();
}

// Get product ID and quantity
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

error_log("Product ID: $productId, Quantity: $quantity, User ID: {$_SESSION['user_id']}");

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

// Add to cart
$cartObj = new Cart();
$result = $cartObj->addToCart($_SESSION['user_id'], $productId, $quantity);

error_log("Add to cart result: " . ($result ? "Success" : "Failed"));

if ($result) {
    // Get updated cart count
    $cartCount = $cartObj->getCartCount($_SESSION['user_id']);
    echo json_encode(['success' => true, 'message' => 'Product added to cart', 'cart_count' => $cartCount]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add product. Please check stock availability.']);
}
?>