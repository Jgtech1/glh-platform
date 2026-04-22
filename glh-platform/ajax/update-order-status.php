<?php
session_start();
require_once '../classes/Order.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'producer' && $_SESSION['role'] != 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$orderId = $_POST['order_id'] ?? 0;
$status = $_POST['status'] ?? '';

$orderObj = new Order();
$result = $orderObj->updateOrderStatus($orderId, $status);

echo json_encode(['success' => $result]);
?>