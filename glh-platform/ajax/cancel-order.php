<?php
session_start();
require_once '../classes/Order.php';
require_once '../classes/User.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to cancel orders']);
    exit();
}

// Check if user is customer
if ($_SESSION['role'] != 'customer') {
    echo json_encode(['success' => false, 'message' => 'Only customers can cancel orders']);
    exit();
}

// Get order ID
$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

// Cancel order
$orderObj = new Order();
$result = $orderObj->cancelOrder($orderId, $_SESSION['user_id']);

echo json_encode($result);
?>