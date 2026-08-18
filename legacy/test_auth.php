<?php
/**
 * Authentication Test Script
 * This script helps verify that authentication is working correctly
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Authentication Test</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light p-5'>";
echo "<div class='container'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-primary text-white'><h3>Authentication Test Results</h3></div>";
echo "<div class='card-body'>";

echo "<h5>Session Status:</h5>";
echo "<ul class='list-group mb-3'>";
echo "<li class='list-group-item'><strong>Session ID:</strong> " . session_id() . "</li>";
echo "<li class='list-group-item'><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</li>";
echo "</ul>";

echo "<h5>Session Data:</h5>";
if (empty($_SESSION)) {
    echo "<div class='alert alert-warning'>No session data found (User is NOT logged in)</div>";
} else {
    echo "<pre class='bg-light p-3 border rounded'>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h5>Authentication Check:</h5>";
echo "<ul class='list-group mb-3'>";
echo "<li class='list-group-item'><strong>isLoggedIn():</strong> " . (isLoggedIn() ? '<span class="badge bg-success">TRUE</span>' : '<span class="badge bg-danger">FALSE</span>') . "</li>";
if (isLoggedIn()) {
    echo "<li class='list-group-item'><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'N/A') . "</li>";
    echo "<li class='list-group-item'><strong>Username:</strong> " . ($_SESSION['username'] ?? 'N/A') . "</li>";
    echo "<li class='list-group-item'><strong>Role:</strong> " . ($_SESSION['role'] ?? 'N/A') . "</li>";
}
echo "</ul>";

echo "<h5>Expected Behavior:</h5>";
echo "<div class='alert alert-info'>";
echo "<ul class='mb-0'>";
echo "<li>If isLoggedIn() is <strong>FALSE</strong>, accessing index.php should redirect to login page</li>";
echo "<li>If isLoggedIn() is <strong>TRUE</strong>, you can access the dashboard</li>";
echo "<li>Session should expire after 2 hours of inactivity</li>";
echo "</ul>";
echo "</div>";

echo "<h5>Actions:</h5>";
echo "<div class='btn-group' role='group'>";
echo "<a href='auth/login.php' class='btn btn-primary'>Go to Login</a>";
echo "<a href='index.php' class='btn btn-secondary'>Go to Dashboard</a>";
echo "<a href='auth/logout.php' class='btn btn-danger'>Logout</a>";
echo "<a href='test_auth.php' class='btn btn-info'>Refresh Test</a>";
echo "</div>";

echo "</div></div></div>";
echo "</body></html>";
?>

