<?php
require_once 'Database.php';
require_once 'CurrencyManager.php';
require_once 'Product.php';
require_once 'Loyalty.php';

class Order {
    private $db;
    private $conn;
    private $currencyManager;
    private $loyalty;

    // How many points a customer earns per unit of currency spent (1 point per $1)
    const POINTS_PER_CURRENCY_UNIT = 1;

    // How many points equal one unit of currency (100 points = $1.00 discount)
    const POINTS_REDEMPTION_RATE = 100;

    public function __construct() {
        $this->db              = Database::getInstance();
        $this->conn            = $this->db->getConnection();
        $this->currencyManager = new CurrencyManager();
        $this->loyalty         = new Loyalty();
    }

    // =========================================================================
    // Loyalty helpers
    // =========================================================================

    /**
     * Calculate how many loyalty points a customer earns for a given spend amount.
     *
     * @param  float $amount  Order total.
     * @return int
     */
    public function calculateEarnedPoints($amount) {
        return (int) floor((float) $amount * self::POINTS_PER_CURRENCY_UNIT);
    }

    /**
     * Calculate the monetary discount value of a number of loyalty points.
     *
     * @param  int   $points
     * @return float
     */
    public function calculatePointsDiscount($points) {
        return round((int) $points / self::POINTS_REDEMPTION_RATE, 2);
    }

    /**
     * Return the maximum number of points a user may redeem for a given order.
     * Redemption is capped at 50 % of the order total to prevent zero-value orders.
     *
     * @param  int   $availablePoints
     * @param  float $orderTotal
     * @return int
     */
    public function getMaxRedeemablePoints($availablePoints, $orderTotal) {
        $maxDiscount   = $orderTotal * 0.50;
        $maxByDiscount = (int) floor($maxDiscount * self::POINTS_REDEMPTION_RATE);
        return min((int) $availablePoints, $maxByDiscount);
    }

    /**
     * Return the current loyalty point balance for a user.
     */
    public function getUserLoyaltyPoints($userId) {
        return $this->loyalty->getUserPoints($userId);
    }

    /**
     * Return the loyalty transaction history for a user.
     */
    public function getLoyaltyHistory($userId) {
        return $this->loyalty->getTransactionHistory($userId);
    }

    // =========================================================================
    // createOrder
    // =========================================================================

    /**
     * Create a new order, deduct/award loyalty points, and update stock.
     *
     * @param  array      $orderData
     * @param  array      $cartItems
     * @return int|false  New order ID on success, false on failure.
     */
    public function createOrder($orderData, $cartItems) {
        $transactionStarted = false;

        try {
            error_log("=== Starting createOrder for user: " . ($orderData['user_id'] ?? 'unknown') . " ===");

            // ------------------------------------------------------------------
            // Validate required fields before touching the DB
            // ------------------------------------------------------------------
            $requiredFields = [
                'user_id', 'shipping_address', 'shipping_city',
                'shipping_zip', 'shipping_country', 'total_amount',
            ];
            foreach ($requiredFields as $field) {
                if (!isset($orderData[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }

            // ------------------------------------------------------------------
            // Clean up any stale open transaction left by a previous failure
            // ------------------------------------------------------------------
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
                error_log("WARNING: Rolled back a stale transaction before createOrder.");
            }

            // ------------------------------------------------------------------
            // 1. Resolve loyalty points to redeem BEFORE the transaction so
            //    the Loyalty read query cannot interfere with transaction state.
            // ------------------------------------------------------------------
            $loyaltyPointsUsed = isset($orderData['loyalty_points_used'])
                ? max(0, (int) $orderData['loyalty_points_used'])
                : 0;

            if ($loyaltyPointsUsed > 0) {
                $availablePoints = $this->loyalty->getUserPoints($orderData['user_id']);
                if ($loyaltyPointsUsed > $availablePoints) {
                    $loyaltyPointsUsed = $availablePoints;
                    error_log("Loyalty points capped to available balance: {$loyaltyPointsUsed}");
                }
            }

            // ------------------------------------------------------------------
            // 2. Currency information
            // ------------------------------------------------------------------
            $currentCurrency    = $this->currencyManager->getCurrentCurrency();
            $currencyCode       = $currentCurrency['currency_code'] ?? 'USD';
            $exchangeRate       = $currentCurrency['exchange_rate']  ?? 1.00;
            $totalAmountBase    = $orderData['total_amount'] / $exchangeRate;
            $totalAmountDisplay = $orderData['total_amount'];

            // ------------------------------------------------------------------
            // 3. Build order meta fields
            // ------------------------------------------------------------------
            $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

            $deliveryAddress = implode(', ', array_filter([
                $orderData['shipping_address'] ?? '',
                $orderData['shipping_city']    ?? '',
                $orderData['shipping_zip']     ?? '',
                $orderData['shipping_country'] ?? '',
            ]));

            $paymentStatus = $orderData['payment_status'] ?? 'pending';
            $orderNotes    = $orderData['order_notes']    ?? null;
            $earnedPoints  = $this->calculateEarnedPoints($totalAmountBase);

            // ------------------------------------------------------------------
            // START TRANSACTION
            // Only the order row, order items, and stock updates live inside
            // the transaction. Loyalty calls are deliberately kept OUTSIDE to
            // prevent the Loyalty class from implicitly closing this transaction.
            // ------------------------------------------------------------------
            $this->conn->beginTransaction();
            $transactionStarted = true;
            error_log("Transaction started successfully");

            // ------------------------------------------------------------------
            // 4. Insert the order row
            // ------------------------------------------------------------------
            $sql = "INSERT INTO orders (
                        order_number,
                        user_id,
                        total_amount_base,
                        total_amount_display,
                        currency_code,
                        exchange_rate_used,
                        delivery_type,
                        delivery_address,
                        status,
                        payment_status,
                        order_notes,
                        loyalty_points_used,
                        loyalty_points_earned,
                        created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                    )";

            $values = [
                $orderNumber,
                $orderData['user_id'],
                $totalAmountBase,
                $totalAmountDisplay,
                $currencyCode,
                $exchangeRate,
                'delivery',
                $deliveryAddress,
                'pending',
                $paymentStatus,
                $orderNotes,
                $loyaltyPointsUsed,
                $earnedPoints,
            ];

            error_log("Order insert values: " . print_r($values, true));

            $stmt     = $this->conn->prepare($sql);
            $inserted = $stmt->execute($values);

            if (!$inserted) {
                $info = $stmt->errorInfo();
                throw new Exception("Failed to insert order. DB error: " . implode(' | ', $info));
            }

            $orderId = $this->conn->lastInsertId();
            error_log("Order row inserted successfully. ID: {$orderId}");

            // ------------------------------------------------------------------
            // 5. Insert order items and deduct stock
            // ------------------------------------------------------------------
            $productObj = new Product();

            foreach ($cartItems as $index => $item) {
                $itemPrice = (float) ($item['price'] ?? $item['base_price'] ?? $item['display_price'] ?? 0);

                if (empty($item['product_id'])) {
                    error_log("Missing product_id in cart item {$index}: " . print_r($item, true));
                    throw new Exception("Cart item missing product_id");
                }

                $stmt = $this->conn->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price_at_time)
                    VALUES (?, ?, ?, ?)
                ");

                $result = $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $itemPrice,
                ]);

                if (!$result) {
                    $info = $stmt->errorInfo();
                    throw new Exception("Failed to insert order item. DB error: " . implode(' | ', $info));
                }

                error_log("Order item inserted: product_id={$item['product_id']}, quantity={$item['quantity']}, price={$itemPrice}");

                // Deduct stock
                if (!$productObj->updateStock($item['product_id'], $item['quantity'])) {
                    throw new Exception("Failed to update stock for product ID: {$item['product_id']}");
                }
            }

            // ------------------------------------------------------------------
            // 6. COMMIT — order and stock are now safely persisted.
            //    Loyalty operations happen AFTER this commit.
            // ------------------------------------------------------------------
            $this->conn->commit();
            $transactionStarted = false;
            error_log("Core order committed successfully. ID: {$orderId}");

            // ------------------------------------------------------------------
            // 7. Loyalty: deduct redeemed points  (outside transaction)
            // ------------------------------------------------------------------
            if ($loyaltyPointsUsed > 0) {
                $redeemed = $this->loyalty->usePoints(
                    $orderData['user_id'],
                    $loyaltyPointsUsed,
                    $orderId
                );
                if (!$redeemed) {
                    error_log("WARNING: Failed to deduct {$loyaltyPointsUsed} loyalty points for order {$orderId}");
                } else {
                    error_log("Successfully deducted {$loyaltyPointsUsed} loyalty points");
                }
            }

            // ------------------------------------------------------------------
            // 8. Loyalty: award earned points  (outside transaction)
            // ------------------------------------------------------------------
            if ($earnedPoints > 0) {
                $awarded = $this->loyalty->addPoints(
                    $orderData['user_id'],
                    $earnedPoints,
                    $orderId,
                    "Points earned for order #{$orderNumber}"
                );
                if (!$awarded) {
                    error_log("WARNING: Failed to award {$earnedPoints} loyalty points for order {$orderId}");
                } else {
                    error_log("Successfully awarded {$earnedPoints} loyalty points");
                }
            }

            error_log("=== Order created successfully! ID: {$orderId} ===");
            return $orderId;

        } catch (PDOException $e) {
            if ($transactionStarted && $this->conn->inTransaction()) {
                $this->conn->rollBack();
                error_log("Transaction rolled back due to PDO Exception");
            }
            error_log("PDO Exception in createOrder: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;

        } catch (Exception $e) {
            if ($transactionStarted && $this->conn->inTransaction()) {
                $this->conn->rollBack();
                error_log("Transaction rolled back due to Exception");
            }
            error_log("Exception in createOrder: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    // =========================================================================
    // Read methods
    // =========================================================================

    /**
     * Get paginated orders for a user.
     */
    public function getOrdersByUser($userId, $limit = 10, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM orders
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->bindParam(2, $limit,  PDO::PARAM_INT);
            $stmt->bindParam(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getOrdersByUser: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count all orders for a user.
     */
    public function getOrderCountByUser($userId) {
        try {
            $stmt = $this->conn->prepare(
                "SELECT COUNT(*) AS count FROM orders WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['count'] : 0;
        } catch (PDOException $e) {
            error_log("Error in getOrderCountByUser: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all orders for a user (no pagination).
     */
    public function getUserOrders($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM orders
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getUserOrders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all orders that include products from a specific producer.
     */
    public function getProducerOrders($producerId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT o.*,
                       u.full_name AS customer_name,
                       u.phone     AS customer_phone
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN products p ON oi.product_id = p.id
                JOIN users u ON o.user_id = u.id
                WHERE p.producer_id = ?
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$producerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getProducerOrders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all orders (admin view).
     */
    public function getAllOrders() {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.*, u.full_name AS customer_name
                FROM orders o
                JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllOrders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single order by ID (includes full customer info).
     */
    public function getOrderById($orderId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT o.*,
                       u.full_name AS customer_name,
                       u.email     AS customer_email,
                       u.phone     AS customer_phone
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If order found, also get additional shipping details if they exist in a separate table
            if ($order) {
                // Try to get shipping details from order_shipping table if it exists
                try {
                    $stmt2 = $this->conn->prepare("
                        SELECT * FROM order_shipping WHERE order_id = ?
                    ");
                    $stmt2->execute([$orderId]);
                    $shipping = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($shipping) {
                        $order = array_merge($order, $shipping);
                    }
                } catch (PDOException $e) {
                    // Table might not exist, that's fine
                }
            }
            
            return $order;
        } catch (PDOException $e) {
            error_log("Error in getOrderById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all line items for an order, including product details.
     */
    public function getOrderItems($orderId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT oi.*,
                       oi.price_at_time AS price,
                       oi.price_at_time AS unit_price_display,
                       p.name,
                       p.image_url,
                       p.unit,
                       u.full_name AS producer_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN users u ON p.producer_id = u.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getOrderItems: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get order timeline/status history for tracking page.
     * This method returns all status changes for an order.
     *
     * @param int $orderId
     * @return array Array of status change events
     */
    public function getOrderTimeline($orderId) {
        try {
            // First, check if there's an order_status_history table
            $stmt = $this->conn->prepare("
                SHOW TABLES LIKE 'order_status_history'
            ");
            $stmt->execute();
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                // Use the status history table if it exists
                $stmt = $this->conn->prepare("
                    SELECT * FROM order_status_history
                    WHERE order_id = ?
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$orderId]);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($history)) {
                    return $history;
                }
            }
            
            // If no history table or no records, create timeline from order data
            $timeline = [];
            $order = $this->getOrderById($orderId);
            
            if (!$order) {
                return [];
            }
            
            // Add order placed event
            $timeline[] = [
                'status' => 'pending',
                'created_at' => $order['created_at'],
                'notes' => 'Order placed successfully'
            ];
            
            // Add other statuses based on current status
            $statuses = ['confirmed', 'processing', 'shipped', 'delivered'];
            $statusMap = [
                'confirmed' => 'Order confirmed by seller',
                'processing' => 'Order is being prepared',
                'shipped' => 'Order has been shipped',
                'delivered' => 'Order delivered successfully'
            ];
            
            $currentStatus = $order['status'];
            $found = false;
            
            foreach ($statuses as $status) {
                if ($found || $status == $currentStatus) {
                    $found = true;
                    // If we have timestamps for these statuses in the order table
                    $dateField = $status . '_at';
                    $dateValue = isset($order[$dateField]) && !empty($order[$dateField]) 
                        ? $order[$dateField] 
                        : date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +' . (array_search($status, $statuses) + 1) . ' days'));
                    
                    $timeline[] = [
                        'status' => $status,
                        'created_at' => $dateValue,
                        'notes' => $statusMap[$status]
                    ];
                    
                    if ($status == $currentStatus) {
                        break;
                    }
                }
            }
            
            return $timeline;
            
        } catch (PDOException $e) {
            error_log("Error in getOrderTimeline: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get order status history from the status history table (if it exists).
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderStatusHistory($orderId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM order_status_history
                WHERE order_id = ?
                ORDER BY created_at ASC
            ");
            $stmt->execute([$orderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getOrderStatusHistory: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a status change to the order history.
     *
     * @param int $orderId
     * @param string $status
     * @param string|null $notes
     * @return bool
     */
    public function addOrderStatusHistory($orderId, $status, $notes = null) {
        try {
            // Check if table exists
            $stmt = $this->conn->prepare("
                SHOW TABLES LIKE 'order_status_history'
            ");
            $stmt->execute();
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                // Create the table if it doesn't exist
                $this->createOrderStatusHistoryTable();
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO order_status_history (order_id, status, notes, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            return $stmt->execute([$orderId, $status, $notes]);
        } catch (PDOException $e) {
            error_log("Error in addOrderStatusHistory: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create the order_status_history table if it doesn't exist.
     */
    private function createOrderStatusHistoryTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS order_status_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                status VARCHAR(50) NOT NULL,
                notes TEXT,
                created_at DATETIME NOT NULL,
                INDEX idx_order_id (order_id),
                INDEX idx_status (status),
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->conn->exec($sql);
            error_log("Created order_status_history table");
        } catch (PDOException $e) {
            error_log("Error creating order_status_history table: " . $e->getMessage());
        }
    }

    // =========================================================================
    // Update methods
    // =========================================================================

    /**
     * Update an order's status.
     */
    public function updateOrderStatus($orderId, $status) {
        try {
            // Check if updated_at column exists
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM orders LIKE 'updated_at'");
            $stmt->execute();
            $hasUpdatedAt = $stmt->rowCount() > 0;

            // Update the order status
            if ($hasUpdatedAt) {
                $stmt = $this->conn->prepare("
                    UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?
                ");
            } else {
                $stmt = $this->conn->prepare("
                    UPDATE orders SET status = ? WHERE id = ?
                ");
            }
            
            $result = $stmt->execute([$status, $orderId]);
            
            // Add to status history
            if ($result) {
                $this->addOrderStatusHistory($orderId, $status, "Status updated to " . ucfirst($status));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error in updateOrderStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update scheduled delivery date/time for an order.
     */
    public function updateScheduledDelivery($orderId, $scheduledDate, $scheduledTime = null) {
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM orders LIKE 'updated_at'");
            $stmt->execute();
            $hasUpdatedAt = $stmt->rowCount() > 0;

            $sql    = "UPDATE orders SET scheduled_date = ?";
            $params = [$scheduledDate];

            if ($scheduledTime !== null) {
                $sql     .= ", scheduled_time = ?";
                $params[] = $scheduledTime;
            }

            if ($hasUpdatedAt) {
                $sql .= ", updated_at = NOW()";
            }

            $sql     .= " WHERE id = ?";
            $params[] = $orderId;

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in updateScheduledDelivery: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a pending order.
     *
     * @param  int      $orderId
     * @param  int|null $userId  If supplied, ownership is verified before cancelling.
     * @return array    ['success' => bool, 'message' => string]
     */
    public function cancelOrder($orderId, $userId = null) {
        $transactionStarted = false;

        try {
            // Clean up any stale open transaction
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
                error_log("WARNING: Rolled back a stale transaction before cancelOrder.");
            }

            $order = $this->getOrderById($orderId);
            if (!$order) {
                throw new Exception('Order not found');
            }

            if ($userId && (int) $order['user_id'] !== (int) $userId) {
                throw new Exception('Unauthorized');
            }

            if ($order['status'] !== 'pending') {
                throw new Exception('Only pending orders can be cancelled');
            }

            // ------------------------------------------------------------------
            // Transaction: mark cancelled + restore stock only.
            // Loyalty calls happen AFTER commit for the same reason as
            // createOrder — to prevent implicit transaction closure.
            // ------------------------------------------------------------------
            $this->conn->beginTransaction();
            $transactionStarted = true;

            // 1. Mark cancelled
            $this->updateOrderStatus($orderId, 'cancelled');

            // 2. Restore stock
            $orderItems = $this->getOrderItems($orderId);
            foreach ($orderItems as $item) {
                $stmt = $this->conn->prepare("
                    UPDATE products
                    SET stock_quantity = stock_quantity + ?
                    WHERE id = ?
                ");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Commit status + stock before any Loyalty calls
            $this->conn->commit();
            $transactionStarted = false;
            error_log("Order {$orderId} cancelled and stock restored.");

            // ------------------------------------------------------------------
            // Loyalty: refund redeemed points  (outside transaction)
            // ------------------------------------------------------------------
            $pointsUsed = (int) ($order['loyalty_points_used'] ?? 0);
            if ($pointsUsed > 0) {
                $refunded = $this->loyalty->addPoints(
                    $order['user_id'],
                    $pointsUsed,
                    $orderId,
                    "Loyalty points refunded for cancelled order #{$order['order_number']}"
                );
                if (!$refunded) {
                    error_log("WARNING: Failed to refund {$pointsUsed} loyalty points for order {$orderId}");
                }
            }

            // ------------------------------------------------------------------
            // Loyalty: revoke earned points  (outside transaction)
            // ------------------------------------------------------------------
            $earnedPoints = isset($order['loyalty_points_earned'])
                ? (int) $order['loyalty_points_earned']
                : $this->calculateEarnedPoints(
                    $order['total_amount_base'] ?? $order['total_amount_display'] ?? 0
                );

            if ($earnedPoints > 0) {
                $currentBalance = $this->loyalty->getUserPoints($order['user_id']);
                $pointsToRevoke = min($earnedPoints, $currentBalance);

                if ($pointsToRevoke > 0) {
                    $revoked = $this->loyalty->usePoints(
                        $order['user_id'],
                        $pointsToRevoke,
                        $orderId
                    );
                    if (!$revoked) {
                        error_log("WARNING: Failed to revoke {$pointsToRevoke} earned loyalty points for order {$orderId}");
                    }
                }
            }

            return ['success' => true, 'message' => 'Order cancelled successfully'];

        } catch (Exception $e) {
            if ($transactionStarted && $this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error in cancelOrder: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>