<?php
/**
 * Test PDF Logo Generation
 */
require_once '../includes/config.php';

// Check if composer is loaded
if (!file_exists('../vendor/autoload.php')) {
    die('Composer vendor folder not found. Run: composer install');
}

require_once '../vendor/autoload.php';

echo "<h1>Testing PDF Logo Generation</h1>";

// Check GD extension
echo "<h3>1. GD Extension Check:</h3>";
if (extension_loaded('gd')) {
    echo "<p style='color:green;'>✓ GD extension is loaded (required for image processing)</p>";
    $gd_info = gd_info();
    echo "<ul>";
    echo "<li>GD Version: " . $gd_info['GD Version'] . "</li>";
    echo "<li>PNG Support: " . ($gd_info['PNG Support'] ? 'Yes' : 'No') . "</li>";
    echo "<li>JPEG Support: " . ($gd_info['JPEG Support'] ? 'Yes' : 'No') . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color:red;'>✗ GD extension NOT loaded (images won't work in PDF)</p>";
}

// Check logo file
echo "<h3>2. Logo File Check:</h3>";
$logo_path = dirname(__DIR__) . '/assets/logo.png';
echo "<p><strong>Logo Path:</strong> $logo_path</p>";
echo "<p><strong>File Exists:</strong> " . (file_exists($logo_path) ? '<span style="color:green;">YES</span>' : '<span style="color:red;">NO</span>') . "</p>";
echo "<p><strong>File Readable:</strong> " . (is_readable($logo_path) ? '<span style="color:green;">YES</span>' : '<span style="color:red;">NO</span>') . "</p>";
echo "<p><strong>File Size:</strong> " . (file_exists($logo_path) ? number_format(filesize($logo_path)) . ' bytes' : 'N/A') . "</p>";

// Check image info
if (file_exists($logo_path) && extension_loaded('gd')) {
    $image_info = getimagesize($logo_path);
    if ($image_info) {
        echo "<p><strong>Image Dimensions:</strong> {$image_info[0]} x {$image_info[1]} pixels</p>";
        echo "<p><strong>Image Type:</strong> {$image_info['mime']}</p>";
    }
}

// Try to generate a simple PDF with logo
echo "<h3>3. Testing PDF Generation:</h3>";
try {
    $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; }
            .logo { margin: 20px 0; }
            h1 { color: #0d6efd; }
        </style>
    </head>
    <body>
        <div class="logo">';
    
    if (file_exists($logo_path)) {
        $html .= '<img src="' . $logo_path . '" alt="Logo" style="height: 80px;">';
    }
    
    $html .= '
        </div>
        <h1>Test PDF with Logo</h1>
        <p>If you see the logo above, it is working correctly!</p>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    </body>
    </html>';
    
    $mpdf->WriteHTML($html);
    
    // Output as download
    $filename = 'test-logo-' . date('YmdHis') . '.pdf';
    $mpdf->Output($filename, 'D');
    
    echo "<p style='color:green;'>✓ PDF generated successfully! Check your downloads.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ PDF Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='../index.php'>← Back to Dashboard</a></p>";
?>

