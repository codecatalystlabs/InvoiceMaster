<?php
/**
 * Code Catalyst Labs - Edit Cashbook Entry
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Edit Cashbook Entry';

// Get cashbook entry ID
$entry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($entry_id === 0) {
    $_SESSION['error'] = "Invalid entry ID";
    header('Location: cashbook.php');
    exit();
}

// Get entry details
$query = "SELECT * FROM cashbook WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $entry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Entry not found";
    header('Location: cashbook.php');
    exit();
}

$entry = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Get accounts
$accounts_query = "SELECT id, account_code, account_name, account_type FROM chart_of_accounts 
                   WHERE is_active = TRUE 
                   ORDER BY account_code";
$accounts_result = mysqli_query($conn, $accounts_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_date = clean($_POST['transaction_date']);
    $account_id = intval($_POST['account_id']);
    $transaction_type = clean($_POST['transaction_type']);
    $payment_method = clean($_POST['payment_method']);
    $amount = floatval($_POST['amount']);
    $description = clean($_POST['description']);
    $category = clean($_POST['category']);
    
    $errors = [];
    
    // Validation
    if (empty($transaction_date)) $errors[] = "Transaction date is required";
    if ($account_id === 0) $errors[] = "Account is required";
    if (empty($transaction_type)) $errors[] = "Transaction type is required";
    if ($amount <= 0) $errors[] = "Amount must be greater than 0";
    if (empty($description)) $errors[] = "Description is required";
    
    // Update cashbook entry if no errors
    if (empty($errors)) {
        $query = "UPDATE cashbook 
                  SET transaction_date = ?, account_id = ?, transaction_type = ?, 
                      payment_method = ?, amount = ?, description = ?, category = ?
                  WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sissdssi", 
            $transaction_date, $account_id, $transaction_type, $payment_method,
            $amount, $description, $category, $entry_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update ledger entry if exists
            $entry_type = ($transaction_type === 'Income') ? 'Credit' : 'Debit';
            $ledger_query = "UPDATE ledger_entries 
                            SET entry_date = ?, account_id = ?, entry_type = ?, amount = ?, description = ?
                            WHERE source_type = 'Cashbook' AND source_id = ?";
            $ledger_stmt = mysqli_prepare($conn, $ledger_query);
            mysqli_stmt_bind_param($ledger_stmt, "sisdsi", 
                $transaction_date, $account_id, $entry_type, $amount, $description, $entry_id);
            mysqli_stmt_execute($ledger_stmt);
            mysqli_stmt_close($ledger_stmt);
            
            // Log audit
            logAudit($conn, 'UPDATE', 'cashbook', $entry_id, "Updated cashbook entry: {$entry['reference_number']}");
            
            $_SESSION['success'] = "Cashbook entry updated successfully!";
            header('Location: cashbook.php');
            exit();
        } else {
            $errors[] = "Error updating entry. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        // Refresh entry data
        $query = "SELECT * FROM cashbook WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $entry_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $entry = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Edit Cashbook Entry</h2>
        <a href="cashbook.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Cashbook
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($entry['reference_number']); ?>" disabled>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="transaction_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       value="<?php echo $entry['transaction_date']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_type" class="form-label">Transaction Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="transaction_type" name="transaction_type" required>
                                    <option value="Income" <?php echo $entry['transaction_type'] === 'Income' ? 'selected' : ''; ?>>
                                        Income (Money In)
                                    </option>
                                    <option value="Expense" <?php echo $entry['transaction_type'] === 'Expense' ? 'selected' : ''; ?>>
                                        Expense (Money Out)
                                    </option>
                                    <option value="Transfer" <?php echo $entry['transaction_type'] === 'Transfer' ? 'selected' : ''; ?>>
                                        Transfer (Between Accounts)
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="account_id" class="form-label">Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="account_id" name="account_id" required>
                                <option value="">Select Account</option>
                                <?php 
                                mysqli_data_seek($accounts_result, 0);
                                while ($account = mysqli_fetch_assoc($accounts_result)): 
                                ?>
                                    <option value="<?php echo $account['id']; ?>"
                                            <?php echo ($entry['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name'] . ' (' . $account['account_type'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount (<?php echo APP_CURRENCY_CODE; ?>) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0" value="<?php echo $entry['amount']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="Cash" <?php echo $entry['payment_method'] === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="Bank Transfer" <?php echo $entry['payment_method'] === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="Mobile Money" <?php echo $entry['payment_method'] === 'Mobile Money' ? 'selected' : ''; ?>>Mobile Money</option>
                                    <option value="Cheque" <?php echo $entry['payment_method'] === 'Cheque' ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="Card" <?php echo $entry['payment_method'] === 'Card' ? 'selected' : ''; ?>>Card</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" 
                                   list="category-list"
                                   value="<?php echo htmlspecialchars($entry['category']); ?>"
                                   placeholder="e.g., Sales, Rent, Utilities">
                            <datalist id="category-list">
                                <option value="Sales">
                                <option value="Rent">
                                <option value="Utilities">
                                <option value="Salaries">
                                <option value="Office Supplies">
                                <option value="Marketing">
                                <option value="Other">
                            </datalist>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($entry['description']); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Entry
                            </button>
                            <a href="cashbook.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Entry Information</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <strong>Created:</strong><br>
                        <?php echo formatDate($entry['created_at']); ?>
                    </p>
                    <p class="small mb-0">
                        <strong>Last Updated:</strong><br>
                        <?php echo formatDate($entry['updated_at']); ?>
                    </p>
                </div>
            </div>
            
            <?php if ($entry['invoice_id'] || $entry['expense_id'] || $entry['service_id']): ?>
            <div class="card bg-warning text-dark mt-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Warning</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0">
                        This entry is linked to:
                        <?php if ($entry['invoice_id']): ?>
                            <br>• Invoice #<?php echo $entry['invoice_id']; ?>
                        <?php endif; ?>
                        <?php if ($entry['expense_id']): ?>
                            <br>• Expense #<?php echo $entry['expense_id']; ?>
                        <?php endif; ?>
                        <?php if ($entry['service_id']): ?>
                            <br>• Service #<?php echo $entry['service_id']; ?>
                        <?php endif; ?>
                        <br><br>Changes may affect related records.
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

