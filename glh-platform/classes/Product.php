<?php
require_once 'Database.php';

class Product {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    public function addProduct($data, $imageFile = null) {
        try {
            $errors = Validator::validateRequired($data, ['name', 'price', 'stock_quantity']);
            if (!empty($errors)) {
                return ['success' => false, 'errors' => $errors];
            }
            
            $imageUrl = null;
            if ($imageFile && isset($imageFile['error']) && $imageFile['error'] === 0) {
                $imageUrl = $this->uploadImage($imageFile);
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO products (producer_id, name, description, price, stock_quantity, image_url, category, unit) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['producer_id'],
                $data['name'],
                $data['description'] ?? null,
                $data['price'],
                $data['stock_quantity'],
                $imageUrl,
                $data['category'] ?? null,
                $data['unit'] ?? null
            ]);
            
            return ['success' => true, 'message' => 'Product added successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
    
    public function updateProduct($productId, $data, $imageFile = null) {
        try {
            $product = $this->getProductById($productId);
            if (!$product) {
                return ['success' => false, 'errors' => ['Product not found']];
            }
            
            $imageUrl = $product['image_url'];
            if ($imageFile && isset($imageFile['error']) && $imageFile['error'] === 0) {
                $imageUrl = $this->uploadImage($imageFile);
            }
            
            $stmt = $this->conn->prepare("
                UPDATE products 
                SET name = ?, description = ?, price = ?, stock_quantity = ?, image_url = ?, category = ?, unit = ?, status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['price'],
                $data['stock_quantity'],
                $imageUrl,
                $data['category'] ?? null,
                $data['unit'] ?? null,
                $data['status'] ?? 'active',
                $productId
            ]);
            
            return ['success' => true, 'message' => 'Product updated successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]];
        }
    }
    
    public function deleteProduct($productId, $producerId = null) {
        try {
            if ($producerId) {
                $stmt = $this->conn->prepare("DELETE FROM products WHERE id = ? AND producer_id = ?");
                return $stmt->execute([$productId, $producerId]);
            } else {
                $stmt = $this->conn->prepare("DELETE FROM products WHERE id = ?");
                return $stmt->execute([$productId]);
            }
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getProductById($id) {
        try {
            // Try to get price column, fallback to base_price if needed
            $stmt = $this->conn->prepare("
                SELECT p.*, u.full_name as producer_name 
                FROM products p 
                JOIN users u ON p.producer_id = u.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            // Handle different column names
            if ($product) {
                if (!isset($product['price']) && isset($product['base_price'])) {
                    $product['price'] = $product['base_price'];
                }
                $product['formatted_price'] = '$' . number_format($product['price'] ?? 0, 2);
            }
            
            return $product;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function getAllProducts($status = 'active') {
        try {
            // First, check what columns exist
            $columns = $this->getTableColumns('products');
            $priceColumn = in_array('price', $columns) ? 'price' : (in_array('base_price', $columns) ? 'base_price' : 'price');
            
            $sql = "
                SELECT p.*, u.full_name as producer_name 
                FROM products p 
                JOIN users u ON p.producer_id = u.id 
                WHERE p.status = ? 
                ORDER BY p.created_at DESC
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$status]);
            $products = $stmt->fetchAll();
            
            // Normalize price field
            foreach ($products as &$product) {
                if (!isset($product['price']) && isset($product['base_price'])) {
                    $product['price'] = $product['base_price'];
                }
                $product['formatted_price'] = '$' . number_format($product['price'] ?? 0, 2);
            }
            
            return $products;
        } catch (PDOException $e) {
            error_log("Error in getAllProducts: " . $e->getMessage());
            return [];
        }
    }
    
    private function getTableColumns($table) {
        try {
            $stmt = $this->conn->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $columns;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getProductsByProducer($producerId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM products 
                WHERE producer_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$producerId]);
            $products = $stmt->fetchAll();
            
            foreach ($products as &$product) {
                if (!isset($product['price']) && isset($product['base_price'])) {
                    $product['price'] = $product['base_price'];
                }
                $product['formatted_price'] = '$' . number_format($product['price'] ?? 0, 2);
            }
            
            return $products;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function searchProducts($keyword) {
        try {
            $stmt = $this->conn->prepare("
                SELECT p.*, u.full_name as producer_name 
                FROM products p 
                JOIN users u ON p.producer_id = u.id 
                WHERE p.status = 'active' AND (p.name LIKE ? OR p.description LIKE ?)
                ORDER BY p.created_at DESC
            ");
            $searchTerm = "%{$keyword}%";
            $stmt->execute([$searchTerm, $searchTerm]);
            $products = $stmt->fetchAll();
            
            foreach ($products as &$product) {
                if (!isset($product['price']) && isset($product['base_price'])) {
                    $product['price'] = $product['base_price'];
                }
                $product['formatted_price'] = '$' . number_format($product['price'] ?? 0, 2);
            }
            
            return $products;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    private function uploadImage($file) {
        $targetDir = dirname(__DIR__) . "/public/assets/uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($file['name']);
        $targetFile = $targetDir . $fileName;
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }
        
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return 'assets/uploads/' . $fileName;
        }
        
        return null;
    }
    
    /*public function updateStock($productId, $quantity) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE id = ? AND stock_quantity >= ?
            ");
            return $stmt->execute([$quantity, $productId, $quantity]);
        } catch (PDOException $e) {
            return false;
        }
    }*/
    public function updateStock($productId, $quantity) {
    try {
        $stmt = $this->conn->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - ? 
            WHERE id = ? AND stock_quantity >= ?
        ");
        return $stmt->execute([$quantity, $productId, $quantity]);
    } catch (PDOException $e) {
        error_log("Error updating stock: " . $e->getMessage());
        return false;
    }
}
}
?>