<?php
/**
 * Code Catalyst Labs - Generate Invoice PDF
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get invoice
$query = "SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone, c.company as client_company
          FROM invoices i 
          LEFT JOIN clients c ON i.client_id = c.id 
          WHERE i.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    die('Invoice not found.');
}

// Get items
$query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

// Check if mPDF is installed
if (!file_exists('../vendor/autoload.php')) {
    die('
    <div style="font-family: Arial, sans-serif; padding: 40px; text-align: center;">
        <h2>PDF Library Not Installed</h2>
        <p>Please run the following command to install dependencies:</p>
        <code style="background: #f4f4f4; padding: 10px; display: inline-block; border-radius: 5px;">
            composer install
        </code>
        <p style="margin-top: 20px;">
            <a href="../invoices/view.php?id=' . $id . '">← Back to Invoice</a>
        </p>
    </div>
    ');
}

require_once '../vendor/autoload.php';

// Create PDF
try {
    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 20,
        'margin_bottom' => 20,
    ]);
    
    // Determine status color
    $status_colors = [
        'Unpaid' => '#ffc107',
        'Paid' => '#28a745',
        'Overdue' => '#dc3545',
        'Partially Paid' => '#17a2b8',
        'Cancelled' => '#6c757d'
    ];
    $status_color = isset($status_colors[$invoice['status']]) ? $status_colors[$invoice['status']] : '#6c757d';
    
    // Build HTML content
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; font-size: 11pt; }
            .header { margin-bottom: 30px; }
            .company-logo { text-align: center; margin-bottom: 20px; }
            .company-name { font-size: 24pt; color: #0d6efd; font-weight: bold; }
            .company-info { font-size: 9pt; color: #666; }
            .invoice-title { font-size: 20pt; color: #333; margin: 20px 0; }
            .info-section { margin-bottom: 20px; }
            .info-label { font-weight: bold; color: #666; font-size: 9pt; }
            .info-value { font-size: 10pt; }
            .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .items-table th { background-color: #0d6efd; color: white; padding: 10px; text-align: left; }
            .items-table td { padding: 8px; border-bottom: 1px solid #ddd; }
            .items-table tfoot td { font-weight: bold; padding: 10px; }
            .total-row { background-color: #e7f1ff; font-size: 12pt; }
            .status-badge { background-color: ' . $status_color . '; color: white; padding: 5px 15px; border-radius: 5px; display: inline-block; }
            .due-date-box { background-color: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 20px 0; text-align: center; }
            .footer { margin-top: 40px; text-align: center; font-size: 9pt; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="company-logo">';
    
    // Add logo if it exists - use base64 encoding for mPDF (required!)
    if (file_exists(LOGO_PATH)) {
        $logo_data = base64_encode(file_get_contents(LOGO_PATH));
        $logo_src = 'data:image/png;base64,' . $logo_data;
        $html .= '<img src="' . $logo_src . '" alt="' . COMPANY_NAME . '" style="height: ' . LOGO_HEIGHT . 'px; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">';
    }
    
    $html .= '
                <div class="company-name">' . COMPANY_NAME . '</div>
                <div class="company-info">' . COMPANY_ADDRESS . '</div>
                <div class="company-info">' . COMPANY_EMAIL . ' | ' . COMPANY_PHONE . '</div>
            </div>
        </div>
        
        <div class="invoice-title">
            INVOICE
            <span class="status-badge">' . $invoice['status'] . '</span>
        </div>
        
        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="info-section">
                        <div class="info-label">FROM:</div>
                        <div class="info-value">
                            <strong>' . COMPANY_NAME . '</strong><br>
                            ' . COMPANY_ADDRESS . '<br>
                            ' . COMPANY_EMAIL . '<br>
                            ' . COMPANY_PHONE . '
                        </div>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <div class="info-section">
                        <div class="info-label">BILL TO:</div>
                        <div class="info-value">
                            <strong>' . htmlspecialchars($invoice['client_name']) . '</strong><br>
                            ' . htmlspecialchars($invoice['client_company']) . '<br>
                            ' . htmlspecialchars($invoice['client_email']) . '<br>
                            ' . htmlspecialchars($invoice['client_phone']) . '
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        
        <table style="width: 100%; margin-bottom: 20px;">
            <tr>
                <td style="width: 33%;">
                    <div class="info-label">Invoice Number:</div>
                    <div class="info-value">' . $invoice['invoice_number'] . '</div>
                </td>
                <td style="width: 33%;">
                    <div class="info-label">Invoice Date:</div>
                    <div class="info-value">' . formatDate($invoice['date']) . '</div>
                </td>
                <td style="width: 34%;">
                    <div class="info-label">Due Date:</div>
                    <div class="info-value">' . formatDate($invoice['due_date']) . '</div>
                </td>
            </tr>
        </table>
        
        <div class="due-date-box">
            <strong>Amount Due: ' . formatCurrency($invoice['total']) . '</strong><br>
            <span style="font-size: 9pt;">Payment Due By: ' . formatDate($invoice['due_date']) . '</span>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Item Name</th>
                    <th style="width: 15%; text-align: center;">Quantity</th>
                    <th style="width: 20%; text-align: right;">Unit Price</th>
                    <th style="width: 20%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>';
    
    while ($item = mysqli_fetch_assoc($items)) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($item['item_name']) . '</td>
                    <td style="text-align: center;">' . $item['qty'] . '</td>
                    <td style="text-align: right;">' . formatCurrency($item['unit_price']) . '</td>
                    <td style="text-align: right;">' . formatCurrency($item['total']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;">Subtotal:</td>
                    <td style="text-align: right;">' . formatCurrency($invoice['subtotal']) . '</td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right;">Tax:</td>
                    <td style="text-align: right;">' . formatCurrency($invoice['tax']) . '</td>
                </tr>';
    
    if ($invoice['discount'] > 0) {
        $html .= '
                <tr>
                    <td colspan="3" style="text-align: right;">Discount:</td>
                    <td style="text-align: right;">-' . formatCurrency($invoice['discount']) . '</td>
                </tr>';
    }
    
    $html .= '
                <tr class="total-row">
                    <td colspan="3" style="text-align: right;">AMOUNT DUE:</td>
                    <td style="text-align: right;">' . formatCurrency($invoice['total']) . '</td>
                </tr>
            </tfoot>
        </table>';
    
    if ($invoice['notes']) {
        $html .= '
        <div style="margin-top: 30px;">
            <div class="info-label">Notes:</div>
            <div class="info-value">' . nl2br(htmlspecialchars($invoice['notes'])) . '</div>
        </div>';
    }
    
    $html .= '
        <div class="footer">
            Thank you for your business!<br>
            Please make payment by the due date to avoid late fees.
        </div>
    </body>
    </html>';
    
    $mpdf->WriteHTML($html);
    
    // Output PDF
    $filename = $invoice['invoice_number'] . '.pdf';
    $mpdf->Output($filename, 'D'); // D = Download
    
} catch (\Mpdf\MpdfException $e) {
    die('PDF Generation Error: ' . $e->getMessage());
}
?>

