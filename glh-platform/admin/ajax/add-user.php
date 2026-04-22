<?php
session_start();
require_once '../../../classes/User.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$userObj = new User();

$data = [
    'username' => $_POST['username'],
    'email' => $_POST['email'],
    'password' => $_POST['password'],
    'full_name' => $_POST['full_name'],
    'phone' => $_POST['phone'] ?? '',
    'role' => $_POST['role']
];

$result = $userObj->register($data);

if ($result['success']) {
    header('Location: ../users.php?success=1');
} else {
    $error = implode(', ', $result['errors']);
    header('Location: ../users.php?error=' . urlencode($error));
}
?>