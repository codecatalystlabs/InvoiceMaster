<?php
$logo_path = dirname(__DIR__) . '/assets/logo.png';
echo "Logo path: " . $logo_path . "<br>";
echo "File exists: " . (file_exists($logo_path) ? "YES" : "NO") . "<br>";
echo "File readable: " . (is_readable($logo_path) ? "YES" : "NO");
?>