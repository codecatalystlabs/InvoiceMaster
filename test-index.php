<?php
/**
 * Test Index - Debug the blank page issue
 */

// Enable ALL error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Testing Application Load</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<hr>";

// Step 1: Test config
echo "<h3>Step 1: Loading config.php...</h3>";
try {
    require_once 'includes/config.php';
    echo "<p style='color:green;'>✓ Config loaded</p>";
    echo "<p>Database: " . (isset($conn) && $conn ? "Connected" : "Not connected") . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red;'>✗ Config Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

// Step 2: Test functions
echo "<h3>Step 2: Loading functions.php...</h3>";
try {
    require_once 'includes/functions.php';
    echo "<p style='color:green;'>✓ Functions loaded</p>";
} catch (Throwable $e) {
    echo "<p style='color:red;'>✗ Functions Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    die();
}

// Step 3: Test requireLogin function
echo "<h3>Step 3: Testing requireLogin()...</h3>";
try {
    echo "<p>isLoggedIn: " . (isLoggedIn() ? "true" : "false") . "</p>";
    echo "<p>If not logged in, requireLogin() will redirect to login page...</p>";
    echo "<p style='color:green;'>✓ Functions working</p>";
} catch (Throwable $e) {
    echo "<p style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Step 4: Test database query
echo "<h3>Step 4: Testing database query...</h3>";
try {
    $query = "SELECT COUNT(*) as total FROM users";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p style='color:green;'>✓ Database query successful</p>";
        echo "<p>Total users: " . $row['total'] . "</p>";
    } else {
        echo "<p style='color:red;'>✗ Query failed: " . mysqli_error($conn) . "</p>";
    }
} catch (Throwable $e) {
    echo "<p style='color:red;'>✗ Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Conclusion:</h3>";
echo "<p>If you see this message, the core application files are working!</p>";
echo "<p>The blank page issue might be caused by:</p>";
echo "<ul>";
echo "<li>Redirect loop (requireLogin keeps redirecting)</li>";
echo "<li>Session issues with cookies</li>";
echo "<li>Missing GD or mbstring extensions</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Try these:</strong></p>";
echo "<p><a href='index.php'>Go to index.php</a></p>";
echo "<p><a href='auth/login.php'>Go to login page directly</a></p>";
?>

