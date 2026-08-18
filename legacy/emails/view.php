<?php
/**
 * Code Catalyst Labs - View Email Thread
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get email
$query = "SELECT e.*, u.username as sent_by_name
          FROM emails e
          LEFT JOIN users u ON e.sent_by = u.id
          WHERE e.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$email = mysqli_fetch_assoc($result);

if (!$email) {
    $_SESSION['error'] = 'Email not found.';
    header('Location: list.php');
    exit();
}

// Mark as read if incoming and not read
if ($email['direction'] === 'incoming' && $email['status'] === 'received') {
    $update_query = "UPDATE emails SET status = 'read', read_at = NOW() WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, "i", $id);
    mysqli_stmt_execute($update_stmt);
    $email['status'] = 'read';
}

// Get thread (related emails based on message_id and in_reply_to)
$thread_emails = [];
if ($email['message_id'] || $email['in_reply_to']) {
    $thread_query = "SELECT e.*, u.username as sent_by_name
                     FROM emails e
                     LEFT JOIN users u ON e.sent_by = u.id
                     WHERE (e.message_id = ? OR e.in_reply_to = ? OR e.in_reply_to = ? OR e.message_id = ?)
                     AND e.id != ?
                     ORDER BY e.sent_at ASC";
    $thread_stmt = mysqli_prepare($conn, $thread_query);
    mysqli_stmt_bind_param($thread_stmt, "ssssi", 
        $email['in_reply_to'], 
        $email['message_id'], 
        $email['in_reply_to'],
        $email['in_reply_to'],
        $id
    );
    mysqli_stmt_execute($thread_stmt);
    $thread_result = mysqli_stmt_get_result($thread_stmt);
    while ($row = mysqli_fetch_assoc($thread_result)) {
        $thread_emails[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-envelope-open"></i> Email Details</h2>
        <div>
            <a href="list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <?php if ($email['direction'] === 'incoming'): ?>
            <a href="compose.php?reply_to=<?php echo $id; ?>" class="btn btn-primary">
                <i class="bi bi-reply"></i> Reply
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php displayAlert(); ?>
    
    <!-- Main Email -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-1"><?php echo htmlspecialchars($email['subject']); ?></h5>
                    <div class="text-muted small">
                        <?php if ($email['direction'] === 'outgoing'): ?>
                            <span class="badge bg-primary">Outgoing</span>
                        <?php else: ?>
                            <span class="badge bg-success">Incoming</span>
                        <?php endif; ?>
                        
                        <?php
                        $badge_class = match($email['status']) {
                            'sent' => 'bg-success',
                            'received' => 'bg-info',
                            'read' => 'bg-secondary',
                            'failed' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($email['status']); ?></span>
                        
                        <?php if ($email['reference_id']): ?>
                            <?php if ($email['reference_type'] === 'quotation'): ?>
                                <a href="../quotations/view.php?id=<?php echo $email['reference_id']; ?>" class="badge bg-info text-decoration-none">
                                    View Quotation
                                </a>
                            <?php elseif ($email['reference_type'] === 'invoice'): ?>
                                <a href="../invoices/view.php?id=<?php echo $email['reference_id']; ?>" class="badge bg-warning text-decoration-none">
                                    View Invoice
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-muted">
                        <?php echo date('M d, Y H:i:s', strtotime($email['sent_at'])); ?>
                    </small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>From:</strong><br>
                    <?php echo htmlspecialchars($email['from_name'] ? $email['from_name'] . ' <' . $email['from_email'] . '>' : $email['from_email']); ?>
                </div>
                <div class="col-md-6">
                    <strong>To:</strong><br>
                    <?php echo htmlspecialchars($email['to_email']); ?>
                </div>
            </div>
            
            <?php if ($email['cc_email']): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <strong>CC:</strong> <?php echo htmlspecialchars($email['cc_email']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($email['has_attachment']): ?>
            <div class="alert alert-info">
                <i class="bi bi-paperclip"></i> <strong>Attachment:</strong> <?php echo htmlspecialchars($email['attachment_name']); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($email['error_message']): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($email['error_message']); ?>
            </div>
            <?php endif; ?>
            
            <hr>
            
            <div class="email-body">
                <?php echo $email['body_html']; ?>
            </div>
        </div>
        <?php if ($email['sent_by_name']): ?>
        <div class="card-footer text-muted small">
            Sent by: <?php echo htmlspecialchars($email['sent_by_name']); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Thread/Conversation -->
    <?php if (!empty($thread_emails)): ?>
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Conversation Thread (<?php echo count($thread_emails); ?>)</h5>
        </div>
        <div class="card-body">
            <?php foreach ($thread_emails as $thread_email): ?>
            <div class="card mb-3 border-start border-3 <?php echo $thread_email['direction'] === 'outgoing' ? 'border-primary' : 'border-success'; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong>
                                <?php if ($thread_email['direction'] === 'outgoing'): ?>
                                    To: <?php echo htmlspecialchars($thread_email['to_email']); ?>
                                <?php else: ?>
                                    From: <?php echo htmlspecialchars($thread_email['from_name'] ?: $thread_email['from_email']); ?>
                                <?php endif; ?>
                            </strong>
                            <span class="badge <?php echo $thread_email['direction'] === 'outgoing' ? 'bg-primary' : 'bg-success'; ?> ms-2">
                                <?php echo ucfirst($thread_email['direction']); ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <?php echo date('M d, Y H:i', strtotime($thread_email['sent_at'])); ?>
                        </small>
                    </div>
                    <p class="mb-2"><strong>Subject:</strong> <?php echo htmlspecialchars($thread_email['subject']); ?></p>
                    <div class="email-body-preview">
                        <?php echo substr(strip_tags($thread_email['body_html']), 0, 200); ?>...
                        <a href="view.php?id=<?php echo $thread_email['id']; ?>">Read more</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.email-body {
    padding: 20px;
    background: #f8f9fa;
    border-radius: 5px;
}

.email-body img {
    max-width: 100%;
    height: auto;
}
</style>

<?php include '../includes/footer.php'; ?>

