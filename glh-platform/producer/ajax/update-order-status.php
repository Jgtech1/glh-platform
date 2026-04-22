<?php
session_start();
require_once '../../classes/Order.php';
require_once '../../classes/User.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'producer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

if ($orderId <= 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

$orderObj = new Order();
$result = $orderObj->updateOrderStatus($orderId, $status);

echo json_encode(['success' => $result]);
?>