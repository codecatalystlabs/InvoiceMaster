<?php
/**
 * Debug Script - Production Server Diagnostics
 * This helps identify the exact error causing the 500 error
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Server Diagnostics</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light p-4'>";
echo "<div class='container'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-primary text-white'><h3>Server Diagnostics</h3></div>";
echo "<div class='card-body'>";

// PHP Version
echo "<h5>PHP Environment:</h5>";
echo "<table class='table table-bordered table-sm'>";
echo "<tr><td><strong>PHP Version</strong></td><td>" . phpversion() . "</td></tr>";
echo "<tr><td><strong>Server Software</strong></td><td>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>";
echo "<tr><td><strong>Document Root</strong></td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>";
echo "<tr><td><strong>Script Path</strong></td><td>" . __FILE__ . "</td></tr>";
echo "</table>";

// Check required extensions
echo "<h5>Required PHP Extensions:</h5>";
$required_extensions = ['mysqli', 'gd', 'mbstring', 'curl', 'zip', 'xml'];
echo "<table class='table table-bordered table-sm'>";
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    $badge = $loaded ? 'bg-success' : 'bg-danger';
    $status = $loaded ? 'Installed' : 'MISSING';
    echo "<tr><td><strong>$ext</strong></td><td><span class='badge $badge'>$status</span></td></tr>";
}
echo "</table>";

// Check file permissions
echo "<h5>File Permissions:</h5>";
$paths_to_check = [
    'includes/config.php',
    'pdf/temp/',
    '.htaccess'
];

echo "<table class='table table-bordered table-sm'>";
foreach ($paths_to_check as $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path);
        $badge = $writable ? 'bg-success' : 'bg-warning';
        echo "<tr><td><strong>$path</strong></td><td>$perms <span class='badge $badge'>" . ($writable ? 'Writable' : 'Read-only') . "</span></td></tr>";
    } else {
        echo "<tr><td><strong>$path</strong></td><td><span class='badge bg-danger'>Not Found</span></td></tr>";
    }
}
echo "</table>";

// Check database connection
echo "<h5>Database Connection Test:</h5>";
echo "<div class='alert alert-info'>";
echo "<strong>Note:</strong> Update the credentials below in config.php for your production database.";
echo "</div>";

// Try to include config
try {
    if (file_exists('includes/config.php')) {
        ob_start();
        include 'includes/config.php';
        $config_output = ob_get_clean();
        
        if ($config_output) {
            echo "<div class='alert alert-warning'><strong>Config Output:</strong><pre>" . htmlspecialchars($config_output) . "</pre></div>";
        }
        
        if (isset($conn) && $conn) {
            echo "<div class='alert alert-success'><i class='bi bi-check-circle'></i> Database connection successful!</div>";
            echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
            
            // Check if tables exist
            $tables = ['users', 'clients', 'quotations', 'invoices'];
            echo "<h6>Tables:</h6>";
            echo "<ul class='list-group'>";
            foreach ($tables as $table) {
                $result = @mysqli_query($conn, "SELECT 1 FROM $table LIMIT 1");
                $exists = $result !== false;
                $badge = $exists ? 'bg-success' : 'bg-danger';
                $status = $exists ? 'Exists' : 'Missing';
                echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                echo "$table <span class='badge $badge'>$status</span>";
                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<div class='alert alert-danger'><i class='bi bi-x-circle'></i> Database connection failed!</div>";
            echo "<p><strong>Error:</strong> " . (isset($conn) ? mysqli_connect_error() : 'Connection object not created') . "</p>";
        }
    } else {
        echo "<div class='alert alert-danger'>Config file not found!</div>";
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'><strong>Error loading config:</strong> " . $e->getMessage() . "</div>";
}

// Check .htaccess
echo "<h5>.htaccess Configuration:</h5>";
if (file_exists('.htaccess')) {
    echo "<div class='alert alert-success'>.htaccess file exists</div>";
    $htaccess_content = file_get_contents('.htaccess');
    if (strpos($htaccess_content, '/invoice/') !== false) {
        echo "<div class='alert alert-warning'>";
        echo "<strong>Warning:</strong> .htaccess still contains '/invoice/' path. Update RewriteBase for production.";
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-warning'>.htaccess file not found</div>";
}

// Check vendor folder
echo "<h5>Composer Dependencies:</h5>";
if (file_exists('vendor/autoload.php')) {
    echo "<div class='alert alert-success'>Composer dependencies installed</div>";
} else {
    echo "<div class='alert alert-danger'><strong>Error:</strong> Composer dependencies not found. Run 'composer install' on the server.</div>";
}

// Configuration checklist
echo "<h5>Production Configuration Checklist:</h5>";
echo "<div class='alert alert-info'>";
echo "<ol class='mb-0'>";
echo "<li>Update <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> in config.php</li>";
echo "<li>Update <code>APP_URL</code> to 'https://codecatalystug.com/invoice'</li>";
echo "<li>Update <code>RewriteBase</code> in .htaccess to '/invoice/' or '/' depending on setup</li>";
echo "<li>Upload and run database.sql on production database</li>";
echo "<li>Upload vendor/ folder or run 'composer install' on server</li>";
echo "<li>Set pdf/temp/ folder permissions to 755 or 777</li>";
echo "<li>Delete this debug.php file after fixing issues</li>";
echo "</ol>";
echo "</div>";

echo "</div></div></div>";
echo "</body></html>";
?>

