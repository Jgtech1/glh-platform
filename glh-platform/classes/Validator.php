<?php
class Validator {
    
    /**
     * Sanitize input data
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        return strlen($password) >= 6;
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone) {
        // Allow numbers, spaces, dashes, plus, parentheses
        return preg_match('/^[\+\-\s\(\)0-9]{10,15}$/', $phone);
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired($data, $fields) {
        $errors = [];
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }
        return $errors;
    }
    
    /**
     * Validate numeric value
     */
    public static function validateNumeric($value, $min = null, $max = null) {
        if (!is_numeric($value)) {
            return false;
        }
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate string length
     */
    public static function validateLength($string, $min = 0, $max = 255) {
        $length = strlen(trim($string));
        return $length >= $min && $length <= $max;
    }
    
    /**
     * Validate username (alphanumeric and underscore only)
     */
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username);
    }
    
    /**
     * Validate URL
     */
    public static function validateUrl($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate integer
     */
    public static function validateInteger($value, $min = null, $max = null) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate date format (Y-m-d)
     */
    public static function validateDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate that a value is in allowed list
     */
    public static function validateInArray($value, $allowedValues) {
        return in_array($value, $allowedValues);
    }
    
    /**
     * XSS Prevention - Clean HTML
     */
    public static function cleanXSS($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
?>