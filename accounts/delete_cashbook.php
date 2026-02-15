<?php
/**
 * Code Catalyst Labs - Delete Cashbook Entry
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

// Get cashbook entry ID
$entry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($entry_id === 0) {
    $_SESSION['error'] = "Invalid entry ID";
    header('Location: cashbook.php');
    exit();
}

// Get entry details
$query = "SELECT * FROM cashbook WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $entry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Entry not found";
    header('Location: cashbook.php');
    exit();
}

$entry = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Check if entry is linked to invoice, expense, or service
if ($entry['invoice_id'] || $entry['expense_id'] || $entry['service_id']) {
    $_SESSION['error'] = "Cannot delete this entry because it is linked to an invoice, expense, or service. Please delete the source record instead.";
    header('Location: cashbook.php');
    exit();
}

// Delete related ledger entries first
$ledger_query = "DELETE FROM ledger_entries WHERE source_type = 'Cashbook' AND source_id = ?";
$ledger_stmt = mysqli_prepare($conn, $ledger_query);
mysqli_stmt_bind_param($ledger_stmt, "i", $entry_id);
mysqli_stmt_execute($ledger_stmt);
mysqli_stmt_close($ledger_stmt);

// Delete cashbook entry
$delete_query = "DELETE FROM cashbook WHERE id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($delete_stmt, "i", $entry_id);

if (mysqli_stmt_execute($delete_stmt)) {
    // Log audit
    logAudit($conn, 'DELETE', 'cashbook', $entry_id, "Deleted cashbook entry: " . $entry['reference_number']);
    
    $_SESSION['success'] = "Cashbook entry deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting entry. Please try again.";
}

mysqli_stmt_close($delete_stmt);
header('Location: cashbook.php');
exit();
?>

