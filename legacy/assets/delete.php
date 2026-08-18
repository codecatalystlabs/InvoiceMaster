<?php
/**
 * Code Catalyst Labs - Delete Asset
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

// Get asset ID
$asset_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($asset_id === 0) {
    $_SESSION['error'] = "Invalid asset ID";
    header('Location: list.php');
    exit();
}

// Get asset details
$query = "SELECT asset_number FROM assets WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $asset_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Asset not found";
    header('Location: list.php');
    exit();
}

$asset = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Delete asset (valuations will be cascade deleted)
$delete_query = "DELETE FROM assets WHERE id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_query);
mysqli_stmt_bind_param($delete_stmt, "i", $asset_id);

if (mysqli_stmt_execute($delete_stmt)) {
    // Log audit
    logAudit($conn, 'DELETE', 'asset', $asset_id, "Deleted asset: " . $asset['asset_number']);
    
    $_SESSION['success'] = "Asset deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting asset. Please try again.";
}

mysqli_stmt_close($delete_stmt);
header('Location: list.php');
exit();
?>

