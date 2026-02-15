<?php
/**
 * Code Catalyst Labs - View Expense
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$page_title = 'View Expense';

// Get expense ID
$expense_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($expense_id === 0) {
    $_SESSION['error'] = "Invalid expense ID";
    header('Location: list.php');
    exit();
}

// Get expense details
$query = "SELECT e.*, c.account_code, c.account_name, u.username as creator 
          FROM expenses e 
          LEFT JOIN chart_of_accounts c ON e.account_id = c.id
          LEFT JOIN users u ON e.created_by = u.id
          WHERE e.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $expense_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Expense not found";
    header('Location: list.php');
    exit();
}

$expense = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-receipt-cutoff"></i> Expense Details</h2>
        <div>
            <?php if (hasRole(['Admin', 'Finance'])): ?>
                <a href="edit.php?id=<?php echo $expense['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($expense['expense_number']); ?></h5>
                        <span class="badge bg-<?php echo getStatusBadge($expense['payment_status']); ?> fs-6">
                            <?php echo $expense['payment_status']; ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong><i class="bi bi-calendar"></i> Expense Date:</strong><br>
                                <?php echo formatDate($expense['expense_date']); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong><i class="bi bi-building"></i> Vendor/Supplier:</strong><br>
                                <?php echo htmlspecialchars($expense['vendor_name']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong><i class="bi bi-tag"></i> Category:</strong><br>
                                <span class="badge bg-secondary">
                                    <?php echo htmlspecialchars($expense['category']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong><i class="bi bi-journal-text"></i> Account:</strong><br>
                                <?php echo htmlspecialchars($expense['account_code'] . ' - ' . $expense['account_name']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong><i class="bi bi-cash"></i> Amount:</strong><br>
                                <span class="fs-4 text-danger"><?php echo formatCurrency($expense['amount']); ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong><i class="bi bi-credit-card"></i> Payment Method:</strong><br>
                                <?php echo htmlspecialchars($expense['payment_method']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($expense['is_recurring']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-arrow-repeat"></i> Recurring Expense</h6>
                                <p class="mb-1">
                                    <strong>Frequency:</strong> <?php echo $expense['recurrence_frequency']; ?>
                                </p>
                                <?php if ($expense['next_recurrence_date']): ?>
                                <p class="mb-0">
                                    <strong>Next Due Date:</strong> <?php echo formatDate($expense['next_recurrence_date']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($expense['description']): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <p class="mb-2">
                                <strong><i class="bi bi-file-text"></i> Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($expense['description'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Record Information</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Created By:</strong><br>
                        <?php echo htmlspecialchars($expense['creator']); ?>
                    </p>
                    <p class="small mb-2">
                        <strong>Created At:</strong><br>
                        <?php echo formatDate($expense['created_at']); ?>
                    </p>
                    <p class="small mb-0">
                        <strong>Last Updated:</strong><br>
                        <?php echo formatDate($expense['updated_at']); ?>
                    </p>
                </div>
            </div>
            
            <?php if (hasRole(['Admin', 'Finance'])): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-gear"></i> Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="edit.php?id=<?php echo $expense['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Expense
                        </a>
                        <a href="delete.php?id=<?php echo $expense['id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to delete this expense?')">
                            <i class="bi bi-trash"></i> Delete Expense
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

