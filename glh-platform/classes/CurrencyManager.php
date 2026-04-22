<?php
require_once 'Database.php';

class CurrencyManager {
    private $db;
    private $conn;
    private $currencies = [];
    private $baseCurrency = null;
    private $userCurrency = null;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->loadCurrencies();
        $this->setUserCurrency();
    }
    
    private function loadCurrencies() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM currency_settings WHERE is_active = TRUE ORDER BY is_base_currency DESC, currency_code");
            $stmt->execute();
            $this->currencies = $stmt->fetchAll();
            
            // If no currencies found, create default
            if (empty($this->currencies)) {
                $this->createDefaultCurrencies();
                $stmt = $this->conn->prepare("SELECT * FROM currency_settings WHERE is_active = TRUE ORDER BY is_base_currency DESC, currency_code");
                $stmt->execute();
                $this->currencies = $stmt->fetchAll();
            }
            
            foreach ($this->currencies as $currency) {
                if ($currency['is_base_currency']) {
                    $this->baseCurrency = $currency;
                }
            }
            
            // If no base currency set, use first one
            if (!$this->baseCurrency && !empty($this->currencies)) {
                $this->baseCurrency = $this->currencies[0];
            }
            
        } catch (PDOException $e) {
            error_log("Error loading currencies: " . $e->getMessage());
            $this->currencies = [];
        }
    }
    
    private function createDefaultCurrencies() {
        try {
            $defaults = [
                ['USD', '$', 'US Dollar', 1.0000, true, true, 2],
                ['EUR', '€', 'Euro', 0.9200, false, true, 2],
                ['GBP', '£', 'British Pound', 0.7900, false, true, 2],
                ['CAD', 'C$', 'Canadian Dollar', 1.3500, false, true, 2],
                ['AUD', 'A$', 'Australian Dollar', 1.5200, false, true, 2],
                ['INR', '₹', 'Indian Rupee', 83.5000, false, true, 2],
                ['JPY', '¥', 'Japanese Yen', 150.5000, false, true, 0]
            ];
            
            $stmt = $this->conn->prepare("
                INSERT INTO currency_settings (currency_code, currency_symbol, currency_name, exchange_rate, is_base_currency, is_active, decimal_places)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($defaults as $currency) {
                $stmt->execute($currency);
            }
        } catch (PDOException $e) {
            error_log("Error creating default currencies: " . $e->getMessage());
        }
    }
    
    private function setUserCurrency() {
        // Priority 1: POST/GET request
        if (isset($_POST['currency'])) {
            $this->userCurrency = $_POST['currency'];
            $_SESSION['currency'] = $this->userCurrency;
            setcookie('user_currency', $this->userCurrency, time() + (86400 * 30), "/");
            return;
        }
        
        // Priority 2: Logged in user preference
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $this->conn->prepare("SELECT preferred_currency FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                if ($user && !empty($user['preferred_currency']) && $this->isValidCurrency($user['preferred_currency'])) {
                    $this->userCurrency = $user['preferred_currency'];
                    $_SESSION['currency'] = $this->userCurrency;
                    return;
                }
            } catch (PDOException $e) {
                error_log("Error getting user currency: " . $e->getMessage());
            }
        }
        
        // Priority 3: Session
        if (isset($_SESSION['currency']) && $this->isValidCurrency($_SESSION['currency'])) {
            $this->userCurrency = $_SESSION['currency'];
            return;
        }
        
        // Priority 4: Cookie
        if (isset($_COOKIE['user_currency']) && $this->isValidCurrency($_COOKIE['user_currency'])) {
            $this->userCurrency = $_COOKIE['user_currency'];
            $_SESSION['currency'] = $this->userCurrency;
            return;
        }
        
        // Priority 5: Default from settings
        $this->userCurrency = $this->getDefaultCurrency();
        $_SESSION['currency'] = $this->userCurrency;
    }
    
    public function convert($amount, $fromCurrency = null, $toCurrency = null) {
        // Handle null or non-numeric amount
        if ($amount === null || !is_numeric($amount)) {
            $amount = 0;
        }
        
        $amount = (float)$amount;
        
        $from = $fromCurrency ?: ($this->baseCurrency ? $this->baseCurrency['currency_code'] : 'USD');
        $to = $toCurrency ?: $this->userCurrency;
        
        if ($from === $to) {
            return $amount;
        }
        
        $fromRate = $this->getExchangeRate($from);
        $toRate = $this->getExchangeRate($to);
        
        if (!$fromRate || !$toRate) {
            return $amount;
        }
        
        // Convert to base currency first, then to target currency
        $amountInBase = $amount / $fromRate;
        $convertedAmount = $amountInBase * $toRate;
        
        return round($convertedAmount, $this->getDecimalPlaces($to));
    }
    
    public function formatPrice($amount, $currencyCode = null) {
        // Handle null or non-numeric amount
        if ($amount === null || !is_numeric($amount)) {
            $amount = 0;
        }
        
        $amount = (float)$amount;
        $code = $currencyCode ?: $this->userCurrency;
        $currency = $this->getCurrencyInfo($code);
        
        if (!$currency) {
            return '$' . number_format($amount, 2);
        }
        
        $formattedAmount = number_format($amount, $currency['decimal_places']);
        
        // Format based on currency (symbol placement)
        if (in_array($code, ['EUR', 'GBP', 'AUD', 'CAD'])) {
            return $currency['currency_symbol'] . $formattedAmount;
        } elseif ($code === 'JPY') {
            return $currency['currency_symbol'] . $formattedAmount;
        } elseif ($code === 'INR') {
            return $currency['currency_symbol'] . ' ' . $formattedAmount;
        } else {
            return $currency['currency_symbol'] . $formattedAmount;
        }
    }
    
    public function getExchangeRate($currencyCode) {
        foreach ($this->currencies as $currency) {
            if ($currency['currency_code'] === $currencyCode) {
                return (float)$currency['exchange_rate'];
            }
        }
        return 1.0;
    }
    
    public function getCurrencyInfo($currencyCode) {
        foreach ($this->currencies as $currency) {
            if ($currency['currency_code'] === $currencyCode) {
                return $currency;
            }
        }
        return null;
    }
    
    public function getAllCurrencies() {
        return $this->currencies;
    }
    
    public function getCurrentCurrency() {
        $currency = $this->getCurrencyInfo($this->userCurrency);
        if (!$currency && !empty($this->currencies)) {
            return $this->currencies[0];
        }
        if (!$currency) {
            return ['currency_code' => 'USD', 'currency_symbol' => '$', 'currency_name' => 'US Dollar', 'exchange_rate' => 1.0, 'decimal_places' => 2];
        }
        return $currency;
    }
    
    public function getCurrentCurrencyCode() {
        return $this->userCurrency;
    }
    
    /*public function setUserCurrency($currencyCode) {
        if (!$this->isValidCurrency($currencyCode)) {
            return false;
        }
        
        $this->userCurrency = $currencyCode;
        $_SESSION['currency'] = $currencyCode;
        setcookie('user_currency', $currencyCode, time() + (86400 * 30), "/");
        
        // Update user preference if logged in
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $this->conn->prepare("UPDATE users SET preferred_currency = ? WHERE id = ?");
                $stmt->execute([$currencyCode, $_SESSION['user_id']]);
            } catch (PDOException $e) {
                error_log("Error updating user currency: " . $e->getMessage());
            }
        }
        
        return true;
    }*/
    
    public function isValidCurrency($currencyCode) {
        foreach ($this->currencies as $currency) {
            if ($currency['currency_code'] === $currencyCode) {
                return true;
            }
        }
        return false;
    }
    
    public function getDefaultCurrency() {
        try {
            $stmt = $this->conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'default_currency'");
            $stmt->execute();
            $default = $stmt->fetch();
            return ($default && !empty($default['setting_value'])) ? $default['setting_value'] : 'USD';
        } catch (PDOException $e) {
            return 'USD';
        }
    }
    
    public function getBaseCurrency() {
        return $this->baseCurrency ? $this->baseCurrency['currency_code'] : 'USD';
    }
    
    public function updateExchangeRate($currencyCode, $newRate) {
        try {
            $stmt = $this->conn->prepare("UPDATE currency_settings SET exchange_rate = ? WHERE currency_code = ?");
            $result = $stmt->execute([$newRate, $currencyCode]);
            
            if ($result) {
                $this->loadCurrencies(); // Reload currencies
            }
            
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function addCurrency($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO currency_settings (currency_code, currency_symbol, currency_name, exchange_rate, is_base_currency, is_active, decimal_places)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                strtoupper($data['currency_code']),
                $data['currency_symbol'],
                $data['currency_name'],
                $data['exchange_rate'],
                isset($data['is_base_currency']) ? $data['is_base_currency'] : false,
                isset($data['is_active']) ? $data['is_active'] : true,
                isset($data['decimal_places']) ? $data['decimal_places'] : 2
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function getDecimalPlaces($currencyCode) {
        $currency = $this->getCurrencyInfo($currencyCode);
        return $currency ? (int)$currency['decimal_places'] : 2;
    }
}
?>