<?php
/**
 * Code Catalyst Labs - Compose Email
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/mailer.php';

requireLogin();

$reply_to_id = isset($_GET['reply_to']) ? (int)$_GET['reply_to'] : 0;
$reply_email = null;

// If replying, get the original email
if ($reply_to_id) {
    $query = "SELECT * FROM emails WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $reply_to_id);
    mysqli_stmt_execute($stmt);
    $reply_email = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = clean($_POST['to']);
    $cc = isset($_POST['cc']) ? clean($_POST['cc']) : '';
    $bcc = isset($_POST['bcc']) ? clean($_POST['bcc']) : '';
    $subject = clean($_POST['subject']);
    $body = $_POST['body']; // Don't clean HTML
    
    // Process CC
    $cc_array = [];
    if (!empty($cc)) {
        $cc_list = explode(',', $cc);
        foreach ($cc_list as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $cc_array[] = $email;
            }
        }
    }
    
    // Process BCC
    $bcc_array = [];
    if (!empty($bcc)) {
        $bcc_list = explode(',', $bcc);
        foreach ($bcc_list as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $bcc_array[] = $email;
            }
        }
    }
    
    // Validate
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Please enter a valid recipient email address.';
    } elseif (empty($subject)) {
        $_SESSION['error'] = 'Please enter a subject.';
    } elseif (empty($body)) {
        $_SESSION['error'] = 'Please enter an email body.';
    } else {
        // Send email
        $in_reply_to = $reply_email ? $reply_email['message_id'] : null;
        $result = sendEmail($to, $subject, $body, null, $cc_array, $bcc_array, 'general', null, $in_reply_to);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Email sent successfully!';
            header('Location: list.php');
            exit();
        } else {
            $_SESSION['error'] = 'Failed to send email: ' . ($result['error'] ?? 'Unknown error');
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-pencil"></i> 
            <?php echo $reply_email ? 'Reply to Email' : 'Compose Email'; ?>
        </h2>
        <a href="list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
    
    <?php displayAlert(); ?>
    
    <?php if ($reply_email): ?>
    <div class="card mb-4 bg-light">
        <div class="card-body">
            <h6><i class="bi bi-reply"></i> Replying to:</h6>
            <p class="mb-1"><strong>From:</strong> <?php echo htmlspecialchars($reply_email['from_email']); ?></p>
            <p class="mb-1"><strong>Subject:</strong> <?php echo htmlspecialchars($reply_email['subject']); ?></p>
            <p class="mb-0"><small class="text-muted"><?php echo date('M d, Y H:i', strtotime($reply_email['sent_at'])); ?></small></p>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="to" class="form-label">To: <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="to" name="to" 
                           value="<?php echo $reply_email ? htmlspecialchars($reply_email['from_email']) : ''; ?>" required>
                    <small class="form-text text-muted">Primary recipient email address</small>
                </div>
                
                <div class="mb-3">
                    <label for="cc" class="form-label">CC:</label>
                    <input type="text" class="form-control" id="cc" name="cc" 
                           placeholder="email1@example.com, email2@example.com">
                    <small class="form-text text-muted">Separate multiple emails with commas</small>
                </div>
                
                <div class="mb-3">
                    <label for="bcc" class="form-label">BCC:</label>
                    <input type="text" class="form-control" id="bcc" name="bcc" 
                           placeholder="email1@example.com, email2@example.com">
                    <small class="form-text text-muted">Separate multiple emails with commas (hidden from other recipients)</small>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="subject" name="subject" 
                           value="<?php echo $reply_email ? 'Re: ' . htmlspecialchars($reply_email['subject']) : ''; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="body" class="form-label">Message: <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="body" name="body" rows="15" required></textarea>
                    <small class="form-text text-muted">HTML is supported</small>
                </div>
                
                <?php if ($reply_email): ?>
                <div class="mb-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Original Message:</h6>
                            <hr>
                            <div class="small"><?php echo $reply_email['body_html']; ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Send Email
                    </button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Simple HTML editor functionality
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('body');
    
    // Auto-grow textarea
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Add basic formatting buttons
    const toolbar = document.createElement('div');
    toolbar.className = 'btn-toolbar mb-2';
    toolbar.innerHTML = `
        <div class="btn-group btn-group-sm me-2" role="group">
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('bold')" title="Bold">
                <i class="bi bi-type-bold"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('italic')" title="Italic">
                <i class="bi bi-type-italic"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('underline')" title="Underline">
                <i class="bi bi-type-underline"></i>
            </button>
        </div>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary" onclick="insertHtml('<br>')" title="Line Break">
                <i class="bi bi-arrow-return-left"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="insertHtml('<p></p>')" title="Paragraph">
                <i class="bi bi-paragraph"></i>
            </button>
        </div>
    `;
    textarea.parentNode.insertBefore(toolbar, textarea);
});

function formatText(format) {
    const textarea = document.getElementById('body');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    
    let formattedText;
    switch(format) {
        case 'bold':
            formattedText = '<strong>' + selectedText + '</strong>';
            break;
        case 'italic':
            formattedText = '<em>' + selectedText + '</em>';
            break;
        case 'underline':
            formattedText = '<u>' + selectedText + '</u>';
            break;
    }
    
    textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start, start + formattedText.length);
}

function insertHtml(html) {
    const textarea = document.getElementById('body');
    const start = textarea.selectionStart;
    textarea.value = textarea.value.substring(0, start) + html + textarea.value.substring(start);
    textarea.focus();
}
</script>

<?php include '../includes/footer.php'; ?>

