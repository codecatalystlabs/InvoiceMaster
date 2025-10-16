<?php
/**
 * Fix Admin Password Script
 * This script will reset the admin password to 'admin123'
 */
require_once 'includes/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Admin Password</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "</head><body class='bg-light p-5'>";
echo "<div class='container'>";
echo "<div class='card shadow'>";
echo "<div class='card-header bg-primary text-white'><h3>Fix Admin Password</h3></div>";
echo "<div class='card-body'>";

// Generate correct password hash for 'admin123'
$correct_password = 'admin123';
$password_hash = password_hash($correct_password, PASSWORD_DEFAULT);

echo "<h5>Password Hash Information:</h5>";
echo "<div class='alert alert-info'>";
echo "<strong>New Password:</strong> admin123<br>";
echo "<strong>New Hash:</strong> <code>" . htmlspecialchars($password_hash) . "</code>";
echo "</div>";

// Check if admin user exists
$query = "SELECT id, username, email FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    // Update existing admin user
    $update_query = "UPDATE users SET password = ? WHERE username = 'admin'";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "s", $password_hash);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<div class='alert alert-success'>";
        echo "<i class='bi bi-check-circle'></i> <strong>Success!</strong> Admin password has been updated to: <code>admin123</code>";
        echo "</div>";
        
        $user = mysqli_fetch_assoc($result);
        echo "<h5>Admin User Details:</h5>";
        echo "<ul class='list-group mb-3'>";
        echo "<li class='list-group-item'><strong>ID:</strong> " . $user['id'] . "</li>";
        echo "<li class='list-group-item'><strong>Username:</strong> " . $user['username'] . "</li>";
        echo "<li class='list-group-item'><strong>Email:</strong> " . $user['email'] . "</li>";
        echo "<li class='list-group-item'><strong>Password:</strong> admin123</li>";
        echo "</ul>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<i class='bi bi-x-circle'></i> <strong>Error!</strong> Failed to update password: " . mysqli_error($conn);
        echo "</div>";
    }
    mysqli_stmt_close($stmt);
} else {
    // Create new admin user
    echo "<div class='alert alert-warning'>No admin user found. Creating new admin user...</div>";
    
    $insert_query = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'Admin', 'Active')";
    $stmt = mysqli_prepare($conn, $insert_query);
    $admin_email = 'admin@codecatalystlabs.com';
    mysqli_stmt_bind_param($stmt, "sss", $username, $admin_email, $password_hash);
    $username = 'admin';
    
    if (mysqli_stmt_execute($stmt)) {
        echo "<div class='alert alert-success'>";
        echo "<i class='bi bi-check-circle'></i> <strong>Success!</strong> Admin user created with password: <code>admin123</code>";
        echo "</div>";
        
        echo "<h5>New Admin User Details:</h5>";
        echo "<ul class='list-group mb-3'>";
        echo "<li class='list-group-item'><strong>Username:</strong> admin</li>";
        echo "<li class='list-group-item'><strong>Email:</strong> admin@codecatalystlabs.com</li>";
        echo "<li class='list-group-item'><strong>Password:</strong> admin123</li>";
        echo "<li class='list-group-item'><strong>Role:</strong> Admin</li>";
        echo "</ul>";
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<i class='bi bi-x-circle'></i> <strong>Error!</strong> Failed to create admin user: " . mysqli_error($conn);
        echo "</div>";
    }
    mysqli_stmt_close($stmt);
}

echo "<h5>Next Steps:</h5>";
echo "<div class='alert alert-info'>";
echo "<ol class='mb-0'>";
echo "<li>Go to the login page</li>";
echo "<li>Use username: <code>admin</code></li>";
echo "<li>Use password: <code>admin123</code></li>";
echo "<li><strong>Delete this file (fix_admin_password.php) after successful login for security</strong></li>";
echo "</ol>";
echo "</div>";

echo "<div class='btn-group mt-3' role='group'>";
echo "<a href='auth/login.php' class='btn btn-primary'>Go to Login Page</a>";
echo "<a href='fix_admin_password.php' class='btn btn-secondary'>Refresh</a>";
echo "</div>";

echo "</div></div></div>";
echo "</body></html>";

mysqli_close($conn);
?>

