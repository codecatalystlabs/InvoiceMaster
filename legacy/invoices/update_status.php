<?php
/**
 * Code Catalyst Labs - Update Invoice Status
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$invoice_id = (int)$_POST['invoice_id'];
$status = clean($_POST['status']);

$query = "UPDATE invoices SET status = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "si", $status, $invoice_id);

if (mysqli_stmt_execute($stmt)) {
    logAudit($conn, 'Update', 'Invoice', $invoice_id, "Invoice status changed to $status");
    $_SESSION['success'] = "Invoice status updated to $status!";
} else {
    $_SESSION['error'] = 'Failed to update invoice status.';
}

mysqli_stmt_close($stmt);

header('Location: view.php?id=' . $invoice_id);
exit();
?>

