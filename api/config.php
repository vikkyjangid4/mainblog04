<?php
// Database configuration
// class DatabaseConfig {
//     private $host = 'boganto-aurora.cluster-cdk0ug8eg3rn.ap-south-1.rds.amazonaws.com';
//     private $db_name = 'blog';
//     private $username = 'blog';
//     private $password = '7P7cYLTc4lE8prXL';
//     public $conn;

class DatabaseConfig {
    private $host = 'localhost';
    private $db_name = 'boganto_blog';
    private $username = 'root';
    private $password = 'Vj2004@jangid';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// CORS headers for React frontend
$allowed_origins = [
    'http://localhost:5173', 
    'http://localhost:3000',
    'https://boganto.com',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? 'http://localhost:3000';

$allowed_origin = in_array($origin, $allowed_origins)
    ? $origin
    : 'http://localhost:3000';   // fallback production origin


header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Helper function to send JSON response
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

// Helper function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Helper function to generate slug
function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
}  

// Helper function to upload file - ALWAYS returns relative path only
function uploadFile($file, $subfolder = '') {
    $upload_dir = '../uploads/';
    
    // Create subfolder if specified
    if ($subfolder) {
        $upload_dir .= $subfolder . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
    }
    
    // Check for upload errors
    if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        error_log('Upload error: ' . $file['error']);
        return false;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        error_log('Invalid file type: ' . $file['type']);
        return false;
    }
    
    // Validate file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        error_log('File too large: ' . $file['size']);
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // IMPORTANT: Return relative path ONLY (no domain) for database storage
        // Format: uploads/subfolder/filename.ext (without leading slash)
        return 'uploads/' . ($subfolder ? $subfolder . '/' : '') . $filename;
    }
    
    error_log('Failed to move uploaded file to: ' . $file_path);
    return false;
}

// Helper function to get relative image paths from database
// Returns ONLY the relative path (uploads/subfolder/filename.ext)
// Frontend will handle URL construction based on environment
function getFullImageUrl($imagePath) {
    if (!$imagePath) {
        return null;
    }
    
    // If it's already a full URL, extract just the relative path
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        // Extract the path after domain: https://boganto.com/uploads/file.jpg -> uploads/file.jpg
        $imagePath = preg_replace('/^https?:\/\/[^\/]+\//', '', $imagePath);
    }
    
    // Remove leading slash if present
    $imagePath = ltrim($imagePath, '/');
    
    // Return relative path only (no domain)
    // Frontend will prepend BASE_URL as needed
    return $imagePath ?: null;
}

// Helper function to clean image path - removes domain prefix if present
// Always returns relative path: uploads/subfolder/filename.ext
function cleanImagePath($imagePath) {
    if (!$imagePath || empty($imagePath)) {
        return null;
    }
    
    // Remove any domain prefix (http://..., https://...)
    $imagePath = preg_replace('/^https?:\/\/[^\/]+\//', '', $imagePath);
    
    // Remove leading slash if present
    $imagePath = ltrim($imagePath, '/');
    
    // Ensure it starts with 'uploads/'
    if (strpos($imagePath, 'uploads/') !== 0) {
        // If it doesn't start with uploads/, but it's in uploads somewhere, fix it
        if (strpos($imagePath, 'uploads/') !== false) {
            $imagePath = substr($imagePath, strpos($imagePath, 'uploads/'));
        }
    }
    
    return $imagePath ?: null;
}
?>