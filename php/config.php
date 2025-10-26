<?php
// php/config.php

// Database configuration
define('DB_HOST', 'localhost'); // Your database host (usually 'localhost' for XAMPP)
define('DB_USER', 'root');     // Your database username (default for XAMPP)
define('DB_PASS', '');         // Your database password (default for XAMPP is empty)
define('DB_NAME', 'swiftshare'); // Your database name

// Base URL for the SwiftShare application
// Important: Adjust this if your project is not directly in the htdocs root, e.g., '/swiftshare/' if in htdocs/swiftshare
define('BASE_URL', '/swiftshare/');

// File storage paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // Absolute path to the uploads directory
define('EXPIRED_DIR', __DIR__ . '/../expired_files/'); // Absolute path to the expired_files directory

// Ensure directories exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}
if (!is_dir(EXPIRED_DIR)) {
    mkdir(EXPIRED_DIR, 0777, true);
}

// Database connection using PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Set default timezone for PHP functions to match IST (Indian Standard Time)
date_default_timezone_set('Asia/Kolkata');

// Max download count and expiration time
define('MAX_DOWNLOAD_COUNT', 3);
define('FILE_EXPIRATION_MINUTES', 5);

// Function to generate a unique 6-character alphanumeric code
function generateUniqueCode($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Function to send JSON response
function sendJsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit();
}