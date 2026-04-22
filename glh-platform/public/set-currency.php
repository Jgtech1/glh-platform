<?php
session_start();

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$currencyCode = isset($_POST['currency']) ? strtoupper(trim($_POST['currency'])) : '';

$allowedCurrencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'INR', 'JPY'];

if (empty($currencyCode) || !in_array($currencyCode, $allowedCurrencies)) {
    echo json_encode(['success' => false, 'message' => 'Invalid currency code']);
    exit();
}

$_SESSION['currency'] = $currencyCode;
setcookie('user_currency', $currencyCode, time() + (86400 * 30), "/");

// If user is logged in, persist preference to DB
if (isset($_SESSION['user_id'])) {
    try {
        require_once '../classes/Database.php';
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("UPDATE users SET preferred_currency = ? WHERE id = ?");
        $stmt->execute([$currencyCode, $_SESSION['user_id']]);
    } catch (Exception $e) {
        // Non-fatal — session/cookie already set above
        error_log("Currency preference save failed: " . $e->getMessage());
    }
}

echo json_encode(['success' => true, 'currency' => $currencyCode]);
exit();
?>