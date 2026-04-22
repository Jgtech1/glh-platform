<?php
require_once 'Database.php';
require_once 'CurrencyManager.php';

class Cart {
    private $db;
    private $conn;
    private $currencyManager;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->currencyManager = new CurrencyManager();
    }
    
    public function addToCart($userId, $productId, $quantity = 1) {
        try {
            // Check stock availability - use 'price' column, not 'base_price'
            $stmt = $this->conn->prepare("SELECT id, price, stock_quantity FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                error_log("Product not found: $productId");
                return false;
            }
            
            if ($product['stock_quantity'] < $quantity) {
                error_log("Insufficient stock for product $productId. Available: {$product['stock_quantity']}, Requested: $quantity");
                return false;
            }
            
            // Check if item already in cart
            $stmt = $this->conn->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $existingItem = $stmt->fetch();
            
            if ($existingItem) {
                // Update quantity - check if new quantity exceeds stock
                $newQuantity = $existingItem['quantity'] + $quantity;
                if ($newQuantity > $product['stock_quantity']) {
                    error_log("New quantity would exceed stock");
                    return false;
                }
                
                // Update quantity
                $stmt = $this->conn->prepare("
                    UPDATE cart_items 
                    SET quantity = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$newQuantity, $existingItem['id']]);
                error_log("Updated cart item. Result: " . ($result ? "Success" : "Failed"));
                return $result;
            } else {
                // Add new item
                $stmt = $this->conn->prepare("
                    INSERT INTO cart_items (user_id, product_id, quantity, added_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                $result = $stmt->execute([$userId, $productId, $quantity]);
                error_log("Added new cart item. Result: " . ($result ? "Success" : "Failed"));
                return $result;
            }
        } catch (PDOException $e) {
            error_log("Error in addToCart: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCartItems($userId) {
        try {
            // Check what price column exists in products table
            $priceColumn = $this->getPriceColumn();
            
            $stmt = $this->conn->prepare("
                SELECT 
                    ci.id as cart_id,
                    ci.user_id,
                    ci.product_id,
                    ci.quantity,
                    ci.added_at,
                    p.id as product_id,
                    p.name,
                    p.$priceColumn as price,
                    p.image_url,
                    p.stock_quantity,
                    p.unit,
                    p.status
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.user_id = ? AND p.status = 'active'
                ORDER BY ci.added_at DESC
            ");
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll();
            
            error_log("Found " . count($items) . " items in cart for user $userId");
            
            foreach ($items as &$item) {
                // Convert price to display currency
                $item['display_price'] = $this->currencyManager->convert($item['price']);
                $item['formatted_price'] = $this->currencyManager->formatPrice($item['display_price']);
                $item['subtotal'] = $item['display_price'] * $item['quantity'];
                $item['formatted_subtotal'] = $this->currencyManager->formatPrice($item['subtotal']);
            }
            
            return $items;
        } catch (PDOException $e) {
            error_log("Error in getCartItems: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Determine which price column exists in products table
     */
    private function getPriceColumn() {
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM products");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('price', $columns)) {
                return 'price';
            } elseif (in_array('base_price', $columns)) {
                return 'base_price';
            } else {
                return 'price'; // default
            }
        } catch (PDOException $e) {
            return 'price';
        }
    }
    
    public function getCartTotal($userId) {
        $items = $this->getCartItems($userId);
        $total = 0;
        foreach ($items as $item) {
            $total += $item['subtotal'];
        }
        return $total;
    }
    
    public function getFormattedCartTotal($userId) {
        $total = $this->getCartTotal($userId);
        return $this->currencyManager->formatPrice($total);
    }
    
    public function updateCartItem($cartItemId, $quantity) {
        try {
            // First get the product to check stock
            $stmt = $this->conn->prepare("
                SELECT ci.product_id, p.stock_quantity 
                FROM cart_items ci 
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.id = ?
            ");
            $stmt->execute([$cartItemId]);
            $item = $stmt->fetch();
            
            if (!$item) {
                return false;
            }
            
            if ($quantity > $item['stock_quantity']) {
                return false;
            }
            
            if ($quantity <= 0) {
                return $this->removeFromCart($cartItemId);
            }
            
            $stmt = $this->conn->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$quantity, $cartItemId]);
        } catch (PDOException $e) {
            error_log("Error in updateCartItem: " . $e->getMessage());
            return false;
        }
    }
    
    public function removeFromCart($cartItemId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart_items WHERE id = ?");
            return $stmt->execute([$cartItemId]);
        } catch (PDOException $e) {
            error_log("Error in removeFromCart: " . $e->getMessage());
            return false;
        }
    }
    
    public function clearCart($userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error in clearCart: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCartCount($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT SUM(quantity) as total 
                FROM cart_items 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return $result && $result['total'] ? (int)$result['total'] : 0;
        } catch (PDOException $e) {
            error_log("Error in getCartCount: " . $e->getMessage());
            return 0;
        }
    }
}
?>