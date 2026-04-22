<?php
require_once 'includes/header.php';

// Handle currency updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_rate'])) {
        $currency_code = $_POST['currency_code'];
        $exchange_rate = $_POST['exchange_rate'];
        $currencyManager->updateExchangeRate($currency_code, $exchange_rate);
        echo '<script>Swal.fire("Success!", "Exchange rate updated!", "success").then(() => { location.reload(); });</script>';
    } elseif (isset($_POST['add_currency'])) {
        $data = [
            'currency_code' => strtoupper($_POST['currency_code']),
            'currency_symbol' => $_POST['currency_symbol'],
            'currency_name' => $_POST['currency_name'],
            'exchange_rate' => $_POST['exchange_rate'],
            'decimal_places' => $_POST['decimal_places'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_base_currency' => isset($_POST['is_base_currency']) ? 1 : 0
        ];
        
        if ($currencyManager->addCurrency($data)) {
            echo '<script>Swal.fire("Success!", "Currency added successfully!", "success").then(() => { location.reload(); });</script>';
        } else {
            echo '<script>Swal.fire("Error!", "Failed to add currency", "error");</script>';
        }
    } elseif (isset($_POST['toggle_currency'])) {
        $currency_code = $_POST['currency_code'];
        $is_active = $_POST['is_active'];
        $stmt = $conn->prepare("UPDATE currency_settings SET is_active = ? WHERE currency_code = ?");
        $stmt->execute([$is_active, $currency_code]);
        echo '<script>Swal.fire("Success!", "Currency status updated!", "success").then(() => { location.reload(); });</script>';
    }
}

// Get all currencies
$currencies = $currencyManager->getAllCurrencies();
$baseCurrency = $currencyManager->getBaseCurrency();
?>

<div class="row">
    <div class="col-md-7">
        <div class="table-container">
            <h5><i class="fas fa-dollar-sign"></i> Currency Settings</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Currency</th>
                            <th>Code</th>
                            <th>Symbol</th>
                            <th>Exchange Rate (1 USD = ?)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currencies as $currency): ?>
                        <tr>
                            <td>
                                <?php echo $currency['currency_name']; ?>
                                <?php if ($currency['is_base_currency']): ?>
                                    <span class="badge bg-warning">Base</span>
                                <?php endif; ?>
                             </td>
                            <td><strong><?php echo $currency['currency_code']; ?></strong></td>
                            <td><?php echo $currency['currency_symbol']; ?></td>
                            <td>
                                <?php if (!$currency['is_base_currency']): ?>
                                    <form method="POST" style="display: inline-flex; gap: 5px;">
                                        <input type="hidden" name="currency_code" value="<?php echo $currency['currency_code']; ?>">
                                        <input type="number" name="exchange_rate" value="<?php echo $currency['exchange_rate']; ?>" step="0.0001" style="width: 100px;" class="form-control form-control-sm">
                                        <button type="submit" name="update_rate" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">1.0000 (Base)</span>
                                <?php endif; ?>
                             </td>
                            <td>
                                <span class="badge <?php echo $currency['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $currency['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                             </td>
                            <td>
                                <?php if (!$currency['is_base_currency']): ?>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="currency_code" value="<?php echo $currency['currency_code']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $currency['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" name="toggle_currency" class="btn btn-sm btn-<?php echo $currency['is_active'] ? 'warning' : 'success'; ?>">
                                            <i class="fas fa-<?php echo $currency['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            <?php echo $currency['is_active'] ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="table-container mt-4">
            <h5><i class="fas fa-chart-line"></i> Currency Conversion Preview</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Amount (USD)</th><th>Converted Amount</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $testAmounts = [10, 25, 50, 100];
                        foreach ($currencies as $currency):
                            if ($currency['is_active']):
                        ?>
                        <tr>
                            <td><strong><?php echo $currency['currency_code']; ?></strong></td>
                            <td>
                                <?php 
                                foreach ($testAmounts as $amount):
                                    $converted = $currencyManager->convert($amount, 'USD', $currency['currency_code']);
                                    echo $currencyManager->formatPrice($converted, $currency['currency_code']) . '<br>';
                                endforeach;
                                ?>
                            </td>
                         </tr>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-5">
        <div class="table-container">
            <h5><i class="fas fa-plus"></i> Add New Currency</h5>
            <form method="POST">
                <div class="mb-3">
                    <label>Currency Code *</label>
                    <input type="text" name="currency_code" class="form-control" placeholder="EUR" maxlength="3" required>
                    <small class="text-muted">3-letter ISO currency code</small>
                </div>
                <div class="mb-3">
                    <label>Currency Symbol *</label>
                    <input type="text" name="currency_symbol" class="form-control" placeholder="€" required>
                </div>
                <div class="mb-3">
                    <label>Currency Name *</label>
                    <input type="text" name="currency_name" class="form-control" placeholder="Euro" required>
                </div>
                <div class="mb-3">
                    <label>Exchange Rate (1 USD = ?) *</label>
                    <input type="number" name="exchange_rate" class="form-control" step="0.0001" placeholder="0.9200" required>
                </div>
                <div class="mb-3">
                    <label>Decimal Places</label>
                    <input type="number" name="decimal_places" class="form-control" value="2">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                    <label class="form-check-label">Active</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_base_currency" class="form-check-input" value="1">
                    <label class="form-check-label">Set as Base Currency</label>
                    <small class="text-muted d-block">Only one base currency allowed</small>
                </div>
                <button type="submit" name="add_currency" class="btn btn-primary w-100">Add Currency</button>
            </form>
        </div>
        
        <div class="table-container mt-4">
            <h5><i class="fas fa-info-circle"></i> About Currencies</h5>
            <div class="alert alert-info">
                <i class="fas fa-lightbulb"></i> <strong>Base Currency:</strong> <?php echo $baseCurrency; ?> is set as the base currency.<br><br>
                <i class="fas fa-exchange-alt"></i> <strong>Exchange Rates:</strong> All prices are stored in USD and converted using the rates above.<br><br>
                <i class="fas fa-sync"></i> <strong>Auto Conversion:</strong> Customers can switch currencies and see converted prices.
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>