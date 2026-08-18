<?php
/**
 * Test GD Extension
 */
echo "<!DOCTYPE html>";
echo "<html><head><title>GD Extension Test</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light p-5'>";
echo "<div class='container'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-primary text-white'><h3>PHP GD Extension Test</h3></div>";
echo "<div class='card-body'>";

if (extension_loaded('gd')) {
    echo "<div class='alert alert-success'>";
    echo "<h4><i class='bi bi-check-circle'></i> Success!</h4>";
    echo "<p>GD extension is <strong>ENABLED</strong> and working properly.</p>";
    echo "</div>";
    
    $gd_info = gd_info();
    echo "<h5>GD Information:</h5>";
    echo "<table class='table table-bordered'>";
    foreach ($gd_info as $key => $value) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
        echo "<td>" . (is_bool($value) ? ($value ? 'Yes' : 'No') : htmlspecialchars($value)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='alert alert-info'>";
    echo "<strong>Next Steps:</strong><br>";
    echo "1. Delete this test file (test_gd.php) for security<br>";
    echo "2. Your application can now generate PDFs properly<br>";
    echo "3. Try logging in at: <a href='auth/login.php'>Login Page</a>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'>";
    echo "<h4><i class='bi bi-x-circle'></i> Error!</h4>";
    echo "<p>GD extension is <strong>NOT LOADED</strong>.</p>";
    echo "<p>Please restart Apache using the XAMPP Control Panel:</p>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Stop' next to Apache</li>";
    echo "<li>Wait a few seconds</li>";
    echo "<li>Click 'Start' next to Apache</li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<div class='btn-group mt-3'>";
echo "<a href='test_gd.php' class='btn btn-secondary'>Refresh Test</a>";
echo "<a href='auth/login.php' class='btn btn-primary'>Go to Login</a>";
echo "</div>";

echo "</div></div></div>";
echo "</body></html>";
?>

