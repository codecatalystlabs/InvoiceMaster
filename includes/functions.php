<?php
/**
 * Code Catalyst Labs - Helper Functions
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    
    if (is_array($role)) {
        return in_array($_SESSION['role'], $role);
    }
    
    return $_SESSION['role'] === $role;
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Clear any existing session data to ensure clean state
        $_SESSION = array();
        header('Location: ' . APP_URL . '/auth/login.php');
        exit();
    }
}

/**
 * Redirect if user doesn't have required role
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header('Location: ' . APP_URL . '/index.php');
        exit();
    }
}

/**
 * Sanitize input data
 */
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Generate quotation number
 */
function generateQuotationNumber($conn) {
    $prefix = 'QUO';
    $year = date('Y');
    
    $query = "SELECT quotation_number FROM quotations 
              WHERE quotation_number LIKE '$prefix-$year-%' 
              ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastNumber = (int)substr($row['quotation_number'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate invoice number
 */
function generateInvoiceNumber($conn) {
    $prefix = 'INV';
    $year = date('Y');
    
    $query = "SELECT invoice_number FROM invoices 
              WHERE invoice_number LIKE '$prefix-$year-%' 
              ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastNumber = (int)substr($row['invoice_number'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

/**
 * Log audit trail
 */
function logAudit($conn, $action, $entity_type, $entity_id = null, $details = '') {
    if (!isLoggedIn()) return;
    
    $user_id = $_SESSION['user_id'];
    $action = mysqli_real_escape_string($conn, $action);
    $entity_type = mysqli_real_escape_string($conn, $entity_type);
    $details = mysqli_real_escape_string($conn, $details);
    
    $query = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "issis", $user_id, $action, $entity_type, $entity_id, $details);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Format currency
 */
/**
 * Resolve the currency symbol defensively. Some hosting PHP environments
 * already define a global CURRENCY_SYMBOL (e.g. as int 262145), so we use an
 * APP_-prefixed constant and still guard against any non-string value.
 */
function currencySymbol() {
    if (defined('APP_CURRENCY_SYMBOL') && is_string(APP_CURRENCY_SYMBOL) && !is_numeric(APP_CURRENCY_SYMBOL) && trim(APP_CURRENCY_SYMBOL) !== '') {
        return APP_CURRENCY_SYMBOL;
    }
    if (defined('APP_CURRENCY_CODE') && is_string(APP_CURRENCY_CODE) && !is_numeric(APP_CURRENCY_CODE) && trim(APP_CURRENCY_CODE) !== '') {
        return APP_CURRENCY_CODE;
    }
    return 'UGX';
}

function formatCurrency($amount) {
    return currencySymbol() . ' ' . number_format((float)$amount, 0);
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date(DISPLAY_DATE_FORMAT, strtotime($date));
}

/**
 * Get client name by ID
 */
function getClientName($conn, $client_id) {
    $query = "SELECT name FROM clients WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['name'];
    }
    
    return 'Unknown Client';
}

/**
 * Get all clients
 */
function getClients($conn) {
    $query = "SELECT * FROM clients ORDER BY name ASC";
    $result = mysqli_query($conn, $query);
    return $result;
}

/**
 * Get status badge class
 */
function getStatusBadge($status) {
    $badges = [
        'Draft' => 'secondary',
        'Sent' => 'info',
        'Accepted' => 'success',
        'Rejected' => 'danger',
        'Converted' => 'primary',
        'Unpaid' => 'warning',
        'Partially Paid' => 'info',
        'Paid' => 'success',
        'Overdue' => 'danger',
        'Cancelled' => 'dark',
        'Active' => 'success',
        'Inactive' => 'secondary'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : 'secondary';
}

/**
 * Display alert message
 */
function displayAlert() {
    $types = ['success', 'error', 'warning', 'info'];
    
    foreach ($types as $type) {
        if (isset($_SESSION[$type])) {
            $alertClass = $type === 'error' ? 'danger' : $type;
            echo '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo $_SESSION[$type];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
            unset($_SESSION[$type]);
        }
    }
}

/**
 * Calculate pagination
 */
function getPagination($total_records, $page, $per_page) {
    $total_pages = ceil($total_records / $per_page);
    $offset = ($page - 1) * $per_page;
    
    return [
        'total_pages' => $total_pages,
        'offset' => $offset,
        'current_page' => $page
    ];
}

/**
 * Display pagination links
 */
function displayPagination($total_pages, $current_page, $base_url) {
    if ($total_pages <= 1) return;
    
    echo '<nav aria-label="Page navigation">';
    echo '<ul class="pagination justify-content-center">';
    
    // Previous button
    $disabled = $current_page <= 1 ? 'disabled' : '';
    $prev_page = $current_page - 1;
    echo '<li class="page-item ' . $disabled . '">';
    echo '<a class="page-link" href="' . $base_url . '&page=' . $prev_page . '">Previous</a>';
    echo '</li>';
    
    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = $i === $current_page ? 'active' : '';
        echo '<li class="page-item ' . $active . '">';
        echo '<a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a>';
        echo '</li>';
    }
    
    // Next button
    $disabled = $current_page >= $total_pages ? 'disabled' : '';
    $next_page = $current_page + 1;
    echo '<li class="page-item ' . $disabled . '">';
    echo '<a class="page-link" href="' . $base_url . '&page=' . $next_page . '">Next</a>';
    echo '</li>';
    
    echo '</ul>';
    echo '</nav>';
}

/**
 * Generate Quotation PDF and save to file
 * @param array $quotation - Quotation data
 * @param array $items - Quotation items
 * @param string $output_file - Path to save the PDF file
 * @return bool - True if successful, false otherwise
 */
function generateQuotationPDF($quotation, $items, $output_file) {
    try {
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
        ]);
        
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
                .quotation-title { font-size: 18pt; color: #333; margin: 20px 0; }
                .info-section { margin-bottom: 20px; }
                .info-label { font-weight: bold; color: #666; font-size: 9pt; }
                .info-value { font-size: 10pt; }
                .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .items-table th { background-color: #0d6efd; color: white; padding: 10px; text-align: left; }
                .items-table td { padding: 8px; border-bottom: 1px solid #ddd; }
                .items-table tfoot td { font-weight: bold; padding: 10px; }
                .total-row { background-color: #e7f1ff; font-size: 12pt; }
                .status-badge { background-color: #28a745; color: white; padding: 5px 15px; border-radius: 5px; display: inline-block; }
                .footer { margin-top: 40px; text-align: center; font-size: 9pt; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-logo">
                    <div class="company-name">' . COMPANY_NAME . '</div>
                    <div class="company-info">' . COMPANY_ADDRESS . '</div>
                    <div class="company-info">' . COMPANY_EMAIL . ' | ' . COMPANY_PHONE . '</div>
                </div>
            </div>
            
            <div class="quotation-title">QUOTATION</div>
            
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
                            <div class="info-label">TO:</div>
                            <div class="info-value">
                                <strong>' . htmlspecialchars($quotation['client_name']) . '</strong><br>';
        
        if (!empty($quotation['client_company'])) {
            $html .= htmlspecialchars($quotation['client_company']) . '<br>';
        }
        if (!empty($quotation['client_email'])) {
            $html .= htmlspecialchars($quotation['client_email']) . '<br>';
        }
        if (!empty($quotation['client_phone'])) {
            $html .= htmlspecialchars($quotation['client_phone']);
        }
        
        $html .= '
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            
            <table style="width: 100%; margin-bottom: 20px;">
                <tr>
                    <td style="width: 50%;">
                        <div class="info-label">Quotation Number:</div>
                        <div class="info-value">' . $quotation['quotation_number'] . '</div>
                    </td>
                    <td style="width: 50%;">
                        <div class="info-label">Date:</div>
                        <div class="info-value">' . formatDate($quotation['date']) . '</div>
                    </td>
                </tr>
            </table>
            
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
        
        foreach ($items as $item) {
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
                        <td style="text-align: right;">' . formatCurrency($quotation['subtotal']) . '</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;">Tax:</td>
                        <td style="text-align: right;">' . formatCurrency($quotation['tax']) . '</td>
                    </tr>';
        
        if ($quotation['discount'] > 0) {
            $html .= '
                    <tr>
                        <td colspan="3" style="text-align: right;">Discount:</td>
                        <td style="text-align: right;">-' . formatCurrency($quotation['discount']) . '</td>
                    </tr>';
        }
        
        $html .= '
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right;">' . formatCurrency($quotation['total']) . '</td>
                    </tr>
                </tfoot>
            </table>';
        
        if (!empty($quotation['notes'])) {
            $html .= '
            <div style="margin-top: 30px;">
                <div class="info-label">Notes:</div>
                <div class="info-value">' . nl2br(htmlspecialchars($quotation['notes'])) . '</div>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                Thank you for your business!<br>
                This quotation is valid for 30 days from the date of issue.
            </div>
        </body>
        </html>';
        
        $mpdf->WriteHTML($html);
        $mpdf->Output($output_file, \Mpdf\Output\Destination::FILE);
        
        return true;
    } catch (\Mpdf\MpdfException $e) {
        error_log('PDF Generation Error: ' . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log('PDF Generation Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate Invoice PDF and save to file
 * @param array $invoice - Invoice data
 * @param array $items - Invoice items
 * @param string $output_file - Path to save the PDF file
 * @return bool - True if successful, false otherwise
 */
function generateInvoicePDF($invoice, $items, $output_file) {
    try {
        $mpdf = new \Mpdf\Mpdf([
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
        ]);
        
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
                .invoice-title { font-size: 18pt; color: #333; margin: 20px 0; }
                .info-section { margin-bottom: 20px; }
                .info-label { font-weight: bold; color: #666; font-size: 9pt; }
                .info-value { font-size: 10pt; }
                .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .items-table th { background-color: #0d6efd; color: white; padding: 10px; text-align: left; }
                .items-table td { padding: 8px; border-bottom: 1px solid #ddd; }
                .items-table tfoot td { font-weight: bold; padding: 10px; }
                .total-row { background-color: #e7f1ff; font-size: 12pt; }
                .status-badge { padding: 5px 15px; border-radius: 5px; display: inline-block; font-size: 9pt; }
                .status-paid { background-color: #28a745; color: white; }
                .status-unpaid { background-color: #dc3545; color: white; }
                .status-partial { background-color: #ffc107; color: #000; }
                .footer { margin-top: 40px; text-align: center; font-size: 9pt; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
                .payment-notice { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-logo">
                    <div class="company-name">' . COMPANY_NAME . '</div>
                    <div class="company-info">' . COMPANY_ADDRESS . '</div>
                    <div class="company-info">' . COMPANY_EMAIL . ' | ' . COMPANY_PHONE . '</div>
                </div>
            </div>
            
            <div class="invoice-title">INVOICE</div>
            
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
                            <div class="info-label">TO:</div>
                            <div class="info-value">
                                <strong>' . htmlspecialchars($invoice['client_name']) . '</strong><br>';
        
        if (!empty($invoice['client_company'])) {
            $html .= htmlspecialchars($invoice['client_company']) . '<br>';
        }
        if (!empty($invoice['client_email'])) {
            $html .= htmlspecialchars($invoice['client_email']) . '<br>';
        }
        if (!empty($invoice['client_phone'])) {
            $html .= htmlspecialchars($invoice['client_phone']);
        }
        
        $html .= '
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
                        <div class="info-label">Date:</div>
                        <div class="info-value">' . formatDate($invoice['date']) . '</div>
                    </td>
                    <td style="width: 33%;">
                        <div class="info-label">Due Date:</div>
                        <div class="info-value">' . formatDate($invoice['due_date']) . '</div>
                    </td>
                </tr>
            </table>
            
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
        
        foreach ($items as $item) {
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
                        <td colspan="3" style="text-align: right;">TOTAL:</td>
                        <td style="text-align: right;">' . formatCurrency($invoice['total']) . '</td>
                    </tr>
                </tfoot>
            </table>';
        
        // Add payment notice if unpaid or partially paid
        if ($invoice['status'] !== 'Paid') {
            $html .= '
            <div class="payment-notice">
                <strong>Payment Due:</strong> ' . formatCurrency($invoice['total']) . ' by ' . formatDate($invoice['due_date']) . '<br>
                Please ensure payment is received by the due date to avoid any late fees.
            </div>';
        }
        
        if (!empty($invoice['notes'])) {
            $html .= '
            <div style="margin-top: 30px;">
                <div class="info-label">Notes:</div>
                <div class="info-value">' . nl2br(htmlspecialchars($invoice['notes'])) . '</div>
            </div>';
        }
        
        $html .= '
            <div class="footer">
                Thank you for your business!<br>
                For any questions about this invoice, please contact us.
            </div>
        </body>
        </html>';
        
        $mpdf->WriteHTML($html);
        $mpdf->Output($output_file, \Mpdf\Output\Destination::FILE);
        
        return true;
    } catch (\Mpdf\MpdfException $e) {
        error_log('PDF Generation Error: ' . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log('PDF Generation Error: ' . $e->getMessage());
        return false;
    }
}