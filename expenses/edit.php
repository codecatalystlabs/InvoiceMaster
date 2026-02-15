<?php
/**
 * Code Catalyst Labs - Edit Expense
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Edit Expense';

// Get expense ID
$expense_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($expense_id === 0) {
    $_SESSION['error'] = "Invalid expense ID";
    header('Location: list.php');
    exit();
}

// Get expense details
$query = "SELECT * FROM expenses WHERE id = ?";
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

// Get expense accounts
$accounts_query = "SELECT id, account_code, account_name FROM chart_of_accounts 
                   WHERE account_type = 'Expense' AND is_active = TRUE 
                   ORDER BY account_code";
$accounts_result = mysqli_query($conn, $accounts_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_date = clean($_POST['expense_date']);
    $account_id = intval($_POST['account_id']);
    $vendor_name = clean($_POST['vendor_name']);
    $category = clean($_POST['category']);
    $amount = floatval($_POST['amount']);
    $payment_method = clean($_POST['payment_method']);
    $payment_status = clean($_POST['payment_status']);
    $description = clean($_POST['description']);
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $recurrence_frequency = $is_recurring ? clean($_POST['recurrence_frequency']) : null;
    $next_recurrence_date = $is_recurring ? clean($_POST['next_recurrence_date']) : null;
    
    $errors = [];
    
    // Validation
    if (empty($expense_date)) {
        $errors[] = "Expense date is required";
    }
    
    if ($account_id === 0) {
        $errors[] = "Account is required";
    }
    
    if (empty($vendor_name)) {
        $errors[] = "Vendor name is required";
    }
    
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    
    if ($amount <= 0) {
        $errors[] = "Amount must be greater than 0";
    }
    
    // Update expense if no errors
    if (empty($errors)) {
        $query = "UPDATE expenses SET expense_date = ?, account_id = ?, vendor_name = ?, category = ?, 
                  amount = ?, payment_method = ?, payment_status = ?, is_recurring = ?, 
                  recurrence_frequency = ?, next_recurrence_date = ?, description = ?
                  WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sissdssisssi", 
            $expense_date, $account_id, $vendor_name, $category, $amount, $payment_method, 
            $payment_status, $is_recurring, $recurrence_frequency, $next_recurrence_date, 
            $description, $expense_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log audit
            logAudit($conn, 'UPDATE', 'expense', $expense_id, "Updated expense: {$expense['expense_number']}");
            
            $_SESSION['success'] = "Expense updated successfully!";
            header('Location: view.php?id=' . $expense_id);
            exit();
        } else {
            $errors[] = "Error updating expense. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Display errors and refresh data
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        // Refresh expense data
        $query = "SELECT * FROM expenses WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $expense_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $expense = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Edit Expense</h2>
        <a href="view.php?id=<?php echo $expense_id; ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to View
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Expense Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($expense['expense_number']); ?>" disabled>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="expense_date" class="form-label">Expense Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="expense_date" name="expense_date" 
                                       value="<?php echo $expense['expense_date']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="account_id" class="form-label">Expense Account <span class="text-danger">*</span></label>
                                <select class="form-select" id="account_id" name="account_id" required>
                                    <option value="">Select Account</option>
                                    <?php while ($account = mysqli_fetch_assoc($accounts_result)): ?>
                                        <option value="<?php echo $account['id']; ?>"
                                                <?php echo ($expense['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="vendor_name" class="form-label">Vendor/Supplier <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="vendor_name" name="vendor_name" 
                                       value="<?php echo htmlspecialchars($expense['vendor_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="category" name="category" 
                                       list="category-list"
                                       value="<?php echo htmlspecialchars($expense['category']); ?>" required>
                                <datalist id="category-list">
                                    <option value="Rent">
                                    <option value="Utilities">
                                    <option value="Salaries">
                                    <option value="Office Supplies">
                                    <option value="Marketing">
                                    <option value="Insurance">
                                    <option value="Maintenance">
                                    <option value="Travel">
                                    <option value="Software">
                                    <option value="Professional Fees">
                                    <option value="Other">
                                </datalist>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount (<?php echo CURRENCY_CODE; ?>) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" 
                                       value="<?php echo $expense['amount']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="Cash" <?php echo $expense['payment_method'] === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="Bank Transfer" <?php echo $expense['payment_method'] === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="Mobile Money" <?php echo $expense['payment_method'] === 'Mobile Money' ? 'selected' : ''; ?>>Mobile Money</option>
                                    <option value="Cheque" <?php echo $expense['payment_method'] === 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="Card" <?php echo $expense['payment_method'] === 'Card' ? 'selected' : ''; ?>>Card</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payment_status" class="form-label">Payment Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_status" name="payment_status" required>
                                <option value="Pending" <?php echo $expense['payment_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Paid" <?php echo $expense['payment_status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="Partially Paid" <?php echo $expense['payment_status'] === 'Partially Paid' ? 'selected' : ''; ?>>Partially Paid</option>
                                <option value="Overdue" <?php echo $expense['payment_status'] === 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($expense['description']); ?></textarea>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" 
                                       value="1" onchange="toggleRecurringFields()"
                                       <?php echo $expense['is_recurring'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_recurring">
                                    <strong>This is a recurring expense</strong>
                                </label>
                            </div>
                        </div>
                        
                        <div id="recurring-fields" style="display: <?php echo $expense['is_recurring'] ? 'block' : 'none'; ?>;">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="recurrence_frequency" class="form-label">Recurrence Frequency</label>
                                    <select class="form-select" id="recurrence_frequency" name="recurrence_frequency">
                                        <option value="">Select Frequency</option>
                                        <option value="Daily" <?php echo $expense['recurrence_frequency'] === 'Daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="Weekly" <?php echo $expense['recurrence_frequency'] === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="Monthly" <?php echo $expense['recurrence_frequency'] === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="Quarterly" <?php echo $expense['recurrence_frequency'] === 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                        <option value="Yearly" <?php echo $expense['recurrence_frequency'] === 'Yearly' ? 'selected' : ''; ?>>Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="next_recurrence_date" class="form-label">Next Recurrence Date</label>
                                    <input type="date" class="form-control" id="next_recurrence_date" name="next_recurrence_date" 
                                           value="<?php echo $expense['next_recurrence_date']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Expense
                            </button>
                            <a href="view.php?id=<?php echo $expense_id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Edit Information</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Created:</strong><br>
                        <?php echo formatDate($expense['created_at']); ?>
                    </p>
                    <p class="small mb-0">
                        <strong>Last Updated:</strong><br>
                        <?php echo formatDate($expense['updated_at']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRecurringFields() {
    const checkbox = document.getElementById('is_recurring');
    const fields = document.getElementById('recurring-fields');
    const frequency = document.getElementById('recurrence_frequency');
    const nextDate = document.getElementById('next_recurrence_date');
    
    if (checkbox.checked) {
        fields.style.display = 'block';
        frequency.required = true;
        nextDate.required = true;
    } else {
        fields.style.display = 'none';
        frequency.required = false;
        nextDate.required = false;
    }
}
</script>

<?php include '../includes/footer.php'; ?>

