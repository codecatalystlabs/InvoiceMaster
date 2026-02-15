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

// Process CC emails
$email_cc = [];
if (!empty($_POST['email_cc'])) {
    $cc_string = clean($_POST['email_cc']);
    $cc_array = explode(',', $cc_string);
    foreach ($cc_array as $email) {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_cc[] = $email;
        }
    }
}

// Process BCC emails
$email_bcc = [];
if (!empty($_POST['email_bcc'])) {
    $bcc_string = clean($_POST['email_bcc']);
    $bcc_array = explode(',', $bcc_string);
    foreach ($bcc_array as $email) {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_bcc[] = $email;
        }
    }
}

// Get invoice details with full client info
$query = "SELECT i.*, 
          c.name as client_name, 
          c.email as client_email, 
          c.phone as client_phone, 
          c.company as client_company
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

// Get invoice items
$query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $invoice_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $items[] = $row;
}

// Generate PDF
$pdf_file = __DIR__ . '/../pdf/temp/' . $invoice['invoice_number'] . '.pdf';

// Create temp directory if it doesn't exist
if (!file_exists(__DIR__ . '/../pdf/temp')) {
    mkdir(__DIR__ . '/../pdf/temp', 0777, true);
}

// Generate PDF and save to file
$pdf_generated = false;
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $pdf_generated = generateInvoicePDF($invoice, $items, $pdf_file);
} catch (Exception $e) {
    error_log("PDF Generation Error: " . $e->getMessage());
}

// Email details
$subject = "Invoice " . $invoice['invoice_number'] . " from " . COMPANY_NAME;
$body = getInvoiceEmailBody($invoice['invoice_number'], $invoice['client_name'], $invoice['total'], $invoice['due_date']);

// Send email with PDF attachment if generated
$attachment = ($pdf_generated && file_exists($pdf_file)) ? $pdf_file : null;
$email_result = sendEmail($email_to, $subject, $body, $attachment, $email_cc, $email_bcc, 'invoice', $invoice_id);

if ($email_result['success']) {
    $recipients_list = $email_to;
    if (!empty($email_cc)) {
        $recipients_list .= ', CC: ' . implode(', ', $email_cc);
    }
    if (!empty($email_bcc)) {
        $recipients_list .= ', BCC: ' . implode(', ', $email_bcc);
    }
    logAudit($conn, 'Email Sent', 'Invoice', $invoice_id, "Invoice emailed to $recipients_list");
    
    $success_msg = 'Invoice sent successfully!';
    if (!$pdf_generated) {
        $success_msg .= ' (Note: PDF attachment could not be generated)';
    }
    $_SESSION['success'] = $success_msg;
    
    // Clean up temporary PDF file
    if (file_exists($pdf_file)) {
        unlink($pdf_file);
    }
} else {
    $error_msg = 'Failed to send email.';
    if (!empty($email_result['error'])) {
        $error_msg .= ' Error: ' . $email_result['error'];
    } else if (isset($_SESSION['email_debug'])) {
        $error_msg .= ' Error: ' . $_SESSION['email_debug'];
        unset($_SESSION['email_debug']);
    }
    $_SESSION['error'] = $error_msg;
}

header('Location: view.php?id=' . $invoice_id);
exit();
?>

