<?php
/**
 * Code Catalyst Labs - Add Cashbook Entry
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole(['Admin', 'Finance']);

$page_title = 'Add Cashbook Entry';

// Generate reference number
function generateCashbookReference($conn) {
    $prefix = 'CB';
    $year = date('Y');
    
    $query = "SELECT reference_number FROM cashbook 
              WHERE reference_number LIKE '$prefix-$year-%' 
              ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $lastNumber = (int)substr($row['reference_number'], -4);
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    return $prefix . '-' . $year . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

// Get accounts
$accounts_query = "SELECT id, account_code, account_name, account_type FROM chart_of_accounts 
                   WHERE is_active = TRUE 
                   ORDER BY account_code";
$accounts_result = mysqli_query($conn, $accounts_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reference_number = generateCashbookReference($conn);
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
    
    // Insert cashbook entry if no errors
    if (empty($errors)) {
        $query = "INSERT INTO cashbook (transaction_date, reference_number, account_id, transaction_type, 
                  payment_method, amount, description, category, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssissdssi", 
            $transaction_date, $reference_number, $account_id, $transaction_type, $payment_method,
            $amount, $description, $category, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $entry_id = mysqli_insert_id($conn);
            
            // Create ledger entry
            $entry_type = ($transaction_type === 'Income') ? 'Credit' : 'Debit';
            $ledger_query = "INSERT INTO ledger_entries (entry_date, reference_number, account_id, 
                            entry_type, amount, description, source_type, source_id, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, 'Cashbook', ?, ?)";
            $ledger_stmt = mysqli_prepare($conn, $ledger_query);
            mysqli_stmt_bind_param($ledger_stmt, "ssissdii", 
                $transaction_date, $reference_number, $account_id, $entry_type, $amount, 
                $description, $entry_id, $_SESSION['user_id']);
            mysqli_stmt_execute($ledger_stmt);
            mysqli_stmt_close($ledger_stmt);
            
            // Log audit
            logAudit($conn, 'CREATE', 'cashbook', $entry_id, "Created cashbook entry: $reference_number");
            
            $_SESSION['success'] = "Cashbook entry created successfully!";
            header('Location: cashbook.php');
            exit();
        } else {
            $errors[] = "Error creating entry. Please try again.";
        }
        
        mysqli_stmt_close($stmt);
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

include '../includes/header.php';
?>

<div class="container">
    <?php displayAlert(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-cash-stack"></i> Add Cashbook Entry</h2>
        <a href="cashbook.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Cashbook
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="transaction_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       value="<?php echo isset($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_type" class="form-label">Transaction Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="transaction_type" name="transaction_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Income" <?php echo (isset($_POST['transaction_type']) && $_POST['transaction_type'] === 'Income') ? 'selected' : ''; ?>>
                                        Income (Money In)
                                    </option>
                                    <option value="Expense" <?php echo (isset($_POST['transaction_type']) && $_POST['transaction_type'] === 'Expense') ? 'selected' : ''; ?>>
                                        Expense (Money Out)
                                    </option>
                                    <option value="Transfer" <?php echo (isset($_POST['transaction_type']) && $_POST['transaction_type'] === 'Transfer') ? 'selected' : ''; ?>>
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
                                            <?php echo (isset($_POST['account_id']) && $_POST['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name'] . ' (' . $account['account_type'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount (<?php echo CURRENCY_CODE; ?>) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amount" name="amount" 
                                       step="0.01" min="0" value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : ''; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="Cash" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Cash') ? 'selected' : ''; ?>>Cash</option>
                                    <option value="Bank Transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Bank Transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="Mobile Money" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Mobile Money') ? 'selected' : ''; ?>>Mobile Money</option>
                                    <option value="Cheque" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Cheque') ? 'selected' : ''; ?>>Cheque</option>
                                    <option value="Card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Card') ? 'selected' : ''; ?>>Card</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" 
                                   list="category-list"
                                   value="<?php echo isset($_POST['category']) ? htmlspecialchars($_POST['category']) : ''; ?>"
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
                            <textarea class="form-control" id="description" name="description" rows="3" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="form-text">Detailed description of the transaction</div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Entry
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
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Transaction Types</h6>
                </div>
                <div class="card-body">
                    <h6 class="text-success"><i class="bi bi-arrow-down-circle"></i> Income</h6>
                    <p class="small mb-3">Money coming in (sales, payments received, deposits, etc.)</p>
                    
                    <h6 class="text-danger"><i class="bi bi-arrow-up-circle"></i> Expense</h6>
                    <p class="small mb-3">Money going out (purchases, bills, payments made, etc.)</p>
                    
                    <h6 class="text-info"><i class="bi bi-arrow-left-right"></i> Transfer</h6>
                    <p class="small mb-0">Moving money between your accounts</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

