<?php
/**
 * Find Logo - Debug Script
 */

echo "<h1>Logo Path Debugging</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{background:#f0f0f0;padding:10px;margin:10px 0;}</style>";

echo "<h3>Current Directory:</h3>";
echo "<div class='info'>" . __DIR__ . "</div>";

echo "<h3>Parent Directory:</h3>";
echo "<div class='info'>" . dirname(__DIR__) . "</div>";

echo "<h3>Trying Different Logo Paths:</h3>";

$paths_to_try = [
    '__DIR__ . "/../assets/logo.png"' => __DIR__ . '/../assets/logo.png',
    'dirname(__DIR__) . "/assets/logo.png"' => dirname(__DIR__) . '/assets/logo.png',
    'realpath(__DIR__ . "/../assets/logo.png")' => realpath(__DIR__ . '/../assets/logo.png'),
    '__DIR__ . "/../../assets/logo.png"' => __DIR__ . '/../../assets/logo.png',
    'getcwd() . "/assets/logo.png"' => getcwd() . '/assets/logo.png',
];

foreach ($paths_to_try as $label => $path) {
    echo "<div class='info'>";
    echo "<strong>Method:</strong> $label<br>";
    echo "<strong>Path:</strong> " . ($path ? $path : '<span class="error">NULL</span>') . "<br>";
    
    if ($path && file_exists($path)) {
        echo "<strong>Exists:</strong> <span class='success'>YES ✓</span><br>";
        echo "<strong>Readable:</strong> " . (is_readable($path) ? '<span class="success">YES ✓</span>' : '<span class="error">NO ✗</span>') . "<br>";
        echo "<strong>Size:</strong> " . number_format(filesize($path)) . " bytes<br>";
        
        // Try to get image info
        $img_info = @getimagesize($path);
        if ($img_info) {
            echo "<strong>Image Type:</strong> " . $img_info['mime'] . "<br>";
            echo "<strong>Dimensions:</strong> " . $img_info[0] . "x" . $img_info[1] . " px<br>";
        }
        
        echo "<strong class='success'>✓ THIS PATH WORKS - USE THIS ONE!</strong>";
    } else {
        echo "<strong>Exists:</strong> <span class='error'>NO ✗</span>";
    }
    echo "</div>";
}

// List files in assets directory
echo "<h3>Files in Assets Directory:</h3>";
$assets_dir = dirname(__DIR__) . '/assets';
if (is_dir($assets_dir)) {
    echo "<div class='info'>";
    echo "<strong>Directory:</strong> $assets_dir<br><br>";
    $files = scandir($assets_dir);
    echo "<strong>Files:</strong><ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $full_path = $assets_dir . '/' . $file;
            $type = is_dir($full_path) ? '[DIR]' : '[FILE]';
            echo "<li>$type $file</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>Assets directory not found at: $assets_dir</div>";
}

// Test base64 encoding if logo found
echo "<h3>Test Base64 Encoding:</h3>";
$working_path = dirname(__DIR__) . '/assets/logo.png';
if (file_exists($working_path)) {
    echo "<div class='info'>";
    echo "<strong>Testing logo at:</strong> $working_path<br>";
    $logo_data = base64_encode(file_get_contents($working_path));
    $data_length = strlen($logo_data);
    echo "<strong>Base64 length:</strong> " . number_format($data_length) . " characters<br>";
    echo "<strong>Preview (first 100 chars):</strong><br>";
    echo "<code>" . htmlspecialchars(substr($logo_data, 0, 100)) . "...</code><br><br>";
    
    // Show actual image
    echo "<strong>Image preview:</strong><br>";
    echo '<img src="data:image/png;base64,' . $logo_data . '" style="max-height:100px; border:1px solid #ccc; padding:5px;">';
    echo "</div>";
} else {
    echo "<div class='error'>Cannot test - logo file not found</div>";
}

echo "<hr>";
echo "<p><a href='../index.php'>← Back to Dashboard</a></p>";
?>

