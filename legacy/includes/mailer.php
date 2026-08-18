<?php
/**
 * Code Catalyst Labs - Email Service
 * PHPMailer Integration
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Send email using PHPMailer
 * @param string $to - Primary recipient email
 * @param string $subject - Email subject
 * @param string $body - HTML email body
 * @param string|null $attachment - Path to attachment file
 * @param array $cc - Array of CC email addresses (optional)
 * @param array $bcc - Array of BCC email addresses (optional)
 * @param string|null $reference_type - Type: 'quotation', 'invoice', 'general'
 * @param int|null $reference_id - ID of related quotation/invoice
 * @param string|null $in_reply_to - Message-ID of email being replied to
 * @return array - ['success' => bool, 'message_id' => string|null, 'error' => string|null]
 */
function sendEmail($to, $subject, $body, $attachment = null, $cc = [], $bcc = [], $reference_type = 'general', $reference_id = null, $in_reply_to = null) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Enable verbose debug output (comment out in production)
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'error_log';
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(COMPANY_EMAIL, COMPANY_NAME);
        
        // Add CC recipients
        if (!empty($cc)) {
            foreach ($cc as $cc_email) {
                $cc_email = trim($cc_email);
                if (filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($cc_email);
                }
            }
        }
        
        // Add BCC recipients
        if (!empty($bcc)) {
            foreach ($bcc as $bcc_email) {
                $bcc_email = trim($bcc_email);
                if (filter_var($bcc_email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addBCC($bcc_email);
                }
            }
        }
        
        // Content - Set these BEFORE adding attachments
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        // Attachment - Add AFTER setting body
        if ($attachment && file_exists($attachment)) {
            $mail->addAttachment($attachment, basename($attachment));
        }
        
        $mail->send();
        
        // Get Message-ID after sending
        $message_id = $mail->getLastMessageID();
        
        // Log email to database
        logEmailToDatabase([
            'message_id' => $message_id,
            'in_reply_to' => $in_reply_to,
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'direction' => 'outgoing',
            'from_email' => SMTP_FROM_EMAIL,
            'from_name' => SMTP_FROM_NAME,
            'to_email' => $to,
            'cc_email' => !empty($cc) ? implode(', ', $cc) : null,
            'bcc_email' => !empty($bcc) ? implode(', ', $bcc) : null,
            'subject' => $subject,
            'body_html' => $body,
            'body_text' => strip_tags($body),
            'has_attachment' => !empty($attachment),
            'attachment_name' => $attachment ? basename($attachment) : null,
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['success' => true, 'message_id' => $message_id, 'error' => null];
    } catch (Exception $e) {
        // Log detailed error information
        error_log("Email Error: {$mail->ErrorInfo}");
        error_log("PHPMailer Exception: " . $e->getMessage());
        
        // Store error in session for display to user
        if (isset($_SESSION)) {
            $_SESSION['email_debug'] = $mail->ErrorInfo;
        }
        
        // Log failed email to database
        logEmailToDatabase([
            'reference_type' => $reference_type,
            'reference_id' => $reference_id,
            'direction' => 'outgoing',
            'from_email' => SMTP_FROM_EMAIL,
            'from_name' => SMTP_FROM_NAME,
            'to_email' => $to,
            'cc_email' => !empty($cc) ? implode(', ', $cc) : null,
            'bcc_email' => !empty($bcc) ? implode(', ', $bcc) : null,
            'subject' => $subject,
            'body_html' => $body,
            'body_text' => strip_tags($body),
            'has_attachment' => !empty($attachment),
            'attachment_name' => $attachment ? basename($attachment) : null,
            'status' => 'failed',
            'error_message' => $mail->ErrorInfo,
            'sent_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['success' => false, 'message_id' => null, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Log email to database
 * @param array $data - Email data
 * @return int|false - Email ID or false on failure
 */
function logEmailToDatabase($data) {
    global $conn;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    $query = "INSERT INTO emails (
        message_id, in_reply_to, reference_type, reference_id, direction,
        from_email, from_name, to_email, cc_email, bcc_email,
        subject, body_html, body_text, has_attachment, attachment_name,
        status, error_message, sent_by, sent_at, received_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Failed to prepare email log statement: " . mysqli_error($conn));
        return false;
    }
    
    // Prepare nullable values as variables (required for bind_param reference)
    $error_message = isset($data['error_message']) ? $data['error_message'] : null;
    $received_at = isset($data['received_at']) ? $data['received_at'] : null;
    
    mysqli_stmt_bind_param($stmt, "ssssssssssssssssisss",
        $data['message_id'],
        $data['in_reply_to'],
        $data['reference_type'],
        $data['reference_id'],
        $data['direction'],
        $data['from_email'],
        $data['from_name'],
        $data['to_email'],
        $data['cc_email'],
        $data['bcc_email'],
        $data['subject'],
        $data['body_html'],
        $data['body_text'],
        $data['has_attachment'],
        $data['attachment_name'],
        $data['status'],
        $error_message,
        $user_id,
        $data['sent_at'],
        $received_at
    );
    
    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    } else {
        error_log("Failed to log email: " . mysqli_stmt_error($stmt));
        return false;
    }
}

/**
 * Generate quotation email body
 */
function getQuotationEmailBody($quotation_number, $client_name, $total) {
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0d6efd; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .button { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . COMPANY_NAME . '</h1>
            </div>
            <div class="content">
                <h2>Quotation: ' . htmlspecialchars($quotation_number) . '</h2>
                <p>Dear ' . htmlspecialchars($client_name) . ',</p>
                <p>Thank you for your interest in our services. Please find attached the quotation for your review.</p>
                <p><strong>Total Amount: ' . formatCurrency($total) . '</strong></p>
                <p>If you have any questions or would like to proceed, please don\'t hesitate to contact us.</p>
                <p>Best regards,<br>' . COMPANY_NAME . '</p>
            </div>
            <div class="footer">
                <p>' . COMPANY_EMAIL . ' | ' . COMPANY_PHONE . '</p>
                <p>' . COMPANY_ADDRESS . '</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $body;
}

/**
 * Generate invoice email body
 */
function getInvoiceEmailBody($invoice_number, $client_name, $total, $due_date) {
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0d6efd; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .highlight { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . COMPANY_NAME . '</h1>
            </div>
            <div class="content">
                <h2>Invoice: ' . htmlspecialchars($invoice_number) . '</h2>
                <p>Dear ' . htmlspecialchars($client_name) . ',</p>
                <p>Thank you for your business. Please find attached your invoice.</p>
                <div class="highlight">
                    <p><strong>Amount Due: ' . formatCurrency($total) . '</strong></p>
                    <p><strong>Due Date: ' . formatDate($due_date) . '</strong></p>
                </div>
                <p>Please ensure payment is received by the due date to avoid any late fees.</p>
                <p>Best regards,<br>' . COMPANY_NAME . '</p>
            </div>
            <div class="footer">
                <p>' . COMPANY_EMAIL . ' | ' . COMPANY_PHONE . '</p>
                <p>' . COMPANY_ADDRESS . '</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $body;
}
?>

