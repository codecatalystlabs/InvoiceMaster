<?php
/**
 * Code Catalyst Labs - View Invoice
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$page_title = 'View Invoice';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get invoice
$query = "SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone, c.company as client_company,
          q.quotation_number
          FROM invoices i 
          LEFT JOIN clients c ON i.client_id = c.id 
          LEFT JOIN quotations q ON i.quotation_id = q.id
          WHERE i.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$invoice = mysqli_fetch_assoc($result);

if (!$invoice) {
    $_SESSION['error'] = 'Invoice not found.';
    header('Location: list.php');
    exit();
}

// Get items
$query = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-receipt"></i> Invoice - <?php echo $invoice['invoice_number']; ?></h2>
        <div>
            <a href="list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Invoice Details</h5>
                    <span class="badge bg-<?php echo getStatusBadge($invoice['status']); ?> fs-6">
                        <?php echo $invoice['status']; ?>
                    </span>
                </div>
                <div class="card-body">
                    <!-- Company & Client Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">FROM:</h6>
                            <h5><?php echo COMPANY_NAME; ?></h5>
                            <p class="mb-0"><?php echo COMPANY_ADDRESS; ?></p>
                            <p class="mb-0"><?php echo COMPANY_EMAIL; ?></p>
                            <p class="mb-0"><?php echo COMPANY_PHONE; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">BILL TO:</h6>
                            <h5><?php echo htmlspecialchars($invoice['client_name']); ?></h5>
                            <p class="mb-0"><?php echo htmlspecialchars($invoice['client_company']); ?></p>
                            <p class="mb-0"><?php echo htmlspecialchars($invoice['client_email']); ?></p>
                            <p class="mb-0"><?php echo htmlspecialchars($invoice['client_phone']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p><strong>Invoice Number:</strong><br><?php echo $invoice['invoice_number']; ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Invoice Date:</strong><br><?php echo formatDate($invoice['date']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Due Date:</strong><br>
                                <span class="<?php echo (strtotime($invoice['due_date']) < time() && $invoice['status'] === 'Unpaid') ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo formatDate($invoice['due_date']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($invoice['quotation_number']): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> This invoice was converted from quotation 
                        <strong><?php echo $invoice['quotation_number']; ?></strong>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Items Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Item Name</th>
                                    <th width="15%">Quantity</th>
                                    <th width="20%">Unit Price</th>
                                    <th width="20%">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo $item['qty']; ?></td>
                                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                    <td><?php echo formatCurrency($item['total']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td><?php echo formatCurrency($invoice['subtotal']); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tax:</strong></td>
                                    <td><?php echo formatCurrency($invoice['tax']); ?></td>
                                </tr>
                                <?php if ($invoice['discount'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Discount:</strong></td>
                                    <td>-<?php echo formatCurrency($invoice['discount']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr class="table-primary">
                                    <td colspan="3" class="text-end"><strong>Amount Due:</strong></td>
                                    <td><strong><?php echo formatCurrency($invoice['total']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if ($invoice['notes']): ?>
                    <div class="mb-3">
                        <h6>Notes:</h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Actions Card -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if (hasRole(['Admin', 'Finance'])): ?>
                        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit Invoice
                        </a>
                        <?php endif; ?>
                        
                        <a href="../pdf/generate_invoice.php?id=<?php echo $id; ?>" class="btn btn-danger" target="_blank">
                            <i class="bi bi-file-pdf"></i> Download PDF
                        </a>
                        
                        <?php if (hasRole(['Admin', 'Finance', 'Sales'])): ?>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#emailModal">
                            <i class="bi bi-envelope"></i> Send Email
                        </button>
                        
                        <?php if ($invoice['status'] !== 'Paid'): ?>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                            <i class="bi bi-check-circle"></i> Mark as Paid
                        </button>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Created:</strong><br><?php echo formatDate($invoice['created_at']); ?></p>
                    <p class="mb-2"><strong>Last Updated:</strong><br><?php echo formatDate($invoice['updated_at']); ?></p>
                    
                    <?php if (strtotime($invoice['due_date']) < time() && $invoice['status'] === 'Unpaid'): ?>
                    <hr>
                    <div class="alert alert-danger mb-0 small">
                        <i class="bi bi-exclamation-triangle"></i> This invoice is overdue!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="send_email.php">
                <div class="modal-header">
                    <h5 class="modal-title">Send Invoice Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="invoice_id" value="<?php echo $id; ?>">
                    
                    <div class="mb-3">
                        <label for="email_to" class="form-label">To: <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email_to" name="email_to" 
                               value="<?php echo htmlspecialchars($invoice['client_email']); ?>" required>
                        <small class="form-text text-muted">Primary recipient email address</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email_cc" class="form-label">CC: <span class="text-muted">(Optional)</span></label>
                        <input type="text" class="form-control" id="email_cc" name="email_cc" 
                               placeholder="email1@example.com, email2@example.com">
                        <small class="form-text text-muted">Separate multiple emails with commas</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email_bcc" class="form-label">BCC: <span class="text-muted">(Optional)</span></label>
                        <input type="text" class="form-control" id="email_bcc" name="email_bcc" 
                               placeholder="email1@example.com, email2@example.com">
                        <small class="form-text text-muted">Separate multiple emails with commas (hidden from other recipients)</small>
                    </div>
                    
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle"></i> The invoice PDF will be attached to the email.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send"></i> Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="update_status.php">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Mark as Paid</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="invoice_id" value="<?php echo $id; ?>">
                    <input type="hidden" name="status" value="Paid">
                    
                    <p>Are you sure you want to mark this invoice as <strong>Paid</strong>?</p>
                    <p><strong>Amount:</strong> <?php echo formatCurrency($invoice['total']); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

