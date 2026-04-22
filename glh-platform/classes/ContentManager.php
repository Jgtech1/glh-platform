<?php
require_once 'Database.php';

class ContentManager {
    private $db;
    private $conn;
    private $cache = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
        $this->loadAllContent();
    }
    
    private function loadAllContent() {
        try {
            $stmt = $this->conn->prepare("SELECT content_key, content_value, content_type FROM dynamic_content WHERE is_active = TRUE");
            $stmt->execute();
            $contents = $stmt->fetchAll();
            
            foreach ($contents as $content) {
                $this->cache[$content['content_key']] = $content['content_value'];
            }
        } catch (PDOException $e) {
            error_log("Error loading content: " . $e->getMessage());
        }
    }
    
    public function get($key, $default = '') {
        return isset($this->cache[$key]) ? $this->cache[$key] : $default;
    }
    
    public function set($key, $value, $type = 'text', $category = 'general') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO dynamic_content (content_key, content_value, content_type, category) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE content_value = ?, content_type = ?, category = ?, updated_at = NOW()
            ");
            $result = $stmt->execute([$key, $value, $type, $category, $value, $type, $category]);
            
            if ($result) {
                $this->cache[$key] = $value;
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error setting content: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllContent($category = null) {
        try {
            if ($category) {
                $stmt = $this->conn->prepare("SELECT * FROM dynamic_content WHERE category = ? ORDER BY content_key");
                $stmt->execute([$category]);
            } else {
                $stmt = $this->conn->prepare("SELECT * FROM dynamic_content ORDER BY category, content_key");
                $stmt->execute();
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function deleteContent($key) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM dynamic_content WHERE content_key = ?");
            $result = $stmt->execute([$key]);
            
            if ($result && isset($this->cache[$key])) {
                unset($this->cache[$key]);
            }
            
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function render($key, $default = '') {
        $content = $this->get($key, $default);
        return htmlspecialchars_decode($content);
    }
    
    public function getCategories() {
        try {
            $stmt = $this->conn->prepare("SELECT DISTINCT category FROM dynamic_content ORDER BY category");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>