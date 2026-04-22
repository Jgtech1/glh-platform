<?php
require_once 'Database.php';

class Loyalty {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    public function addPoints($userId, $points, $orderId = null, $description = null) {
        try {
            $this->conn->beginTransaction();
            
            // Update user points
            $stmt = $this->conn->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            $stmt->execute([$points, $userId]);
            
            // Record transaction
            $stmt = $this->conn->prepare("
                INSERT INTO loyalty_transactions (user_id, points, transaction_type, order_id, description)
                VALUES (?, ?, 'earned', ?, ?)
            ");
            $stmt->execute([$userId, $points, $orderId, $description]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    public function usePoints($userId, $points, $orderId = null) {
        try {
            // Check if user has enough points
            $stmt = $this->conn->prepare("SELECT loyalty_points FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user['loyalty_points'] < $points) {
                return false;
            }
            
            $this->conn->beginTransaction();
            
            // Update user points
            $stmt = $this->conn->prepare("UPDATE users SET loyalty_points = loyalty_points - ? WHERE id = ?");
            $stmt->execute([$points, $userId]);
            
            // Record transaction
            $stmt = $this->conn->prepare("
                INSERT INTO loyalty_transactions (user_id, points, transaction_type, order_id, description)
                VALUES (?, ?, 'used', ?, 'Points used for order discount')
            ");
            $stmt->execute([$userId, $points, $orderId]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    public function getUserPoints($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT loyalty_points FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            return $user ? $user['loyalty_points'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getTransactionHistory($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM loyalty_transactions 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>