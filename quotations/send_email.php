<?php
/**
 * Code Catalyst Labs - Send Quotation Email
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

requireLogin();
requireRole(['Admin', 'Sales']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list.php');
    exit();
}

$quotation_id = (int)$_POST['quotation_id'];
$email_to = clean($_POST['email_to']);

// Get quotation details
$query = "SELECT q.*, c.name as client_name 
          FROM quotations q 
          LEFT JOIN clients c ON q.client_id = c.id 
          WHERE q.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $quotation_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$quotation = mysqli_fetch_assoc($result);

if (!$quotation) {
    $_SESSION['error'] = 'Quotation not found.';
    header('Location: list.php');
    exit();
}

// Generate PDF
$pdf_file = __DIR__ . '/../pdf/temp/' . $quotation['quotation_number'] . '.pdf';
$pdf_url = APP_URL . '/pdf/generate_quotation.php?id=' . $quotation_id . '&save=1';

// Create temp directory if it doesn't exist
if (!file_exists(__DIR__ . '/../pdf/temp')) {
    mkdir(__DIR__ . '/../pdf/temp', 0777, true);
}

// Email details
$subject = "Quotation " . $quotation['quotation_number'] . " from " . COMPANY_NAME;
$body = getQuotationEmailBody($quotation['quotation_number'], $quotation['client_name'], $quotation['total']);

// Note: For full implementation, you would generate the PDF first
// For now, we'll send without attachment if PDF generation is not set up
$sent = sendEmail($email_to, $subject, $body);

if ($sent) {
    // Update status to Sent
    $query = "UPDATE quotations SET status = 'Sent' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $quotation_id);
    mysqli_stmt_execute($stmt);
    
    logAudit($conn, 'Email Sent', 'Quotation', $quotation_id, "Quotation emailed to $email_to");
    $_SESSION['success'] = 'Quotation sent successfully!';
} else {
    $_SESSION['error'] = 'Failed to send email. Please check email configuration.';
}

header('Location: view.php?id=' . $quotation_id);
exit();
?>

