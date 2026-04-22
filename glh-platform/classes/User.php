<?php
require_once 'Database.php';
require_once 'Validator.php';  // Add this line

class User {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    public function register($data) {
        try {
            // Validate data
            $errors = Validator::validateRequired($data, ['username', 'email', 'password', 'full_name']);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            // Validate username format
            if (!Validator::validateUsername($data['username'])) {
                return ['success' => false, 'errors' => ['Username must be 3-50 characters and can only contain letters, numbers, and underscore']];
            }
            
            // Validate email
            if (!Validator::validateEmail($data['email'])) {
                return ['success' => false, 'errors' => ['Invalid email format']];
            }
            
            // Validate password
            if (!Validator::validatePassword($data['password'])) {
                return ['success' => false, 'errors' => ['Password must be at least 6 characters']];
            }
            
            // Check if user exists
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'errors' => ['Username or email already exists']];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $this->conn->prepare("
                INSERT INTO users (username, email, password, full_name, phone, address, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $role = isset($data['role']) ? $data['role'] : 'customer';
            $phone = isset($data['phone']) ? Validator::sanitize($data['phone']) : null;
            $address = isset($data['address']) ? Validator::sanitize($data['address']) : null;
            
            $stmt->execute([
                Validator::sanitize($data['username']),
                Validator::sanitize($data['email']),
                $hashedPassword,
                Validator::sanitize($data['full_name']),
                $phone,
                $address,
                $role
            ]);
            
            return ['success' => true, 'message' => 'Registration successful'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                return ['success' => true, 'role' => $user['role']];
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login error'];
        }
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, full_name, phone, address, role, loyalty_points, preferred_currency FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function getAllUsers($role = null) {
        try {
            if ($role) {
                $stmt = $this->conn->prepare("SELECT id, username, email, full_name, phone, role, loyalty_points, created_at FROM users WHERE role = ? ORDER BY created_at DESC");
                $stmt->execute([$role]);
            } else {
                $stmt = $this->conn->prepare("SELECT id, username, email, full_name, phone, role, loyalty_points, created_at FROM users ORDER BY created_at DESC");
                $stmt->execute();
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function deleteUser($userId) {
        try {
            // Don't allow deleting admin users
            $stmt = $this->conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user && $user['role'] == 'admin') {
                return false;
            }
            
            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function updateLoyaltyPoints($userId, $points) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
            return $stmt->execute([$points, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function updatePreferredCurrency($userId, $currencyCode) {
        try {
            $stmt = $this->conn->prepare("UPDATE users SET preferred_currency = ? WHERE id = ?");
            return $stmt->execute([$currencyCode, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>