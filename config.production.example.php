<?php
/**
 * PRODUCTION Configuration Example
 * Copy this file to includes/config.php on your production server
 * Update the values below with your production settings
 */

// Database Configuration - UPDATE THESE FOR PRODUCTION
define('DB_HOST', 'localhost'); // Usually 'localhost' or ask your hosting provider
define('DB_USER', 'your_db_username'); // Your cPanel database username
define('DB_PASS', 'your_db_password'); // Your database password
define('DB_NAME', 'your_db_name'); // Your database name

// Application Settings - UPDATE FOR PRODUCTION
define('APP_NAME', 'Code Catalyst Labs');
define('APP_URL', 'https://codecatalystug.com/invoice'); // UPDATE THIS
define('ITEMS_PER_PAGE', 10);

// Company Information
define('COMPANY_NAME', 'Code Catalyst Labs');
define('COMPANY_EMAIL', 'info@codecatalystlabs.com');
define('COMPANY_PHONE', '+256 (783) 261162');
define('COMPANY_ADDRESS', '32 Kanjokya Street, Mug One House, Kamwokya');

// Currency Settings
define('CURRENCY_CODE', 'UGX');
define('CURRENCY_SYMBOL', 'UGX');

// Tax Rate (percentage)
define('DEFAULT_TAX_RATE', 10);

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DISPLAY_DATE_FORMAT', 'M d, Y');

// Email Configuration - UPDATE WITH YOUR SMTP SETTINGS
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@codecatalystlabs.com');
define('SMTP_FROM_NAME', 'Code Catalyst Labs');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    // In production, log errors instead of displaying them
    error_log("Database Connection failed: " . mysqli_connect_error());
    die("We're experiencing technical difficulties. Please try again later.");
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Configure session settings for security
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters for security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_secure', 1); // HTTPS only (important for production)
    
    // Set session lifetime (2 hours of inactivity)
    ini_set('session.gc_maxlifetime', 7200);
    ini_set('session.cookie_lifetime', 0);
    
    session_start();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    // Check for session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

// Hide errors in production (comment out during initial setup for debugging)
// ini_set('display_errors', 0);
// error_reporting(0);
?>

