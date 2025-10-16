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
 */
function sendEmail($to, $subject, $body, $attachment = null) {
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
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(COMPANY_EMAIL, COMPANY_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        
        // Attachment
        if ($attachment && file_exists($attachment)) {
            $mail->addAttachment($attachment);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
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

