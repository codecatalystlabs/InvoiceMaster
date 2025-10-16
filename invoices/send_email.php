<?php
/**
 * Code Catalyst Labs - Send Invoice Email
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

requireLogin();
requireRole(['Admin', 'Finance', 'Sales']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$invoice_id = (int)$_POST['invoice_id'];
$email_to = clean($_POST['email_to']);

// Get invoice details
$query = "SELECT i.*, c.name as client_name 
          FROM invoices i 
          LEFT JOIN clients c ON i.client_id = c.id 
          WHERE i.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $invoice_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    $_SESSION['error'] = 'Invoice not found.';
    header('Location: list.php');
    exit();
}

// Email details
$subject = "Invoice " . $invoice['invoice_number'] . " from " . COMPANY_NAME;
$body = getInvoiceEmailBody($invoice['invoice_number'], $invoice['client_name'], $invoice['total'], $invoice['due_date']);

$sent = sendEmail($email_to, $subject, $body);

if ($sent) {
    logAudit($conn, 'Email Sent', 'Invoice', $invoice_id, "Invoice emailed to $email_to");
    $_SESSION['success'] = 'Invoice sent successfully!';
} else {
    $_SESSION['error'] = 'Failed to send email. Please check email configuration.';
}

header('Location: view.php?id=' . $invoice_id);
exit();
?>

