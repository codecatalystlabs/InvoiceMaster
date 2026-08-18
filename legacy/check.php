<?php
// Force display errors to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Error Check</h1>";
echo "<p>If you see this, PHP is working...</p>";

// Try to include config
echo "<h3>Testing config.php...</h3>";
try {
    require_once 'includes/config.php';
    echo "<p style='color:green;'>✓ Config loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Config Error: " . $e->getMessage() . "</p>";
}

// Try to include functions
echo "<h3>Testing functions.php...</h3>";
try {
    require_once 'includes/functions.php';
    echo "<p style='color:green;'>✓ Functions loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Functions Error: " . $e->getMessage() . "</p>";
}

// Test database connection
echo "<h3>Testing Database...</h3>";
if (isset($conn) && $conn) {
    echo "<p style='color:green;'>✓ Database connected</p>";
} else {
    echo "<p style='color:red;'>✗ Database NOT connected</p>";
}

// Check PHP version compatibility
echo "<h3>PHP Version Issues:</h3>";
$php_version = phpversion();
echo "<p>Current PHP: $php_version</p>";

if (version_compare($php_version, '7.0.0', '<')) {
    echo "<p style='color:red;'><strong>⚠ WARNING: PHP 5.6 is very old and may have compatibility issues!</strong></p>";
    echo "<p>Your code uses features that may not work in PHP 5.6:</p>";
    echo "<ul>";
    echo "<li>Some password functions may behave differently</li>";
    echo "<li>Session handling may be different</li>";
    echo "<li>Consider upgrading to PHP 7.4 or 8.0</li>";
    echo "</ul>";
}

// Test session
echo "<h3>Testing Session...</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color:green;'>✓ Session is active</p>";
} else {
    echo "<p style='color:red;'>✗ Session not started</p>";
}

// Check for specific PHP 5.6 issues
echo "<h3>Checking PHP 5.6 Specific Issues:</h3>";
if (version_compare($php_version, '7.0.0', '<')) {
    echo "<p style='color:orange;'>Testing session_cookie_samesite (not supported in PHP 5.6)...</p>";
    echo "<p>This is likely causing your blank page!</p>";
}

echo "<hr>";
echo "<p><strong>Next Step:</strong> If you see errors above, we'll fix them. Otherwise, the issue is in session configuration.</p>";
?>

