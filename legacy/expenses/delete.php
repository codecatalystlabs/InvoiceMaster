<?php
/**
 * Code Catalyst Labs - Delete Expense
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

// Get expense ID
$expense_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($expense_id === 0) {
    $_SESSION['error'] = "Invalid expense ID";
    header('Location: list.php');
    exit();
}

// Get expense details
$query = "SELECT expense_number FROM expenses WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $expense_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Expense not found";
    header('Location: list.php');
    exit();
}

$expense = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Delete expense
$delete_query = "DELETE FROM expenses WHERE id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($delete_stmt, "i", $expense_id);

if (mysqli_stmt_execute($delete_stmt)) {
    // Log audit
    logAudit($conn, 'DELETE', 'expense', $expense_id, "Deleted expense: " . $expense['expense_number']);
    
    $_SESSION['success'] = "Expense deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting expense. Please try again.";
}

mysqli_stmt_close($delete_stmt);
header('Location: list.php');
exit();
?>

