<?php
/**
 * Code Catalyst Labs - Convert Quotation to Invoice
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Sales']);

$quotation_id = isset($_GET['quotation_id']) ? (int)$_GET['quotation_id'] : 0;

// Get quotation
$query = "SELECT * FROM quotations WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $quotation_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quotation = mysqli_fetch_assoc($result);

if (!$quotation) {
    $_SESSION['error'] = 'Quotation not found.';
    header('Location: ../quotations/list.php');
    exit();
}

if ($quotation['status'] === 'Converted') {
    $_SESSION['error'] = 'This quotation has already been converted.';
    header('Location: ../quotations/view.php?id=' . $quotation_id);
    exit();
}

// Get quotation items
$query = "SELECT * FROM quotation_items WHERE quotation_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $quotation_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);

// Generate invoice number
$invoice_number = generateInvoiceNumber($conn);

// Create invoice
$date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+30 days'));
$status = 'Unpaid';

$query = "INSERT INTO invoices (quotation_id, client_id, invoice_number, date, due_date, subtotal, tax, discount, total, status, notes) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iisssddddss", 
    $quotation_id, 
    $quotation['client_id'], 
    $invoice_number, 
    $date, 
    $due_date, 
    $quotation['subtotal'], 
    $quotation['tax'], 
    $quotation['discount'], 
    $quotation['total'], 
    $status,
    $quotation['notes']
);

if (mysqli_stmt_execute($stmt)) {
    $invoice_id = mysqli_insert_id($conn);
    
    // Copy items
    $item_query = "INSERT INTO invoice_items (invoice_id, item_name, qty, unit_price, total) VALUES (?, ?, ?, ?, ?)";
    $item_stmt = mysqli_prepare($conn, $item_query);
    
    while ($item = mysqli_fetch_assoc($items_result)) {
        mysqli_stmt_bind_param($item_stmt, "isidd", 
            $invoice_id, 
            $item['item_name'], 
            $item['qty'], 
            $item['unit_price'], 
            $item['total']
        );
        mysqli_stmt_execute($item_stmt);
    }
    
    mysqli_stmt_close($item_stmt);
    
    // Update quotation status to Converted
    $update_query = "UPDATE quotations SET status = 'Converted' WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $quotation_id);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    logAudit($conn, 'Convert', 'Invoice', $invoice_id, "Quotation {$quotation['quotation_number']} converted to Invoice $invoice_number");
    $_SESSION['success'] = 'Quotation converted to invoice successfully!';
    header('Location: view.php?id=' . $invoice_id);
    exit();
} else {
    $_SESSION['error'] = 'Failed to convert quotation.';
    header('Location: ../quotations/view.php?id=' . $quotation_id);
    exit();
}
?>

