<?php
// Simple PHP Info and Diagnostics
// This file is not blocked by .htaccess

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Server Info</title>";
echo "<style>body{font-family:Arial;padding:20px;} .box{background:#f0f0f0;padding:15px;margin:10px 0;border-radius:5px;} .error{background:#ffcccc;} .success{background:#ccffcc;} .warning{background:#ffffcc;}</style>";
echo "</head><body>";
echo "<h1>Server Diagnostics</h1>";

// PHP Version
echo "<div class='box success'>";
echo "<h3>✓ PHP Version: " . phpversion() . "</h3>";
echo "</div>";

// Required Extensions
echo "<div class='box'>";
echo "<h3>PHP Extensions:</h3>";
$exts = ['mysqli', 'gd', 'mbstring', 'curl'];
foreach ($exts as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p style='color:" . ($loaded ? 'green' : 'red') . "'>" . $ext . ": " . ($loaded ? "✓ Installed" : "✗ MISSING") . "</p>";
}
echo "</div>";

// Database Test
echo "<div class='box'>";
echo "<h3>Database Connection Test:</h3>";
$db_host = 'localhost';
$db_user = 'codecata_invoice';
$db_pass = 'codecata_invoice';
$db_name = 'codecata_invoice';

echo "<p><strong>Attempting connection...</strong></p>";
echo "<p>Host: $db_host</p>";
echo "<p>Database: $db_name</p>";

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if ($conn) {
    echo "<p style='color:green;'><strong>✓ Database connection successful!</strong></p>";
    
    // Check tables
    $tables = ['users', 'clients', 'quotations', 'invoices'];
    echo "<p><strong>Tables:</strong></p>";
    foreach ($tables as $table) {
        $result = @mysqli_query($conn, "SELECT 1 FROM $table LIMIT 1");
        $exists = $result !== false;
        echo "<p style='color:" . ($exists ? 'green' : 'red') . "'>- $table: " . ($exists ? "✓ Exists" : "✗ Missing") . "</p>";
    }
    mysqli_close($conn);
} else {
    echo "<p style='color:red;'><strong>✗ Database connection FAILED!</strong></p>";
    echo "<p style='color:red;'>Error: " . mysqli_connect_error() . "</p>";
    echo "<div class='box error'>";
    echo "<h4>Fix Database Connection:</h4>";
    echo "<ol>";
    echo "<li>Get your database credentials from cPanel → MySQL Databases</li>";
    echo "<li>Update the credentials at the top of this file (info.php)</li>";
    echo "<li>Or update includes/config.php with correct credentials</li>";
    echo "</ol>";
    echo "</div>";
}
echo "</div>";

// File Permissions
echo "<div class='box'>";
echo "<h3>File Permissions:</h3>";
$paths = ['includes/config.php', 'pdf/temp/', 'vendor/autoload.php'];
foreach ($paths as $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        echo "<p style='color:green;'>$path: $perms " . ($writable ? "(writable)" : "(read-only)") . "</p>";
    } else {
        echo "<p style='color:red;'>$path: NOT FOUND</p>";
    }
}
echo "</div>";

// Composer
echo "<div class='box'>";
echo "<h3>Composer Dependencies:</h3>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color:green;'>✓ Composer vendor folder exists</p>";
} else {
    echo "<p style='color:red;'>✗ Vendor folder MISSING - Upload vendor/ folder or run 'composer install'</p>";
}
echo "</div>";

// .htaccess
echo "<div class='box warning'>";
echo "<h3>⚠ Important Notes:</h3>";
echo "<ul>";
echo "<li>Update database credentials in <strong>includes/config.php</strong></li>";
echo "<li>Change APP_URL to: <strong>https://codecatalystug.com/invoice</strong></li>";
echo "<li>Import database.sql in phpMyAdmin</li>";
echo "<li>Upload vendor/ folder</li>";
echo "<li><strong>DELETE this info.php file after fixing issues</strong></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='index.php'>← Go to Application</a> | <a href='auth/login.php'>Go to Login</a></p>";
echo "</body></html>";
?>

