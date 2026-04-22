<?php
session_start();
require_once '../classes/User.php';
require_once '../classes/CurrencyManager.php';

$userObj = new User();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../public/login.php');
    exit();
}

$currencyManager = new CurrencyManager();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_rate') {
            $currencyManager->updateExchangeRate($_POST['currency_code'], $_POST['exchange_rate']);
            $success = "Exchange rate updated!";
        } elseif ($_POST['action'] == 'add_currency') {
            $currencyManager->addCurrency($_POST);
            $success = "Currency added!";
        }
    }
}

$currencies = $currencyManager->getAllCurrencies();
$baseCurrency = $currencyManager->getBaseCurrency();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Currencies - GLH Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-user-shield"></i> Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="manage-content.php">Manage Content</a>
                <a class="nav-link" href="manage-currency.php">Manage Currency</a>
                <a class="nav-link" href="../public/logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Currency Management</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <h6>Base Currency: <?php echo $baseCurrency; ?></h6>
                        <table class="table">
                            <thead>
                                <tr><th>Currency</th><th>Symbol</th><th>Exchange Rate</th><th>Status</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($currencies as $currency): ?>
                                <tr>
                                    <td><?php echo $currency['currency_code']; ?> - <?php echo $currency['currency_name']; ?></td>
                                    <td><?php echo $currency['currency_symbol']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_rate">
                                            <input type="hidden" name="currency_code" value="<?php echo $currency['currency_code']; ?>">
                                            <input type="number" name="exchange_rate" value="<?php echo $currency['exchange_rate']; ?>" step="0.0001" style="width:100px;">
                                            <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($currency['is_base_currency']): ?>
                                            <span class="badge bg-success">Base Currency</span>
                                        <?php else: ?>
                                            <span class="badge bg-info"><?php echo $currency['is_active'] ? 'Active' : 'Inactive'; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$currency['is_base_currency']): ?>
                                            <button class="btn btn-sm btn-danger">Disable</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Add New Currency</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_currency">
                            <div class="mb-3">
                                <label>Currency Code (3 letters)</label>
                                <input type="text" name="currency_code" class="form-control" required maxlength="3">
                            </div>
                            <div class="mb-3">
                                <label>Currency Symbol</label>
                                <input type="text" name="currency_symbol" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Currency Name</label>
                                <input type="text" name="currency_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Exchange Rate (1 USD = ?)</label>
                                <input type="number" name="exchange_rate" class="form-control" step="0.0001" required>
                            </div>
                            <div class="mb-3">
                                <label>Decimal Places</label>
                                <input type="number" name="decimal_places" class="form-control" value="2">
                            </div>
                            <button type="submit" class="btn btn-success">Add Currency</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>