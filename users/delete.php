<?php
/**
 * Code Catalyst Labs - Delete User
 * Admin only
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireRole('Admin');

// Get user ID
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id === 0) {
    $_SESSION['error'] = "Invalid user ID";
    header('Location: list.php');
    exit();
}

// Cannot delete yourself
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account";
    header('Location: list.php');
    exit();
}

// Get user details
$query = "SELECT username FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "User not found";
    header('Location: list.php');
    exit();
}

$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Delete user
$delete_query = "DELETE FROM users WHERE id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($delete_stmt, "i", $user_id);

if (mysqli_stmt_execute($delete_stmt)) {
    // Log audit
    logAudit($conn, 'DELETE', 'user', $user_id, "Deleted user: " . $user['username']);
    
    $_SESSION['success'] = "User deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting user. Please try again.";
}

mysqli_stmt_close($delete_stmt);
header('Location: list.php');
exit();
?>

