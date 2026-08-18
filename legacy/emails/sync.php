<?php
/**
 * Code Catalyst Labs - Sync Emails via IMAP
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin']);

if (!IMAP_ENABLED) {
    $_SESSION['error'] = 'IMAP is not enabled. Please enable it in config.php';
    header('Location: list.php');
    exit();
}

// Check if IMAP extension is loaded
if (!function_exists('imap_open')) {
    $_SESSION['error'] = 'PHP IMAP extension is not installed. Please install it to use this feature.';
    header('Location: list.php');
    exit();
}

$synced_count = 0;
$errors = [];

try {
    // Build IMAP connection string
    $imap_host = '{' . IMAP_HOST . ':' . IMAP_PORT . '/imap';
    if (IMAP_SSL) {
        $imap_host .= '/ssl';
    }
    $imap_host .= '}' . IMAP_FOLDER;
    
    // Connect to IMAP
    $inbox = @imap_open($imap_host, IMAP_USERNAME, IMAP_PASSWORD);
    
    if (!$inbox) {
        throw new Exception('Cannot connect to IMAP server: ' . imap_last_error());
    }
    
    // Get emails since last sync (or last 7 days if first sync)
    $since_date = date('d F Y', strtotime('-7 days'));
    $emails = imap_search($inbox, 'SINCE "' . $since_date . '"');
    
    if ($emails) {
        // Sort emails (oldest first)
        rsort($emails);
        
        foreach ($emails as $email_number) {
            try {
                // Get email header
                $header = imap_headerinfo($inbox, $email_number);
                
                // Get message ID
                $message_id = isset($header->message_id) ? trim($header->message_id, '<>') : null;
                
                // Check if we already have this email
                if ($message_id) {
                    $check_query = "SELECT id FROM emails WHERE message_id = ?";
                    $check_stmt = mysqli_prepare($conn, $check_query);
                    mysqli_stmt_bind_param($check_stmt, "s", $message_id);
                    mysqli_stmt_execute($check_stmt);
                    if (mysqli_stmt_get_result($check_stmt)->num_rows > 0) {
                        continue; // Already synced
                    }
                }
                
                // Get email structure
                $structure = imap_fetchstructure($inbox, $email_number);
                
                // Get email body
                $body_html = '';
                $body_text = '';
                
                if (isset($structure->parts) && count($structure->parts)) {
                    foreach ($structure->parts as $part_num => $part) {
                        if ($part->subtype === 'HTML') {
                            $body_html = imap_fetchbody($inbox, $email_number, $part_num + 1);
                            if ($part->encoding == 3) {
                                $body_html = base64_decode($body_html);
                            } elseif ($part->encoding == 4) {
                                $body_html = quoted_printable_decode($body_html);
                            }
                        } elseif ($part->subtype === 'PLAIN') {
                            $body_text = imap_fetchbody($inbox, $email_number, $part_num + 1);
                            if ($part->encoding == 3) {
                                $body_text = base64_decode($body_text);
                            } elseif ($part->encoding == 4) {
                                $body_text = quoted_printable_decode($body_text);
                            }
                        }
                    }
                } else {
                    $body_text = imap_body($inbox, $email_number);
                }
                
                // If no HTML body, create one from text
                if (empty($body_html) && !empty($body_text)) {
                    $body_html = nl2br(htmlspecialchars($body_text));
                }
                
                // Get sender info
                $from_email = isset($header->from[0]) ? $header->from[0]->mailbox . '@' . $header->from[0]->host : '';
                $from_name = isset($header->from[0]->personal) ? imap_utf8($header->from[0]->personal) : '';
                
                // Get recipient info
                $to_email = isset($header->to[0]) ? $header->to[0]->mailbox . '@' . $header->to[0]->host : '';
                
                // Get CC if any
                $cc_email = '';
                if (isset($header->cc) && is_array($header->cc)) {
                    $cc_list = [];
                    foreach ($header->cc as $cc) {
                        $cc_list[] = $cc->mailbox . '@' . $cc->host;
                    }
                    $cc_email = implode(', ', $cc_list);
                }
                
                // Get subject
                $subject = isset($header->subject) ? imap_utf8($header->subject) : '(No Subject)';
                
                // Get in-reply-to header
                $in_reply_to = isset($header->in_reply_to) ? trim($header->in_reply_to, '<>') : null;
                
                // Get date
                $date = isset($header->date) ? date('Y-m-d H:i:s', strtotime($header->date)) : date('Y-m-d H:i:s');
                
                // Try to determine if this is a reply to one of our sent emails
                $reference_type = 'general';
                $reference_id = null;
                
                if ($in_reply_to) {
                    $ref_query = "SELECT reference_type, reference_id FROM emails WHERE message_id = ?";
                    $ref_stmt = mysqli_prepare($conn, $ref_query);
                    mysqli_stmt_bind_param($ref_stmt, "s", $in_reply_to);
                    mysqli_stmt_execute($ref_stmt);
                    $ref_result = mysqli_stmt_get_result($ref_stmt);
                    if ($ref_row = mysqli_fetch_assoc($ref_result)) {
                        $reference_type = $ref_row['reference_type'];
                        $reference_id = $ref_row['reference_id'];
                    }
                }
                
                // Check for attachments
                $has_attachment = 0;
                if (isset($structure->parts)) {
                    foreach ($structure->parts as $part) {
                        if (isset($part->disposition) && strtoupper($part->disposition) === 'ATTACHMENT') {
                            $has_attachment = 1;
                            break;
                        }
                    }
                }
                
                // Insert into database
                $insert_query = "INSERT INTO emails (
                    message_id, in_reply_to, reference_type, reference_id, direction,
                    from_email, from_name, to_email, cc_email,
                    subject, body_html, body_text, has_attachment,
                    status, sent_at, received_at
                ) VALUES (?, ?, ?, ?, 'incoming', ?, ?, ?, ?, ?, ?, ?, ?, 'received', ?, NOW())";
                
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "sssssssssssss",
                    $message_id,
                    $in_reply_to,
                    $reference_type,
                    $reference_id,
                    $from_email,
                    $from_name,
                    $to_email,
                    $cc_email,
                    $subject,
                    $body_html,
                    $body_text,
                    $has_attachment,
                    $date
                );
                
                if (mysqli_stmt_execute($stmt)) {
                    $synced_count++;
                } else {
                    $errors[] = "Failed to save email: " . mysqli_stmt_error($stmt);
                }
                
            } catch (Exception $e) {
                $errors[] = "Error processing email #$email_number: " . $e->getMessage();
            }
        }
    }
    
    imap_close($inbox);
    
    if ($synced_count > 0) {
        $_SESSION['success'] = "Successfully synced $synced_count new email(s)!";
    } else {
        $_SESSION['info'] = 'No new emails to sync.';
    }
    
    if (!empty($errors)) {
        $_SESSION['warning'] = 'Some errors occurred: ' . implode(', ', array_slice($errors, 0, 3));
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Sync failed: ' . $e->getMessage();
    error_log('IMAP Sync Error: ' . $e->getMessage());
}

header('Location: list.php');
exit();
?>

