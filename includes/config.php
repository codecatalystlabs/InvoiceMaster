<?php
/**
 * Code Catalyst Labs - Configuration File
 * Database connection and system settings
 */

// Start session FIRST before any output
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters BEFORE session_start
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 7200);
    ini_set('session.cookie_lifetime', 0);
    
    session_start();
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invoice_system');

// Application Settings
define('APP_NAME', 'Code Catalyst Labs');
define('APP_URL', 'http://localhost/invoice');
define('ITEMS_PER_PAGE', 10);

// Company Information
define('COMPANY_NAME', 'Code Catalyst Labs');
define('COMPANY_EMAIL', 'info@codecatalystug.com');
define('COMPANY_PHONE', '+256 (783) 261162');
define('COMPANY_ADDRESS', '32 Kanjokya Street, Mug One House, Kamwokya');

// Currency Settings
if (!defined('CURRENCY_CODE')) {
    define('CURRENCY_CODE', 'UGX');
}
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'UGX');
}

// Logo Settings
define('LOGO_PATH', dirname(__DIR__) . '/assets/logo.png');
define('LOGO_URL', APP_URL . '/assets/logo.png'); // For web pages
define('LOGO_HEIGHT', 60); // Logo height in pixels for PDFs

// Tax Rate (percentage)
define('DEFAULT_TAX_RATE', 0);

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DISPLAY_DATE_FORMAT', 'M d, Y');

// Email Configuration (SMTP)
define('SMTP_HOST', 'mail.codecatalystug.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'info@codecatalystug.com');
define('SMTP_PASSWORD', 'Cod3C@t@!ystUg');
define('SMTP_FROM_EMAIL', 'info@codecatalystug.com');
define('SMTP_FROM_NAME', 'Code Catalyst Labs');

// Email Configuration (IMAP - for receiving replies)
define('IMAP_ENABLED', false); // Set to true to enable reply tracking
define('IMAP_HOST', 'mail.codecatalystug.com');
define('IMAP_PORT', 993);
define('IMAP_USERNAME', 'info@codecatalystug.com');
define('IMAP_PASSWORD', 'Cod3C@t@!ystUg');
define('IMAP_SSL', true);
define('IMAP_FOLDER', 'INBOX');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Session management (session already started at top of file)
// Regenerate session ID periodically to prevent session fixation
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    // Regenerate session ID every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Check for session timeout (2 hours of inactivity)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    // Last request was more than 2 hours ago
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time(); // Update last activity time
